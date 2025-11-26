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

// Validate required fields
if (empty($data['current_password']) || empty($data['new_password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Get user from session
$sql = "SELECT id, password FROM usuarios WHERE usuario = ?";
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

// Verify current password
if (!password_verify($data['current_password'], $user['password'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Current password is incorrect']);
    exit;
}

// Update password
$hashed_password = password_hash($data['new_password'], PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
$stmt->bind_param("si", $hashed_password, $user['id']);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => $conn->error]);
}
?>
