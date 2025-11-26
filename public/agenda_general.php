<?php
session_start();
require "../config/conexion.php";

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 2) {
    header("Location: index.php");
    exit;
}

// Get volunteers for select list
$vol_sql = "SELECT id, nombre, apellidos FROM voluntarios ORDER BY nombre";
$vol_res = $conn->query($vol_sql);
$volunteers = [];
while ($row = $vol_res->fetch_assoc()) {
    $volunteers[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agenda General</title>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/calendar.css">
    <link rel="stylesheet" href="../assets/css/agenda.css">

</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0 5%;">
        <h1>Agenda General</h1>
        <button class="btn btn-primary" onclick="openCreateModal()"><i class="fas fa-plus"></i> Nueva Cita</button>
    </div>

    <div id='calendar'></div>
</div>

<!-- Create/Edit Modal -->
<div id="citaModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('citaModal')">&times;</span>
        <h2 id="modalTitle">Nueva Cita</h2>
        <form id="citaForm">
            <input type="hidden" id="citaId" name="id">
            <div class="form-group">
                <label>Título</label>
                <input type="text" id="titulo" name="titulo" required>
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>Voluntario</label>
                <select id="voluntario_id" name="voluntario_id">
                    <option value="">-- Sin asignar --</option>
                    <?php foreach ($volunteers as $vol): ?>
                        <option value="<?php echo $vol['id']; ?>"><?php echo $vol['nombre'] . ' ' . $vol['apellidos']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Fecha Inicio</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio" required>
            </div>
            <div class="form-group" style="display: flex; gap: 10px;">
                <div style="flex: 1;">
                    <label>Hora Inicio</label>
                    <input type="time" id="hora_inicio" name="hora_inicio" required>
                </div>
                <div style="flex: 1;">
                    <label>Hora Fin</label>
                    <input type="time" id="hora_fin" name="hora_fin" required>
                </div>
            </div>
            <div class="form-group">
                <label>Tipo</label>
                <select id="tipo" name="tipo" onchange="toggleRepeatInfo()">
                    <option value="unica">Única</option>
                    <option value="semanal">Semanal</option>
                </select>
            </div>
            <div id="repeatInfo" style="display: none; font-size: 0.9em; color: #666; margin-bottom: 10px;">
                <i class="fas fa-info-circle"></i> Se repetirá cada semana el mismo día.
            </div>
            
            <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                <button type="button" class="btn btn-danger" id="deleteBtn" style="display: none;" onclick="deleteCita()">Eliminar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        locale: 'es',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        slotMinTime: "08:00:00",
        slotMaxTime: "22:00:00",
        height: 'auto',
        allDaySlot: false,
        events: '../functions/get_citas.php',
        eventClick: function(info) {
            openEditModal(info.event);
        },
        dateClick: function(info) {
            openCreateModal(info.dateStr);
        }
    });
    calendar.render();
    window.calendar = calendar;
});

function openCreateModal(dateStr) {
    document.getElementById('citaForm').reset();
    document.getElementById('citaId').value = '';
    document.getElementById('modalTitle').innerText = 'Nueva Cita';
    document.getElementById('deleteBtn').style.display = 'none';
    
    if(dateStr) {
        // If dateStr has time (T), split it
        if(dateStr.includes('T')) {
            const parts = dateStr.split('T');
            document.getElementById('fecha_inicio').value = parts[0];
            document.getElementById('hora_inicio').value = parts[1].substring(0, 5);
            // Default 1 hour duration
            let h = parseInt(parts[1].substring(0, 2)) + 1;
            let hStr = h < 10 ? '0'+h : h;
            document.getElementById('hora_fin').value = hStr + parts[1].substring(2, 5);
        } else {
            document.getElementById('fecha_inicio').value = dateStr;
        }
    }
    
    document.getElementById('citaModal').style.display = 'block';
}

function openEditModal(event) {
    const props = event.extendedProps;
    document.getElementById('citaId').value = props.id;
    document.getElementById('titulo').value = props.titulo;
    document.getElementById('descripcion').value = props.descripcion;
    document.getElementById('voluntario_id').value = props.voluntario_id || '';
    
    // For editing, we use the original start date of the series if it's weekly, 
    // OR should we allow changing just this instance?
    // Requirement says "Crear, editar y eliminar citas". 
    // Usually editing a recurring series edits the whole series.
    // Let's stick to editing the main record.
    
    document.getElementById('fecha_inicio').value = props.fecha_inicio;
    document.getElementById('hora_inicio').value = props.hora_inicio;
    document.getElementById('hora_fin').value = props.hora_fin;
    document.getElementById('tipo').value = props.tipo;
    
    document.getElementById('modalTitle').innerText = 'Editar Cita';
    document.getElementById('deleteBtn').style.display = 'block';
    document.getElementById('citaModal').style.display = 'block';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

function toggleRepeatInfo() {
    const tipo = document.getElementById('tipo').value;
    document.getElementById('repeatInfo').style.display = tipo === 'semanal' ? 'block' : 'none';
}

document.getElementById('citaForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('citaId').value;
    const method = id ? 'PUT' : 'POST';
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    fetch('../functions/citas.php', {
        method: method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if(result.success) {
            closeModal('citaModal');
            window.calendar.refetchEvents();
        } else {
            alert('Error: ' + result.error);
        }
    });
});

function deleteCita() {
    const id = document.getElementById('citaId').value;
    if(confirm('¿Estás seguro de eliminar esta cita? Si es semanal, se eliminarán todas las repeticiones.')) {
        fetch(`../functions/citas.php?id=${id}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(result => {
            if(result.success) {
                closeModal('citaModal');
                window.calendar.refetchEvents();
            } else {
                alert('Error: ' + result.error);
            }
        });
    }
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = "none";
    }
}
</script>
</body>
</html>
