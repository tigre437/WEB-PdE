<?php
session_start();
require "../config/conexion.php";

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] < 1) {
    http_response_code(403);
    exit;
}

// Obtener id del voluntario
$sql = "SELECT id FROM usuarios WHERE usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['usuario']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$voluntario_id = $user['id'];

// Obtener turnos
$sql2 = "SELECT t.fecha, t.hora, p.nombre AS persona
         FROM turnos t
         JOIN personas_mayores p ON t.persona_id = p.id
         WHERE t.voluntario_id = ?
         ORDER BY t.fecha, t.hora";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i", $voluntario_id);
$stmt2->execute();
$result2 = $stmt2->get_result();

$events = [];
while($row = $result2->fetch_assoc()){
    $start = $row['fecha'] . 'T' . $row['hora'];
    $events[] = [
        'title' => $row['persona'],
        'start' => $start
    ];
}

header('Content-Type: application/json');
echo json_encode($events);