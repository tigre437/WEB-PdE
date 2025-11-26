<?php
session_start();
require "../config/conexion.php";

header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] < 1) {
    echo json_encode([]);
    exit;
}

$start = $_GET['start'] ?? date('Y-m-d');
$end = $_GET['end'] ?? date('Y-m-d', strtotime('+1 month'));

// Get all active appointments
$sql = "SELECT c.*, v.nombre as vol_nombre, v.apellidos as vol_apellidos 
        FROM citas c 
        LEFT JOIN voluntarios v ON c.voluntario_id = v.id 
        WHERE c.estado = 'activa'";

// If volunteer, only show assigned or maybe all? 
// Requirement: "Los voluntarios deben poder ver sus citas en su panel"
// Usually they only see theirs.
if ($_SESSION['rol'] == 1) {
    // Get volunteer ID
    $u_sql = "SELECT id FROM usuarios WHERE usuario = ?";
    $u_stmt = $conn->prepare($u_sql);
    $u_stmt->bind_param("s", $_SESSION['usuario']);
    $u_stmt->execute();
    $u_res = $u_stmt->get_result();
    $u_row = $u_res->fetch_assoc();
    $user_id = $u_row['id'];
    
    // Get volunteer record id
    $v_sql = "SELECT id FROM voluntarios WHERE usuario_id = ?";
    $v_stmt = $conn->prepare($v_sql);
    $v_stmt->bind_param("i", $user_id);
    $v_stmt->execute();
    $v_res = $v_stmt->get_result();
    $v_row = $v_res->fetch_assoc();
    $voluntario_id = $v_row['id'];

    $sql .= " AND c.voluntario_id = $voluntario_id";
}

$result = $conn->query($sql);

$events = [];

// Get cancellations
$cancel_sql = "SELECT * FROM citas_canceladas";
$cancel_res = $conn->query($cancel_sql);
$cancellations = [];
while ($row = $cancel_res->fetch_assoc()) {
    $cancellations[$row['cita_id']][] = $row['fecha_cancelada'];
}

while ($row = $result->fetch_assoc()) {
    $vol_name = $row['vol_nombre'] ? $row['vol_nombre'] . ' ' . $row['vol_apellidos'] : 'Sin asignar';
    $color = $row['vol_nombre'] ? '#28a745' : '#ffc107'; // Green if assigned, Yellow if not
    
    if ($row['tipo'] === 'unica') {
        // Check if in range
        if ($row['fecha_inicio'] >= $start && $row['fecha_inicio'] <= $end) {
            $events[] = [
                'id' => $row['id'],
                'title' => $row['titulo'] . ' (' . $vol_name . ')',
                'start' => $row['fecha_inicio'] . 'T' . $row['hora_inicio'],
                'end' => $row['fecha_inicio'] . 'T' . $row['hora_fin'],
                'extendedProps' => $row,
                'backgroundColor' => $color
            ];
        }
    } elseif ($row['tipo'] === 'semanal') {
        // Generate weekly events
        $current = strtotime($row['fecha_inicio']);
        $end_ts = strtotime($end);
        $start_ts = strtotime($start);
        
        // If start date of event is after requested range end, skip
        if ($current > $end_ts) continue;

        // Move current to the first occurrence within or after start range
        // But we must respect the day of week.
        // Actually, simpler loop: iterate from event start date by +1 week until > end range.
        
        while ($current <= $end_ts) {
            $date_str = date('Y-m-d', $current);
            
            if ($current >= $start_ts) {
                // Check if cancelled
                if (!isset($cancellations[$row['id']]) || !in_array($date_str, $cancellations[$row['id']])) {
                    $events[] = [
                        'id' => $row['id'], // Same ID for all instances, but we might need unique ID for calendar? FullCalendar handles it.
                        'title' => $row['titulo'] . ' (' . $vol_name . ')',
                        'start' => $date_str . 'T' . $row['hora_inicio'],
                        'end' => $date_str . 'T' . $row['hora_fin'],
                        'extendedProps' => array_merge($row, ['instance_date' => $date_str]),
                        'backgroundColor' => $color
                    ];
                }
            }
            $current = strtotime('+1 week', $current);
        }
    }
}

echo json_encode($events);
?>
