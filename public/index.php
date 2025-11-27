<?php
session_start();
require "../config/conexion.php";

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] < 1) {
    header("Location: login.php");
    exit;
}

// Obtener id del voluntario
$sql = "SELECT id FROM usuarios WHERE usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['usuario']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$voluntario_id = $user['id'];

$datos_stmt = $conn->prepare("SELECT * FROM voluntarios WHERE usuario_id = ?");
$datos_stmt->bind_param("i", $user['id']);
$datos_stmt->execute();
$result = $datos_stmt->get_result();
$datos = $result->fetch_assoc();

// Get an admin ID to chat with (e.g., the first one found)
$admin_stmt = $conn->prepare("SELECT id FROM usuarios WHERE rol = 2 LIMIT 1");
$admin_stmt->execute();
$admin_res = $admin_stmt->get_result()->fetch_assoc();
$admin_id = $admin_res ? $admin_res['id'] : 0;

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Panel de Voluntarios</title>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/calendar.css">
    <link rel="stylesheet" href="../assets/css/index.css">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <h1>Bienvenido, <?php echo htmlspecialchars($datos['nombre']); ?> <?php echo htmlspecialchars($datos['apellidos']); ?></h1>
    <p>Aquí puedes ver tus citas asignadas. Haz clic en una cita para ver detalles o cancelarla.</p>

    <div id='calendar'></div>
</div>

<!-- Modal Structure -->
<div id="eventModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h2>Detalles de la Cita</h2>
    <div id="modalDetails"></div>
    <button id="cancelBtn">
        Notificar cancelación por WhatsApp
    </button>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        locale: 'es',
        slotMinTime: "08:00:00",
        slotMaxTime: "21:00:00",
        height: 'auto',
        allDaySlot: false,
        events: '../functions/get_citas.php',
        eventClick: function(info) {
            info.jsEvent.preventDefault();
            var props = info.event.extendedProps;
            
            var details = "<strong>Cita:</strong> " + (props.titulo || info.event.title) + "<br>" +
                          "<strong>Descripción:</strong> " + (props.descripcion || 'N/A') + "<br>" +
                          "<strong>Horario:</strong> " + (props.hora_inicio || '') + " - " + (props.hora_fin || '') + "<br>";
            
            if (props.tipo === 'semanal') {
                details += "<strong>Tipo:</strong> Cita semanal<br>";
            }
            
            if (props.vol_nombre) {
                details += "<strong>Voluntario:</strong> " + props.vol_nombre + " " + (props.vol_apellidos || '') + "<br>";
            }
            
            if (props.instance_date) {
                details += "<strong>Fecha de esta instancia:</strong> " + props.instance_date + "<br>";
            } else if (props.fecha_inicio) {
                details += "<strong>Fecha:</strong> " + props.fecha_inicio + "<br>";
            }
            
            document.getElementById('modalDetails').innerHTML = details;
            
            var modal = document.getElementById('eventModal');
            var cancelBtn = document.getElementById('cancelBtn');
            modal.style.display = "block";

            // Store event info for later use
            modal.dataset.citaId = info.event.id;
            modal.dataset.fecha = props.instance_date || props.fecha_inicio;
            modal.dataset.titulo = props.titulo || info.event.title;
            
            // Reset button to cancel state
            cancelBtn.textContent = "Notificar cancelación por WhatsApp";
            cancelBtn.style.backgroundColor = "#ff4d4d";
            cancelBtn.dataset.state = "cancel";
            cancelBtn.disabled = false;
            
            cancelBtn.onclick = function() {
                var citaId = modal.dataset.citaId;
                var fecha = modal.dataset.fecha;
                var titulo = modal.dataset.titulo;
                var fechaParts = fecha.split('-');
                var fechaFormatted = fechaParts[2] + '/' + fechaParts[1] + '/' + fechaParts[0];
                var voluntario = props.vol_nombre ? (props.vol_nombre + ' ' + (props.vol_apellidos || '')) : '<?php echo htmlspecialchars($datos['nombre'] . ' ' . $datos['apellidos']); ?>';
                
                if (cancelBtn.dataset.state === "cancel") {
                    // Confirmation dialog
                    if (!confirm('¿Estás seguro de que quieres cancelar esta cita?')) {
                        return;
                    }
                    
                    // Disable button while processing
                    cancelBtn.disabled = true;
                    cancelBtn.textContent = "Cancelando...";
                    
                    // Call cancel_instance endpoint
                    fetch('../functions/citas.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'cancel_instance',
                            cita_id: citaId,
                            fecha: fecha
                        })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            alert('Cita cancelada correctamente. Se ha creado un aviso para otros voluntarios.');
                            
                            // Refresh calendar
                            calendar.refetchEvents();
                            
                            // Change button to re-confirm
                            cancelBtn.textContent = "Confirmar que puedo ir";
                            cancelBtn.style.backgroundColor = "#28a745";
                            cancelBtn.dataset.state = "reconfirm";
                            cancelBtn.disabled = false;
                            
                            // Open WhatsApp with your custom message
                            var text = "Hola Mati, soy " + voluntario + ". No podré asistir a la cita '" + titulo + "' del " + fechaFormatted + ". Te aviso para que puedas reemplazarme.";
                            var url = "https://wa.me/34722704173?text=" + encodeURIComponent(text);
                            window.open(url, '_blank');
                        } else {
                            alert('Error al cancelar: ' + (result.error || 'Unknown error'));
                            cancelBtn.disabled = false;
                            cancelBtn.textContent = "Notificar cancelación por WhatsApp";
                        }
                    })
                    .catch(error => {
                        alert('Error de conexión: ' + error);
                        cancelBtn.disabled = false;
                        cancelBtn.textContent = "Notificar cancelación por WhatsApp";
                    });
                } else if (cancelBtn.dataset.state === "reconfirm") {
                    // Re-confirm attendance
                    if (!confirm('¿Confirmas que puedes asistir a esta cita?')) {
                        return;
                    }
                    
                    cancelBtn.disabled = true;
                    cancelBtn.textContent = "Confirmando...";
                    
                    fetch('../functions/citas.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'reconfirm_instance',
                            cita_id: citaId,
                            fecha: fecha
                        })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            alert('Cita confirmada correctamente. El aviso ha sido cerrado.');
                            
                            // Refresh calendar
                            calendar.refetchEvents();
                            
                            // Change button back to cancel
                            cancelBtn.textContent = "Notificar cancelación por WhatsApp";
                            cancelBtn.style.backgroundColor = "#ff4d4d";
                            cancelBtn.dataset.state = "cancel";
                            cancelBtn.disabled = false;
                            
                            // Close modal
                            modal.style.display = "none";
                        } else {
                            alert('Error al confirmar: ' + (result.error || 'Unknown error'));
                            cancelBtn.disabled = false;
                            cancelBtn.textContent = "Confirmar que puedo ir";
                        }
                    })
                    .catch(error => {
                        alert('Error de conexión: ' + error);
                        cancelBtn.disabled = false;
                        cancelBtn.textContent = "Confirmar que puedo ir";
                    });
                }
            };
        }
    });

    calendar.render();
    window.calendar = calendar;

    // Close modal logic
    var span = document.getElementsByClassName("close")[0];
    var modal = document.getElementById('eventModal');
    
    span.onclick = function() {
        modal.style.display = "none";
    }
    
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
});
</script>

</body>
</html>
