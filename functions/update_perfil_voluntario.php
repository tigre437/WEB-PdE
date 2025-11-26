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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

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

// Get volunteer ID to ensure they're updating their own profile
$sql = "SELECT id FROM voluntarios WHERE usuario_id = ?";
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

// Update volunteer profile
$stmt = $conn->prepare("UPDATE voluntarios SET nombre=?, apellidos=?, fecha_nacimiento=?, dni=?, telefono=? WHERE id=?");
$stmt->bind_param("sssssi", 
    $data['nombre'], 
    $data['apellidos'], 
    $data['fecha_nacimiento'], 
    $data['dni'], 
    $data['telefono'],
    $voluntario['id']
);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => $conn->error]);
}
?>
