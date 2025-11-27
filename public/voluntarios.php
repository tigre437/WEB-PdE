<?php
session_start();
require "../config/conexion.php";

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 2) {
    header("Location: index.php");
    exit;
}

// Get current admin ID
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $_SESSION['usuario']);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$current_user_id = $res['id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Voluntarios</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/voluntarios.css">
    <link rel="stylesheet" href="../assets/css/tabla_voluntarios.css">

</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1>Gestión de Voluntarios</h1>
        <button class="btn-primary" onclick="openModal('createModal')"><i class="fas fa-plus"></i> Nuevo Voluntario</button>
    </div>

    <table class="vol-table" id="voluntariosTable">
    <thead>
        <tr>
            <th><i class="fas fa-user"></i> Nombre</th>
            <th><i class="fas fa-id-badge"></i> Apellidos</th>
            <th><i class="fas fa-id-card"></i> DNI</th>
            <th><i class="fas fa-phone"></i> Teléfono</th>
            <th><i class="fas fa-user-circle"></i> Fecha de nacimiento</th>
            <th><i class="fas fa-cogs"></i> Acciones</th>
        </tr>
    </thead>
    <tbody>
        <!-- JS inserts rows -->
    </tbody>
</table>
</div>

<!-- Create/Edit Modal -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('createModal')">&times;</span>
        <h2 id="modalTitle">Nuevo Voluntario</h2>
        <form id="volunteerForm">
            <input type="hidden" id="volunteerId" name="id">
            <div class="form-group">
                <label>Usuario (Login)</label>
                <input type="text" id="usuario" name="usuario" required>
            </div>
            <div class="form-group" id="passwordGroup">
                <label>Contraseña</label>
                <input type="password" id="password" name="password">
            </div>
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>
            <div class="form-group">
                <label>Apellidos</label>
                <input type="text" id="apellidos" name="apellidos" required>
            </div>
            <div class="form-group">
                <label>Fecha Nacimiento</label>
                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento">
            </div>
            <div class="form-group">
                <label>DNI</label>
                <input type="text" id="dni" name="dni">
            </div>
            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" id="telefono" name="telefono">
            </div>
            <div class="form-group">
                <label>Anotaciones</label>
                <textarea id="anotaciones" name="anotaciones" rows="3"></textarea>
            </div>
            <button type="submit" class="btn-primary">Guardar</button>
        </form>
    </div>
</div>

<!-- View Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('viewModal')">&times;</span>
        <div class="profile-header">
            <div class="profile-img" onclick="document.getElementById('photoInput').click()" title="Click para cambiar foto" style="cursor: pointer; position: relative; overflow: hidden; width: 150px; height: 150px;">
                <i class="fas fa-user" id="profileIcon"></i>
                <img id="profileImage" src="" alt="Foto Perfil" style="display: none; width: 100%; height: 100%; object-fit: cover;">
                <div class="overlay" style="position: absolute; bottom: 0; background: rgba(0,0,0,0.5); color: white; width: 100%; text-align: center; font-size: 12px; padding: 2px;">Editar</div>
            </div>
            <input type="file" id="photoInput" style="display: none;" accept="image/*">
            <div class="profile-info">
                <h2 id="viewName">Nombre Completo</h2>
                <p><i class="fas fa-id-card"></i> <span id="viewDni"></span></p>
                <p><i class="fas fa-phone"></i> <span id="viewPhone"></span></p>
                <p><i class="fas fa-birthday-cake"></i> <span id="viewDob"></span></p>
            </div>
        </div>
        
        <div class="detail-section">
            <h3><i class="fas fa-sticky-note"></i> Anotaciones</h3>
            <p id="viewNotes" style="color: #555; font-style: italic;"></p>
        </div>

        <div class="detail-section">
            <h3><i class="fas fa-calendar-check"></i> Citas Asignadas</h3>
            <ul class="assignments-list" id="viewAssignments">
                <!-- Assignments loaded here -->
            </ul>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div id="changePasswordModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <span class="close" onclick="closeModal('changePasswordModal')">&times;</span>
        <h2>Cambiar Contraseña</h2>
        <p>Cambiando contraseña para: <strong id="cp_volunteerName"></strong></p>
        <form id="changePasswordForm">
            <input type="hidden" id="cp_volunteerId">
            <div class="form-group">
                <label>Nueva Contraseña</label>
                <div class="input-wrapper">
                    <input type="password" id="cp_newPassword" required minlength="4">
                    <button type="button" class="toggle-password" onclick="togglePassword('cp_newPassword', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label>Confirmar Contraseña</label>
                <div class="input-wrapper">
                    <input type="password" id="cp_confirmPassword" required minlength="4">
                    <button type="button" class="toggle-password" onclick="togglePassword('cp_confirmPassword', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 10px;">Guardar Nueva Contraseña</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', loadVolunteers);

function loadVolunteers() {
    fetch('../functions/voluntarios.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('#voluntariosTable tbody');
            tbody.innerHTML = '';
            data.forEach(vol => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${vol.nombre}</td>
                    <td>${vol.apellidos}</td>
                    <td>${vol.dni}</td>
                    <td>${vol.telefono}</td>
                    <td>${vol.fecha_nacimiento}</td>
                    <td>
                        <i class="fas fa-eye action-btn" onclick="viewVolunteer(${vol.id})" title="Ver"></i>
                        <i class="fas fa-edit action-btn" onclick="editVolunteer(${vol.id}, '${vol.nombre}', '${vol.apellidos}', '${vol.fecha_nacimiento}', '${vol.dni}', '${vol.telefono}', '${vol.anotaciones}', '${vol.usuario}')" title="Editar"></i>
                        <i class="fas fa-key action-btn" onclick="openChangePasswordModal(${vol.id}, '${vol.nombre} ${vol.apellidos}')" title="Cambiar Contraseña"></i>
                        <i class="fas fa-trash action-btn delete" onclick="deleteVolunteer(${vol.id})" title="Borrar"></i>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        });
}

function openModal(id) {
    document.getElementById(id).style.display = 'block';
    if(id === 'createModal') {
        document.getElementById('volunteerForm').reset();
        document.getElementById('volunteerId').value = '';
        document.getElementById('modalTitle').innerText = 'Nuevo Voluntario';
        document.getElementById('passwordGroup').style.display = 'block';
        document.getElementById('usuario').disabled = false;
    }
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

// Close modal if clicked outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = "none";
    }
}

