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
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Panel de Voluntarios</title>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        #calendar {
            width: 80%; /* Adjusted width for main content */
            margin: 40px auto;
        }
    </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <h1>Bienvenido, <?php echo $_SESSION['usuario']; ?></h1>

    <div id='calendar'></div>
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
        events: '../functions/get_turnos.php' // Archivo que devuelve los turnos en JSON
    });

    calendar.render();
});
</script>

</body>
</html>
