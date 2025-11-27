<?php
session_start();
require "../config/conexion.php";

// Check admin permissions only
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 2) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($data['id']) || empty($data['nueva_password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    // Get usuario_id from voluntario id
    $stmt = $conn->prepare("SELECT usuario_id FROM voluntarios WHERE id = ?");
    $stmt->bind_param("i", $data['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Volunteer not found']);
        exit;
    }
    
    $usuario_id = $row['usuario_id'];
    
    // Update password
    $hashed_password = password_hash($data['nueva_password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $usuario_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Contraseña actualizada correctamente']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error updating password: ' . $conn->error]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
