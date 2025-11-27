<?php
session_start();
require "../config/conexion.php";

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] < 2) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Gestionar Avisos - Panel de Administración</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin_avisos.css">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="admin-container">
        <h1><i class="fas fa-tasks"></i> Gestionar Avisos y Aplicaciones</h1>
        <p>Revisa y aprueba las aplicaciones de voluntarios para citas canceladas.</p>
        
        <div id="avisos-container">
            <p style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Cargando avisos...</p>
        </div>
    </div>
</div>

<script>
function loadAvisos() {
    fetch('../functions/avisos.php')
        .then(response => response.json())
        .then(avisos => {
            const container = document.getElementById('avisos-container');
            
            if (avisos.length === 0) {
                container.innerHTML = `
                    <div class="no-data">
                        <i class="fas fa-check-circle"></i>
                        <h3>No hay avisos activos</h3>
                        <p>No hay citas canceladas pendientes de asignación.</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = '';
            
            // Load applications for each aviso
            avisos.forEach(aviso => {
                loadAvisoWithApplications(aviso);
            });
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('avisos-container').innerHTML = `
                <div class="no-data">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Error al cargar avisos</h3>
                    <p>${error.message}</p>
                </div>
            `;
        });
}

function loadAvisoWithApplications(aviso) {
    fetch(`../functions/avisos_aplicaciones.php?aviso_id=${aviso.id}`)
        .then(response => response.json())
        .then(applications => {
            const section = document.createElement('div');
            section.className = 'aviso-section';
            section.id = `aviso-${aviso.id}`;
            
            const fechaParts = aviso.fecha_cita.split('-');
            const fechaFormatted = fechaParts[2] + '/' + fechaParts[1] + '/' + fechaParts[0];
            
            let applicationsHTML = '';
            if (applications.length === 0) {
                applicationsHTML = '<div class="no-data"><i class="fas fa-info-circle"></i> No hay aplicaciones para este aviso</div>';
            } else {
                applications.forEach(app => {
                    const statusClass = app.estado;
                    const statusText = app.estado.charAt(0).toUpperCase() + app.estado.slice(1);
                    
                    applicationsHTML += `
                        <div class="application-card ${app.estado}">
                            <div class="application-header">
                                <div class="volunteer-name">
                                    <i class="fas fa-user"></i> ${app.nombre} ${app.apellidos}
                                </div>
                                <span class="status-badge ${statusClass}">${statusText}</span>
                            </div>
                            <div><strong>Usuario:</strong> ${app.usuario}</div>
                            <div><strong>Teléfono:</strong> ${app.telefono || 'N/A'}</div>
                            <div><strong>Fecha de aplicación:</strong> ${new Date(app.created_at).toLocaleString('es-ES')}</div>
                            ${app.mensaje ? `<div class="application-message">"${app.mensaje}"</div>` : ''}
                            ${app.estado === 'pendiente' ? `
                                <div class="action-buttons">
                                    <button class="btn btn-approve" onclick="handleApplication(${app.id}, 'aprobado', ${aviso.id})">
                                        <i class="fas fa-check"></i> Aprobar
                                    </button>
                                    <button class="btn btn-reject" onclick="handleApplication(${app.id}, 'rechazado', ${aviso.id})">
                                        <i class="fas fa-times"></i> Rechazar
                                    </button>
                                </div>
                            ` : ''}
                        </div>
                    `;
                });
            }
            
            section.innerHTML = `
                <div class="aviso-header">
                    <div class="aviso-title">${aviso.titulo}</div>
                    <span class="status-badge ${aviso.estado}">${aviso.estado.toUpperCase()}</span>
                </div>
                <div class="aviso-info">
                    <div class="info-item">
                        <strong>Fecha</strong>
                        ${fechaFormatted}
                    </div>
                    <div class="info-item">
                        <strong>Horario</strong>
                        ${aviso.hora_inicio} - ${aviso.hora_fin}
                    </div>
                    <div class="info-item">
                        <strong>Cancelado por</strong>
                        ${aviso.nombre} ${aviso.apellidos}
                    </div>
                    <div class="info-item">
                        <strong>Aplicaciones</strong>
                        ${applications.length}
                    </div>
                </div>
                <div class="applications-list">
                    <h3><i class="fas fa-users"></i> Aplicaciones:</h3>
                    ${applicationsHTML}
                </div>
            `;
            
            document.getElementById('avisos-container').appendChild(section);
        });
}

function handleApplication(applicationId, estado, avisoId) {
    if (!confirm(`¿Estás seguro de ${estado === 'aprobado' ? 'aprobar' : 'rechazar'} esta aplicación?`)) {
        return;
    }
    
    fetch('../functions/avisos_aplicaciones.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id: applicationId,
            estado: estado
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(estado === 'aprobado' ? 
                '¡Aplicación aprobada! El voluntario ha sido asignado a la cita.' : 
                'Aplicación rechazada.'
            );
            loadAvisos(); // Reload all
        } else {
            alert('Error: ' + (result.error || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Error de conexión: ' + error);
    });
}

// Load on page load
loadAvisos();
</script>

</body>
</html>
