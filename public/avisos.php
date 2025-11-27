<?php
session_start();
require "../config/conexion.php";

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] < 1) {
    header("Location: login.php");
    exit;
}

// Get volunteer data
$sql = "SELECT id FROM usuarios WHERE usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['usuario']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$datos_stmt = $conn->prepare("SELECT * FROM voluntarios WHERE usuario_id = ?");
$datos_stmt->bind_param("i", $user['id']);
$datos_stmt->execute();
$result = $datos_stmt->get_result();
$datos = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Avisos - Panel de Voluntarios</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/avisos.css">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="avisos-container">
        <h1><i class="fas fa-bullhorn"></i> Avisos de Citas Disponibles</h1>
        <p>Aquí puedes ver las citas que otros voluntarios han cancelado y aplicar para tomarlas.</p>
        
        <div id="avisos-list">
            <p style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Cargando avisos...</p>
        </div>
    </div>
</div>

<!-- Application Modal -->
<div id="applicationModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Aplicar para esta cita</h2>
        <p id="modal-aviso-info"></p>
        <label for="mensaje">Mensaje opcional:</label>
        <textarea id="mensaje" rows="4" placeholder="Puedes dejar un mensaje explicando por qué quieres tomar esta cita..."></textarea>
        <button class="submit-btn" id="submitApplication">Enviar Aplicación</button>
    </div>
</div>

<script>
let currentAvisoId = null;

// Load avisos
function loadAvisos() {
    fetch('../functions/avisos.php')
        .then(response => response.json())
        .then(avisos => {
            const container = document.getElementById('avisos-list');
            
            if (avisos.length === 0) {
                container.innerHTML = `
                    <div class="no-avisos">
                        <i class="fas fa-check-circle"></i>
                        <h3>No hay avisos disponibles</h3>
                        <p>Actualmente no hay citas canceladas que necesiten voluntarios.</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = '';
            avisos.forEach(aviso => {
                const card = document.createElement('div');
                card.className = 'aviso-card';
                
                const fechaParts = aviso.fecha_cita.split('-');
                const fechaFormatted = fechaParts[2] + '/' + fechaParts[1] + '/' + fechaParts[0];
                
                card.innerHTML = `
                    <div class="aviso-header">
                        <div class="aviso-title">${aviso.titulo}</div>
                        <div class="aviso-badge">Disponible</div>
                    </div>
                    <div class="aviso-details">
                        <div><strong>Fecha:</strong> ${fechaFormatted}</div>
                        <div><strong>Horario:</strong> ${aviso.hora_inicio} - ${aviso.hora_fin}</div>
                        <div><strong>Descripción:</strong> ${aviso.descripcion || 'N/A'}</div>
                    </div>
                    <button class="apply-btn" onclick="openApplicationModal(${aviso.id}, '${aviso.titulo}', '${fechaFormatted}')">
                        <i class="fas fa-hand-paper"></i> Aplicar para esta cita
                    </button>
                `;
                
                container.appendChild(card);
            });
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('avisos-list').innerHTML = `
                <div class="no-avisos">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Error al cargar avisos</h3>
                    <p>${error.message}</p>
                </div>
            `;
        });
}

function openApplicationModal(avisoId, titulo, fecha) {
    currentAvisoId = avisoId;
    document.getElementById('modal-aviso-info').textContent = `Cita: ${titulo} - ${fecha}`;
    document.getElementById('mensaje').value = '';
    document.getElementById('applicationModal').style.display = 'block';
}

// Submit application
document.getElementById('submitApplication').addEventListener('click', function() {
    const mensaje = document.getElementById('mensaje').value;
    
    this.disabled = true;
    this.textContent = 'Enviando...';
    
    fetch('../functions/avisos_aplicaciones.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            aviso_id: currentAvisoId,
            mensaje: mensaje
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('¡Aplicación enviada correctamente! Los administradores la revisarán pronto.');
            document.getElementById('applicationModal').style.display = 'none';
            loadAvisos(); // Reload to show updated status
        } else {
            alert('Error: ' + (result.error || 'Unknown error'));
        }
        this.disabled = false;
        this.textContent = 'Enviar Aplicación';
    })
    .catch(error => {
        alert('Error de conexión: ' + error);
        this.disabled = false;
        this.textContent = 'Enviar Aplicación';
    });
});

// Close modal
document.getElementsByClassName('close')[0].onclick = function() {
    document.getElementById('applicationModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('applicationModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Load on page load
loadAvisos();
</script>

</body>
</html>
