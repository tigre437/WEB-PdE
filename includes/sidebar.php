<?php
// Asegurarse de que la sesión esté iniciada (ya debería estarlo en index.php, pero por seguridad)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$rol = $_SESSION['rol'] ?? 0;
?>

<div class="sidebar">
    <h2>Panel de Control</h2>
    <ul>
        <?php if ($rol == 1): // Voluntarios ?>
            <li><a href="index.php"><i class="fas fa-home"></i> Inicio / Mi Panel</a></li>
            <li><a href="mis_citas.php"><i class="fas fa-calendar-check"></i> Mis Citas</a></li>
            <li><a href="mensajes.php"><i class="fas fa-envelope"></i> Mensajes</a></li>
            <li><a href="perfil.php"><i class="fas fa-user"></i> Perfil</a></li>
            <li><a href="documentos.php"><i class="fas fa-file-alt"></i> Documentos</a></li>
        <?php elseif ($rol == 2): // Responsable ?>
            <li><a href="index.php"><i class="fas fa-home"></i> Inicio</a></li>
            <li><a href="agenda_general.php"><i class="fas fa-calendar-alt"></i> Agenda General</a></li>
            <li><a href="voluntarios.php"><i class="fas fa-users"></i> Voluntarios</a></li>
            <li><a href="citas_pendientes.php"><i class="fas fa-clock"></i> Citas Pendientes</a></li>
            <li><a href="mensajes_difusion.php"><i class="fas fa-bullhorn"></i> Mensajes / Difusión</a></li>
            <li><a href="configuracion.php"><i class="fas fa-cog"></i> Configuración</a></li>
        <?php endif; ?>
        
        <!-- Opción común para salir -->
        <li><a href="logout.php" style="margin-top: 20px; border-top: 1px solid #4f5962;"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
    </ul>
</div>
