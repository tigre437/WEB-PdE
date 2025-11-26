<?php
session_start();
require "../config/conexion.php";

header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] < 1) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Create or Cancel Instance
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['action']) && $input['action'] === 'cancel_instance') {
        // Cancel specific instance of a recurring event
        $cita_id = $input['cita_id'];
        $fecha = $input['fecha'];
        
        $sql = "INSERT INTO citas_canceladas (cita_id, fecha_cancelada) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $cita_id, $fecha);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        exit;
    }

    // Create new appointment
    $titulo = $input['titulo'];
    $descripcion = $input['descripcion'] ?? '';
    $fecha_inicio = $input['fecha_inicio'];
    $hora_inicio = $input['hora_inicio'];
    $hora_fin = $input['hora_fin'];
    $tipo = $input['tipo'];
    $voluntario_id = !empty($input['voluntario_id']) ? $input['voluntario_id'] : null;
    
    $dia_semana = null;
    if ($tipo === 'semanal') {
        // Calculate day of week from start date (1=Mon, 7=Sun)
        $dia_semana = date('N', strtotime($fecha_inicio));
    }

    $sql = "INSERT INTO citas (titulo, descripcion, fecha_inicio, hora_inicio, hora_fin, tipo, dia_semana, voluntario_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssii", $titulo, $descripcion, $fecha_inicio, $hora_inicio, $hora_fin, $tipo, $dia_semana, $voluntario_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }

} elseif ($method === 'PUT') {
    // Update appointment
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = $input['id'];
    $titulo = $input['titulo'];
    $descripcion = $input['descripcion'] ?? '';
    $fecha_inicio = $input['fecha_inicio'];
    $hora_inicio = $input['hora_inicio'];
    $hora_fin = $input['hora_fin'];
    $tipo = $input['tipo'];
    $voluntario_id = !empty($input['voluntario_id']) ? $input['voluntario_id'] : null;
    
    $dia_semana = null;
    if ($tipo === 'semanal') {
        $dia_semana = date('N', strtotime($fecha_inicio));
    }

    $sql = "UPDATE citas SET titulo=?, descripcion=?, fecha_inicio=?, hora_inicio=?, hora_fin=?, tipo=?, dia_semana=?, voluntario_id=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssiii", $titulo, $descripcion, $fecha_inicio, $hora_inicio, $hora_fin, $tipo, $dia_semana, $voluntario_id, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }

} elseif ($method === 'DELETE') {
    // Delete entire appointment (or cancel unique)
    $id = $_GET['id'];
    
    // Hard delete for simplicity, or soft delete if preferred. 
    // Requirement says "Eliminar citas", implies removal.
    // But for unique appointments, maybe we want to keep history? 
    // Let's do hard delete for now as per "CRUD" standard unless specified otherwise.
    
    $sql = "DELETE FROM citas WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}
?>