document.getElementById('volunteerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('volunteerId').value;
    const method = id ? 'PUT' : 'POST';
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    fetch('../functions/voluntarios.php', {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if(result.success) {
            closeModal('createModal');
            loadVolunteers();
        } else {
            alert('Error: ' + result.error);
        }
    });
});

function editVolunteer(id, nombre, apellidos, fecha, dni, telefono, anotaciones, usuario) {
    openModal('createModal');
    document.getElementById('modalTitle').innerText = 'Editar Voluntario';
    document.getElementById('volunteerId').value = id;
    document.getElementById('nombre').value = nombre;
    document.getElementById('apellidos').value = apellidos;
    document.getElementById('fecha_nacimiento').value = fecha;
    document.getElementById('dni').value = dni;
    document.getElementById('telefono').value = telefono;
    document.getElementById('anotaciones').value = anotaciones;
    document.getElementById('usuario').value = usuario;
    document.getElementById('usuario').disabled = true; // Cannot change username easily
    document.getElementById('passwordGroup').style.display = 'none'; // Hide password for edit
}

function deleteVolunteer(id) {
    if(confirm('¿Estás seguro de borrar este voluntario?')) {
        fetch(`../functions/voluntarios.php?id=${id}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(result => {
            if(result.success) {
                loadVolunteers();
            } else {
                alert('Error: ' + result.error);
            }
        });
    }
}

function viewVolunteer(id) {
    fetch(`../functions/get_voluntario_detalle.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('viewName').innerText = data.nombre + ' ' + data.apellidos;
            document.getElementById('viewDni').innerText = data.dni || 'N/A';
            document.getElementById('viewPhone').innerText = data.telefono || 'N/A';
            document.getElementById('viewDob').innerText = data.fecha_nacimiento || 'N/A';
            document.getElementById('viewNotes').innerText = data.anotaciones || 'Sin anotaciones';
            
            if(data.foto) {
                document.getElementById('profileImage').src = data.foto;
                document.getElementById('profileImage').style.display = 'block';
                document.getElementById('profileIcon').style.display = 'none';
            } else {
                document.getElementById('profileImage').style.display = 'none';
                document.getElementById('profileIcon').style.display = 'block';
            }

            // Store ID for upload
            document.getElementById('photoInput').dataset.id = id;
            
            const list = document.getElementById('viewAssignments');
            list.innerHTML = '';
            if(data.turnos && data.turnos.length > 0) {
                data.turnos.forEach(turno => {
                    const li = document.createElement('li');
                    li.innerHTML = `<span>${turno.fecha} ${turno.hora}</span> <strong>${turno.persona}</strong>`;
                    list.appendChild(li);
                });
            } else {
                list.innerHTML = '<li>No hay citas asignadas</li>';
            }
            
            document.getElementById('viewModal').style.display = 'block';
        });
}

document.getElementById('photoInput').addEventListener('change', function() {
    if (this.files && this.files[0]) {
        const id = this.dataset.id;
        const formData = new FormData();
        formData.append('foto', this.files[0]);
        formData.append('id', id);

        fetch('../functions/upload_foto.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('profileImage').src = data.path;
                document.getElementById('profileImage').style.display = 'block';
                document.getElementById('profileIcon').style.display = 'none';
                // Optional: Refresh table if photo is shown there too
            } else {
                alert('Error al subir foto: ' + data.error);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error de conexión');
        });
    }
});

// Password Change Logic
function openChangePasswordModal(id, nombre) {
    document.getElementById('changePasswordModal').style.display = 'block';
    document.getElementById('cp_volunteerId').value = id;
    document.getElementById('cp_volunteerName').innerText = nombre;
    document.getElementById('changePasswordForm').reset();
}

document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const newPass = document.getElementById('cp_newPassword').value;
    const confirmPass = document.getElementById('cp_confirmPassword').value;
    
    if (newPass !== confirmPass) {
        alert('Las contraseñas no coinciden');
        return;
    }
    
    const id = document.getElementById('cp_volunteerId').value;
    
    fetch('../functions/admin_cambiar_password.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: id,
            nueva_password: newPass
        })
    })
    .then(response => response.json())
    .then(result => {
        if(result.success) {
            alert('Contraseña actualizada correctamente');
            closeModal('changePasswordModal');
        } else {
            alert('Error: ' + result.error);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error de conexión');
    });
});

function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>


</body>
</html>
