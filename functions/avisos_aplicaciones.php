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
    // Get applications
    if ($_SESSION['rol'] >= 2) {
        // Admin: get all applications with details
        if (isset($_GET['aviso_id'])) {
            $aviso_id = $_GET['aviso_id'];
            $sql = "SELECT aa.*, v.nombre, v.apellidos, v.telefono, v.email, u.usuario
                    FROM avisos_aplicaciones aa
                    JOIN voluntarios v ON aa.voluntario_id = v.id
                    JOIN usuarios u ON v.usuario_id = u.id
                    WHERE aa.aviso_id = ?
                    ORDER BY aa.created_at ASC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $aviso_id);
        } else {
            $sql = "SELECT aa.*, v.nombre, v.apellidos, a.titulo as aviso_titulo
                    FROM avisos_aplicaciones aa
                    JOIN voluntarios v ON aa.voluntario_id = v.id
                    JOIN avisos a ON aa.aviso_id = a.id
                    WHERE aa.estado = 'pendiente'
                    ORDER BY aa.created_at DESC";
            $stmt = $conn->prepare($sql);
        }
    } else {
        // Volunteer: get only their applications
        $u_sql = "SELECT id FROM usuarios WHERE usuario = ?";
        $u_stmt = $conn->prepare($u_sql);
        $u_stmt->bind_param("s", $_SESSION['usuario']);
        $u_stmt->execute();
        $u_res = $u_stmt->get_result();
        $u_row = $u_res->fetch_assoc();
        $user_id = $u_row['id'];
        
        $v_sql = "SELECT id FROM voluntarios WHERE usuario_id = ?";
        $v_stmt = $conn->prepare($v_sql);
        $v_stmt->bind_param("i", $user_id);
        $v_stmt->execute();
        $v_res = $v_stmt->get_result();
        $v_row = $v_res->fetch_assoc();
        $voluntario_id = $v_row['id'];
        
        $sql = "SELECT aa.*, a.titulo as aviso_titulo, a.fecha_cita
                FROM avisos_aplicaciones aa
                JOIN avisos a ON aa.aviso_id = a.id
                WHERE aa.voluntario_id = ?
                ORDER BY aa.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $voluntario_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $aplicaciones = [];
    while ($row = $result->fetch_assoc()) {
        $aplicaciones[] = $row;
    }
    echo json_encode($aplicaciones);

} elseif ($method === 'POST') {
    // Volunteer applies for a notice
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Get volunteer ID
    $u_sql = "SELECT id FROM usuarios WHERE usuario = ?";
    $u_stmt = $conn->prepare($u_sql);
    $u_stmt->bind_param("s", $_SESSION['usuario']);
    $u_stmt->execute();
    $u_res = $u_stmt->get_result();
    $u_row = $u_res->fetch_assoc();
    $user_id = $u_row['id'];
    
    $v_sql = "SELECT id FROM voluntarios WHERE usuario_id = ?";
    $v_stmt = $conn->prepare($v_sql);
    $v_stmt->bind_param("i", $user_id);
    $v_stmt->execute();
    $v_res = $v_stmt->get_result();
    $v_row = $v_res->fetch_assoc();
    $voluntario_id = $v_row['id'];
    
    $aviso_id = $input['aviso_id'];
    $mensaje = $input['mensaje'] ?? '';
    
    $sql = "INSERT INTO avisos_aplicaciones (aviso_id, voluntario_id, mensaje) 
            VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $aviso_id, $voluntario_id, $mensaje);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        if ($conn->errno == 1062) { // Duplicate entry
            echo json_encode(['success' => false, 'error' => 'Ya has aplicado a este aviso']);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
    }

} elseif ($method === 'PUT') {
    // Admin approves/rejects application
    if ($_SESSION['rol'] < 2) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = $input['id'];
    $estado = $input['estado']; // 'aprobado' or 'rechazado'
    
    if ($estado === 'aprobado') {
        // Get application details
        $sql = "SELECT aa.*, a.cita_id, a.fecha_cita 
                FROM avisos_aplicaciones aa
                JOIN avisos a ON aa.aviso_id = a.id
                WHERE aa.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $app = $result->fetch_assoc();
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update application status
            $sql1 = "UPDATE avisos_aplicaciones SET estado = ? WHERE id = ?";
            $stmt1 = $conn->prepare($sql1);
            $stmt1->bind_param("si", $estado, $id);
            $stmt1->execute();
            
            // Update cita to assign new volunteer
            $sql2 = "UPDATE citas SET voluntario_id = ? WHERE id = ?";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param("ii", $app['voluntario_id'], $app['cita_id']);
            $stmt2->execute();
            
            // Remove from cancelled list
            $sql3 = "DELETE FROM citas_canceladas WHERE cita_id = ? AND fecha_cancelada = ?";
            $stmt3 = $conn->prepare($sql3);
            $stmt3->bind_param("is", $app['cita_id'], $app['fecha_cita']);
            $stmt3->execute();
            
            // Close the aviso
            $sql4 = "UPDATE avisos SET estado = 'asignado' WHERE id = ?";
            $stmt4 = $conn->prepare($sql4);
            $stmt4->bind_param("i", $app['aviso_id']);
            $stmt4->execute();
            
            // Reject other pending applications for this aviso
            $sql5 = "UPDATE avisos_aplicaciones SET estado = 'rechazado' WHERE aviso_id = ? AND id != ? AND estado = 'pendiente'";
            $stmt5 = $conn->prepare($sql5);
            $stmt5->bind_param("ii", $app['aviso_id'], $id);
            $stmt5->execute();
            
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        // Just update status for rejection
        $sql = "UPDATE avisos_aplicaciones SET estado = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $estado, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
    }
}
?>
