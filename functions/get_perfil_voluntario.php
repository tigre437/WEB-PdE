<?php
session_start();
require "../config/conexion.php";

// Check permissions - allow both volunteers (rol=1) and admins (rol=2)
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2)) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// Get user ID from session
$sql = "SELECT id FROM usuarios WHERE usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['usuario']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

$usuario_id = $user['id'];

// Get volunteer profile data
$sql = "SELECT v.*, u.usuario 
        FROM voluntarios v 
        JOIN usuarios u ON v.usuario_id = u.id 
        WHERE v.usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$voluntario = $result->fetch_assoc();

if (!$voluntario) {
    http_response_code(404);
    echo json_encode(['error' => 'Volunteer profile not found']);
    exit;
}

// Get assigned appointments (turnos)
// Using usuario_id as turnos.voluntario_id references usuarios.id
$sql = "SELECT t.fecha, t.hora, p.nombre AS persona
        FROM turnos t
        LEFT JOIN personas_mayores p ON t.persona_id = p.id
        WHERE t.voluntario_id = ?
        ORDER BY t.fecha DESC, t.hora DESC
        LIMIT 20";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$turnos = [];
while ($row = $result->fetch_assoc()) {
    $turnos[] = [
        'fecha' => $row['fecha'],
        'hora' => $row['hora'],
        'titulo' => 'Turno asignado',
        'descripcion' => '',
        'persona' => $row['persona'] ?: 'N/A',
        'tipo' => 'turno'
    ];
}

$voluntario['turnos'] = $turnos;

echo json_encode($voluntario);
?>

