<?php
session_start();
require "../config/conexion.php";

// Check permissions - allow both admins (rol=2) and volunteers (rol=1)
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] != 2 && $_SESSION['rol'] != 1)) {
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

if (!isset($_FILES['foto']) || !isset($_POST['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing file or ID']);
    exit;
}

$id = $_POST['id'];
$file = $_FILES['foto'];

// If user is a volunteer (rol=1), verify they're updating their own profile
if ($_SESSION['rol'] == 1) {
    $sql = "SELECT v.id FROM voluntarios v 
            JOIN usuarios u ON v.usuario_id = u.id 
            WHERE u.usuario = ? AND v.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $_SESSION['usuario'], $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['error' => 'You can only update your own photo']);
        exit;
    }
}

// Validate file
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowed_types)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed.']);
    exit;
}

if ($file['size'] > 5 * 1024 * 1024) { // 5MB
    http_response_code(400);
    echo json_encode(['error' => 'File too large. Max 5MB.']);
    exit;
}

// Create directory if not exists (redundant check but safe)
$upload_dir = '../public/uploads/voluntarios/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Generate unique filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'vol_' . $id . '_' . time() . '.' . $ext;
$target_path = $upload_dir . $filename;
$public_path = 'uploads/voluntarios/' . $filename;

if (move_uploaded_file($file['tmp_name'], $target_path)) {
    // Update DB
    $stmt = $conn->prepare("UPDATE voluntarios SET foto = ? WHERE id = ?");
    $stmt->bind_param("si", $public_path, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'path' => $public_path]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database update failed']);
    }
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to move uploaded file']);
}
?>
