<?php
// Prevent any output before JSON
ob_start();
error_reporting(0); // Suppress errors for JSON response
ini_set('display_errors', 0);

session_start();
require "../config/conexion.php";

// Clear any output that might have occurred
ob_end_clean();
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
        // Cancel specific instance of a recurring event and create notice
        $cita_id = $input['cita_id'];
        $fecha = $input['fecha'];
        
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
        
        // Get cita details
        $c_sql = "SELECT titulo, descripcion, hora_inicio, hora_fin FROM citas WHERE id = ?";
        $c_stmt = $conn->prepare($c_sql);
        $c_stmt->bind_param("i", $cita_id);
        $c_stmt->execute();
        $c_res = $c_stmt->get_result();
        $cita = $c_res->fetch_assoc();
        
        // Start transaction
        $conn->begin_transaction();
        
        
        try {
            // Insert into citas_canceladas
            $sql = "INSERT INTO citas_canceladas (cita_id, fecha_cancelada) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $cita_id, $fecha);
            $stmt->execute();
            
            // Try to create aviso (notice) if table exists
            $aviso_id = null;
            $check_table = $conn->query("SHOW TABLES LIKE 'avisos'");
            if ($check_table && $check_table->num_rows > 0) {
                try {
                    $aviso_sql = "INSERT INTO avisos (cita_id, fecha_cita, voluntario_cancelado_id, titulo, descripcion, hora_inicio, hora_fin) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $aviso_stmt = $conn->prepare($aviso_sql);
                    $aviso_stmt->bind_param("isissss", $cita_id, $fecha, $voluntario_id, $cita['titulo'], $cita['descripcion'], $cita['hora_inicio'], $cita['hora_fin']);
                    $aviso_stmt->execute();
                    $aviso_id = $conn->insert_id;
                } catch (Exception $aviso_error) {
                    // Aviso creation failed, but cancellation succeeded
                    error_log("Failed to create aviso: " . $aviso_error->getMessage());
                }
            }
            
            $conn->commit();
            echo json_encode(['success' => true, 'aviso_id' => $aviso_id]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    if (isset($input['action']) && $input['action'] === 'reconfirm_instance') {
        // Re-confirm attendance - remove from cancelled and close related aviso
        $cita_id = $input['cita_id'];
        $fecha = $input['fecha'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Remove from citas_canceladas
            $sql = "DELETE FROM citas_canceladas WHERE cita_id = ? AND fecha_cancelada = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $cita_id, $fecha);
            $stmt->execute();
            
            
            // Close related aviso if table exists
            $check_table = $conn->query("SHOW TABLES LIKE 'avisos'");
            if ($check_table && $check_table->num_rows > 0) {
                try {
                    $aviso_sql = "UPDATE avisos SET estado = 'cerrado' WHERE cita_id = ? AND fecha_cita = ? AND estado = 'abierto'";
                    $aviso_stmt = $conn->prepare($aviso_sql);
                    $aviso_stmt->bind_param("is", $cita_id, $fecha);
                    $aviso_stmt->execute();
                } catch (Exception $aviso_error) {
                    error_log("Failed to close aviso: " . $aviso_error->getMessage());
                }
            }
            
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
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
