<?php
session_start();
require "../config/conexion.php";

header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] < 1) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get all open notices or specific notice
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
        $sql = "SELECT a.*, v.nombre, v.apellidos, c.titulo as cita_titulo 
                FROM avisos a 
                JOIN voluntarios v ON a.voluntario_cancelado_id = v.id
                JOIN citas c ON a.cita_id = c.id
                WHERE a.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
    } else {
        // Get notices based on role
        if ($_SESSION['rol'] >= 2) {
            // Admins see ALL notices
            $sql = "SELECT a.*, v.nombre, v.apellidos, c.titulo as cita_titulo 
                    FROM avisos a 
                    JOIN voluntarios v ON a.voluntario_cancelado_id = v.id
                    JOIN citas c ON a.cita_id = c.id
                    ORDER BY a.created_at DESC";
        } else {
            // Volunteers only see OPEN notices
            $sql = "SELECT a.*, v.nombre, v.apellidos, c.titulo as cita_titulo 
                    FROM avisos a 
                    JOIN voluntarios v ON a.voluntario_cancelado_id = v.id
                    JOIN citas c ON a.cita_id = c.id
                    WHERE a.estado = 'abierto'
                    ORDER BY a.created_at DESC";
        }
        $stmt = $conn->prepare($sql);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (isset($_GET['id'])) {
        $aviso = $result->fetch_assoc();
        echo json_encode($aviso ?: ['success' => false, 'error' => 'Not found']);
    } else {
        $avisos = [];
        while ($row = $result->fetch_assoc()) {
            $avisos[] = $row;
        }
        echo json_encode($avisos);
    }

} elseif ($method === 'POST') {
    // Create new notice
    $input = json_decode(file_get_contents('php://input'), true);
    
    $cita_id = $input['cita_id'];
    $fecha_cita = $input['fecha_cita'];
    $voluntario_cancelado_id = $input['voluntario_cancelado_id'];
    $titulo = $input['titulo'];
    $descripcion = $input['descripcion'] ?? '';
    $hora_inicio = $input['hora_inicio'] ?? null;
    $hora_fin = $input['hora_fin'] ?? null;
    
    $sql = "INSERT INTO avisos (cita_id, fecha_cita, voluntario_cancelado_id, titulo, descripcion, hora_inicio, hora_fin) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isissss", $cita_id, $fecha_cita, $voluntario_cancelado_id, $titulo, $descripcion, $hora_inicio, $hora_fin);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'aviso_id' => $conn->insert_id]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }

} elseif ($method === 'PUT') {
    // Update notice status
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = $input['id'];
    $estado = $input['estado'];
    
    $sql = "UPDATE avisos SET estado = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $estado, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }

} elseif ($method === 'DELETE') {
    // Delete notice
    $id = $_GET['id'];
    
    $sql = "DELETE FROM avisos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}
?>
