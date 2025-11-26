<?php
session_start();
require "../config/conexion.php";

// Check admin permissions
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 2) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // List all volunteers
        $sql = "SELECT v.*, u.usuario 
                FROM voluntarios v 
                JOIN usuarios u ON v.usuario_id = u.id 
                WHERE u.rol = 1";
        $result = $conn->query($sql);
        $voluntarios = [];
        while ($row = $result->fetch_assoc()) {
            $voluntarios[] = $row;
        }
        echo json_encode($voluntarios);
        break;

    case 'POST':
        // Create new volunteer
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (empty($data['usuario']) || empty($data['password']) || empty($data['nombre'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }

        $conn->begin_transaction();

        try {
            // 1. Create User
            $stmt = $conn->prepare("INSERT INTO usuarios (usuario, password, rol) VALUES (?, ?, 1)");
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt->bind_param("ss", $data['usuario'], $hashed_password);
            $stmt->execute();
            $usuario_id = $conn->insert_id;

            // 2. Create Volunteer Profile
            $stmt = $conn->prepare("INSERT INTO voluntarios (usuario_id, nombre, apellidos, fecha_nacimiento, dni, telefono, anotaciones) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssss", 
                $usuario_id, 
                $data['nombre'], 
                $data['apellidos'], 
                $data['fecha_nacimiento'], 
                $data['dni'], 
                $data['telefono'],
                $data['anotaciones']
            );
            $stmt->execute();

            $conn->commit();
            echo json_encode(['success' => true, 'id' => $conn->insert_id]);
        } catch (Exception $e) {
            $conn->rollback();
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'PUT':
        // Update volunteer
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing ID']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE voluntarios SET nombre=?, apellidos=?, fecha_nacimiento=?, dni=?, telefono=?, anotaciones=? WHERE id=?");
        $stmt->bind_param("ssssssi", 
            $data['nombre'], 
            $data['apellidos'], 
            $data['fecha_nacimiento'], 
            $data['dni'], 
            $data['telefono'],
            $data['anotaciones'],
            $data['id']
        );
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => $conn->error]);
        }
        break;

    case 'DELETE':
        // Delete volunteer
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing ID']);
            exit;
        }

        // Get usuario_id first to delete the user account too
        $stmt = $conn->prepare("SELECT usuario_id FROM voluntarios WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row) {
            $usuario_id = $row['usuario_id'];
            // Deleting user will cascade to volunteer if FK set up correctly, 
            // but let's be safe and delete user which is the parent
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $usuario_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => $conn->error]);
            }
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Volunteer not found']);
        }
        break;
}
?>
