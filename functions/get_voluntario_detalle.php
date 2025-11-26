<?php
session_start();
require "../config/conexion.php";

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 2) {
    http_response_code(403);
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    http_response_code(400);
    exit;
}

// Get Volunteer Info
$sql = "SELECT v.*, u.usuario 
        FROM voluntarios v 
        JOIN usuarios u ON v.usuario_id = u.id 
        WHERE v.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$voluntario = $stmt->get_result()->fetch_assoc();

if (!$voluntario) {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}

// Get Assignments (Turnos)
// Assuming 'turnos' table has 'voluntario_id' which refers to 'usuarios.id' (based on get_turnos.php)
// Wait, get_turnos.php says: WHERE t.voluntario_id = ? (and binds $voluntario_id which comes from usuarios table)
// So we need the usuario_id of this volunteer to query turnos.
$usuario_id = $voluntario['usuario_id'];

$sql_turnos = "SELECT t.fecha, t.hora, p.nombre AS persona
               FROM turnos t
               JOIN personas_mayores p ON t.persona_id = p.id
               WHERE t.voluntario_id = ?
               ORDER BY t.fecha DESC, t.hora DESC";
$stmt = $conn->prepare($sql_turnos);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$turnos = [];
while ($row = $result->fetch_assoc()) {
    $turnos[] = $row;
}

$voluntario['turnos'] = $turnos;

header('Content-Type: application/json');
echo json_encode($voluntario);
?>
