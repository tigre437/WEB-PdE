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

</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <h1>Bienvenido, <?php echo $datos['nombre']; ?> <?php echo $datos['apellidos']; ?></h1>
    <p>Aquí puedes ver tus citas asignadas. Haz clic en una cita para ver detalles o cancelarla.</p>

    <div id='calendar'></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        locale: 'es',
        slotMinTime: "08:00:00",
        slotMaxTime: "22:00:00",
        height: 'auto',
        allDaySlot: false,
        events: '../functions/get_citas.php', // Now using the new endpoint
        eventClick: function(info) {
            handleEventClick(info.event);
        }
    });

    calendar.render();
    window.calendar = calendar;
});

function handleEventClick(event) {
    const props = event.extendedProps;
    let msg = `Cita: ${props.titulo}\n`;
    msg += `Descripción: ${props.descripcion || 'N/A'}\n`;
    msg += `Horario: ${props.hora_inicio} - ${props.hora_fin}\n`;
    
    if(props.tipo === 'semanal') {
        msg += `(Esta es una cita semanal)\n`;
    }
    
    msg += `\n¿Deseas CANCELAR tu asistencia a esta cita?`;
    
    if(confirm(msg)) {
        // Logic to cancel
        // If it's 'unica', we might want to mark it as cancelled or unassign?
        // Requirement: "Los voluntarios deben poder ver sus citas en su panel y cancelarlas."
        // Usually 'cancelar' means they can't go.
        // If it's weekly, we should only cancel THIS instance.
        
        // We will use the 'cancel_instance' action we prepared in citas.php
        // But wait, for 'unica' appointments, should we delete them or just unassign?
        // Or add to cancelled list?
        // Let's treat all cancellations by volunteers as "adding to exception list" or "marking cancelled".
        // For 'unica', if we add to 'citas_canceladas', it won't show up in get_citas.php because of the check.
        // So that works for both!
        
        // We need the specific date of this instance for weekly events.
        // For unique events, the date is fixed.
        
        let dateToCancel = props.instance_date || props.fecha_inicio;
        // instance_date is added by get_citas.php for weekly events.
        // For unique events, it might not be there, so use fecha_inicio.
        
        // Wait, get_citas.php:
        // if unique: events[]... (no instance_date)
        // if weekly: events[]... instance_date = ...
        
        // But for unique, start is the date.
        if (!dateToCancel && event.start) {
             dateToCancel = event.startStr.split('T')[0];
        }

        fetch('../functions/citas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'cancel_instance',
                cita_id: event.id,
                fecha: dateToCancel
            })
        })
        .then(response => response.json())
        .then(result => {
            if(result.success) {
                alert('Cita cancelada correctamente.');
                window.calendar.refetchEvents();
            } else {
                alert('Error al cancelar: ' + result.error);
            }
        });
    }
}
</script>

</body>
</html>
