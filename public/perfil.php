<?php
session_start();
require "../config/conexion.php";

if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2)) {
    header("Location: index.php");
    exit;
}

// Get volunteer data
$sql = "SELECT id FROM usuarios WHERE usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['usuario']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$usuario_id = $user['id'];

$datos_stmt = $conn->prepare("SELECT * FROM voluntarios WHERE usuario_id = ?");
$datos_stmt->bind_param("i", $usuario_id);
$datos_stmt->execute();
$result = $datos_stmt->get_result();
$datos = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/perfil.css">

</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="profile-container">
        <h1 style="color: #e5e7eb; margin-bottom: 30px;"><i class="fas fa-user-circle"></i> Mi Perfil</h1>
        


        <!-- Profile Header Card -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-photo-container" onclick="document.getElementById('photoInput').click()">
                    <div class="profile-photo">
                        <?php if ($datos['foto']): ?>
                            <img src="<?php echo $datos['foto']; ?>" alt="Foto de perfil" id="profileImage">
                        <?php else: ?>
                            <i class="fas fa-user" id="profileIcon"></i>
                        <?php endif; ?>
                    </div>
                    <div class="photo-overlay">
                        <i class="fas fa-camera"></i> Cambiar foto
                    </div>
                </div>
                <input type="file" id="photoInput" style="display: none;" accept="image/*">
                
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($datos['nombre'] . ' ' . $datos['apellidos']); ?></h1>
                    <p><i class="fas fa-user"></i> Voluntario</p>
                    <p><i class="fas fa-id-badge"></i> Usuario: <?php echo htmlspecialchars($_SESSION['usuario']); ?></p>
                </div>
            </div>
        </div>

        <!-- Personal Information Card -->
        <div class="profile-card view-mode" id="infoCard">
            <div class="section-title">
                <i class="fas fa-info-circle"></i>
                <span>Información Personal</span>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <label>Nombre</label>
                    <div class="value" id="nombreValue"><?php echo htmlspecialchars($datos['nombre']); ?></div>
                    <input type="text" id="nombre" value="<?php echo htmlspecialchars($datos['nombre']); ?>">
                </div>

                <div class="info-item">
                    <label>Apellidos</label>
                    <div class="value" id="apellidosValue"><?php echo htmlspecialchars($datos['apellidos']); ?></div>
                    <input type="text" id="apellidos" value="<?php echo htmlspecialchars($datos['apellidos']); ?>">
                </div>

                <div class="info-item">
                    <label>DNI</label>
                    <div class="value" id="dniValue"><?php echo htmlspecialchars($datos['dni'] ?: 'No especificado'); ?></div>
                    <input type="text" id="dni" value="<?php echo htmlspecialchars($datos['dni']); ?>">
                </div>

                <div class="info-item">
                    <label>Teléfono</label>
                    <div class="value" id="telefonoValue"><?php echo htmlspecialchars($datos['telefono'] ?: 'No especificado'); ?></div>
                    <input type="text" id="telefono" value="<?php echo htmlspecialchars($datos['telefono']); ?>">
                </div>

                <div class="info-item">
                    <label>Fecha de Nacimiento</label>
                    <div class="value" id="fechaValue"><?php echo htmlspecialchars($datos['fecha_nacimiento'] ?: 'No especificado'); ?></div>
                    <input type="date" id="fecha_nacimiento" value="<?php echo htmlspecialchars($datos['fecha_nacimiento']); ?>">
                </div>
            </div>

            <div class="btn-group">
                <button class="btn btn-primary" id="editBtn" onclick="enableEdit()">
                    <i class="fas fa-edit"></i> Editar
                </button>
                <button class="btn btn-success" id="saveBtn" style="display: none;" onclick="saveProfile()">
                    <i class="fas fa-save"></i> Guardar
                </button>
                <button class="btn btn-secondary" id="cancelBtn" style="display: none;" onclick="cancelEdit()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            </div>
        </div>

        <!-- Change Password Card -->
        <div class="profile-card">
            <div class="section-title">
                <i class="fas fa-lock"></i>
                <span>Seguridad</span>
            </div>

            <button class="btn btn-primary" onclick="togglePasswordSection()">
                <i class="fas fa-key"></i> Cambiar Contraseña
            </button>

            <div class="password-section" id="passwordSection">
                <div class="password-form">
                    <div class="form-group">
                        <label>Contraseña Actual</label>
                        <div class="password-wrapper">
                            <input type="password" id="currentPassword" placeholder="Ingresa tu contraseña actual">
                            <button type="button" class="toggle-password" onclick="togglePassword('currentPassword', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Nueva Contraseña</label>
                        <div class="password-wrapper">
                            <input type="password" id="newPassword" placeholder="Ingresa tu nueva contraseña">
                            <button type="button" class="toggle-password" onclick="togglePassword('newPassword', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Confirmar Nueva Contraseña</label>
                        <div class="password-wrapper">
                            <input type="password" id="confirmPassword" placeholder="Confirma tu nueva contraseña">
                            <button type="button" class="toggle-password" onclick="togglePassword('confirmPassword', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-success" onclick="changePassword()">
                            <i class="fas fa-check"></i> Cambiar Contraseña
                        </button>
                        <button class="btn btn-secondary" onclick="togglePasswordSection()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <!-- Assigned Appointments Card -->
        <div class="profile-card">
            <div class="section-title">
                <i class="fas fa-calendar-check"></i>
                <span>Mis Citas Asignadas</span>
            </div>

            <ul class="appointments-list" id="appointmentsList">
                <li style="background: #2c2f33; border: none; justify-content: center;">
                    <i class="fas fa-spinner fa-spin"></i> Cargando...
                </li>
            </ul>
        </div>
    </div>
</div>

<script>
const voluntarioId = <?php echo $datos['id']; ?>;

// Load appointments on page load
document.addEventListener('DOMContentLoaded', loadAppointments);

function loadAppointments() {
    fetch('../functions/get_perfil_voluntario.php')
        .then(response => response.json())
        .then(data => {
            const list = document.getElementById('appointmentsList');
            list.innerHTML = '';
            
            if (data.turnos && data.turnos.length > 0) {
                data.turnos.forEach(turno => {
                    const li = document.createElement('li');
                    li.innerHTML = `
                        <div class="appointment-info">
                            <div class="appointment-date">
                                <i class="fas fa-calendar"></i> ${turno.fecha} - ${turno.hora}
                            </div>
                            <div class="appointment-title">
                                <i class="fas fa-clipboard"></i> ${turno.titulo}
                            </div>
                            <div class="appointment-person">
                                <i class="fas fa-user"></i> ${turno.persona}
                            </div>
                        </div>
                    `;
                    list.appendChild(li);
                });
            } else {
                list.innerHTML = '<li style="background: #2c2f33; border: none; justify-content: center;">No tienes citas asignadas</li>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('appointmentsList').innerHTML = 
                '<li style="background: #dc3545; border: none; justify-content: center;">Error al cargar citas</li>';
        });
}

function enableEdit() {
    document.getElementById('infoCard').classList.remove('view-mode');
    document.getElementById('infoCard').classList.add('edit-mode');
    document.getElementById('editBtn').style.display = 'none';
    document.getElementById('saveBtn').style.display = 'inline-flex';
    document.getElementById('cancelBtn').style.display = 'inline-flex';
}

function cancelEdit() {
    document.getElementById('infoCard').classList.remove('edit-mode');
    document.getElementById('infoCard').classList.add('view-mode');
    document.getElementById('editBtn').style.display = 'inline-flex';
    document.getElementById('saveBtn').style.display = 'none';
    document.getElementById('cancelBtn').style.display = 'none';
    
    // Reset values
    document.getElementById('nombre').value = document.getElementById('nombreValue').textContent;
    document.getElementById('apellidos').value = document.getElementById('apellidosValue').textContent;
    document.getElementById('dni').value = document.getElementById('dniValue').textContent === 'No especificado' ? '' : document.getElementById('dniValue').textContent;
    document.getElementById('telefono').value = document.getElementById('telefonoValue').textContent === 'No especificado' ? '' : document.getElementById('telefonoValue').textContent;
    document.getElementById('fecha_nacimiento').value = document.getElementById('fechaValue').textContent === 'No especificado' ? '' : document.getElementById('fechaValue').textContent;
}

function saveProfile() {
    const data = {
        nombre: document.getElementById('nombre').value,
        apellidos: document.getElementById('apellidos').value,
        dni: document.getElementById('dni').value,
        telefono: document.getElementById('telefono').value,
        fecha_nacimiento: document.getElementById('fecha_nacimiento').value
    };

    fetch('../functions/update_perfil_voluntario.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Update display values
            document.getElementById('nombreValue').textContent = data.nombre;
            document.getElementById('apellidosValue').textContent = data.apellidos;
            document.getElementById('dniValue').textContent = data.dni || 'No especificado';
            document.getElementById('telefonoValue').textContent = data.telefono || 'No especificado';
            document.getElementById('fechaValue').textContent = data.fecha_nacimiento || 'No especificado';
            
            cancelEdit();
            showAlert('Perfil actualizado correctamente', 'success', document.querySelector('#infoCard .btn-group'));
        } else {
            showAlert('Error al actualizar perfil: ' + result.error, 'error', document.querySelector('#infoCard .btn-group'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error de conexión', 'error', document.querySelector('#infoCard .btn-group'));
    });
}

function togglePasswordSection() {
    const section = document.getElementById('passwordSection');
    section.classList.toggle('active');
    
    // Clear fields
    document.getElementById('currentPassword').value = '';
    document.getElementById('newPassword').value = '';
    document.getElementById('confirmPassword').value = '';
}

function changePassword() {
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if (!currentPassword || !newPassword || !confirmPassword) {
        showAlert('Por favor completa todos los campos', 'error', document.querySelector('#passwordSection .btn-group'));
        return;
    }

    if (newPassword !== confirmPassword) {
        showAlert('Las contraseñas nuevas no coinciden', 'error', document.querySelector('#passwordSection .btn-group'));
        return;
    }

    if (newPassword.length < 6) {
        showAlert('La contraseña debe tener al menos 6 caracteres', 'error', document.querySelector('#passwordSection .btn-group'));
        return;
    }

    fetch('../functions/cambiar_password.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            current_password: currentPassword,
            new_password: newPassword
        })
    })
    .then(response => {
        // Check if response is OK (status 200-299)
        if (!response.ok) {
            // Try to parse error message from JSON
            return response.json().then(data => {
                throw new Error(data.error || 'Error al cambiar contraseña');
                showAlert('Error al cambiar contraseña', 'error', document.querySelector('#passwordSection .btn-group'));
            }).catch(() => {
                // If JSON parsing fails, throw generic error
                throw new Error('Error al cambiar contraseña (código: ' + response.status + ')');
            });
        }
        return response.json();
    })
    .then(result => {
        if (result.success) {
            togglePasswordSection();
            showAlert('Contraseña cambiada correctamente', 'success', document.querySelector('#passwordSection .btn-group'));
        } else {
            showAlert('Error: ' + (result.error || 'Error desconocido'), 'error', document.querySelector('#passwordSection .btn-group'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert(error.message || 'Error de conexión', 'error', document.querySelector('#passwordSection .btn-group'));
    });
}


// Photo upload
document.getElementById('photoInput').addEventListener('change', function() {
    if (this.files && this.files[0]) {
        const formData = new FormData();
        formData.append('foto', this.files[0]);
        formData.append('id', voluntarioId);

        fetch('../functions/upload_foto.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const img = document.getElementById('profileImage');
                const icon = document.getElementById('profileIcon');
                
                if (img) {
                    img.src = data.path + '?t=' + new Date().getTime();
                } else {
                    const photoDiv = document.querySelector('.profile-photo');
                    photoDiv.innerHTML = `<img src="${data.path}" alt="Foto de perfil" id="profileImage">`;
                }
                
                showAlert('Foto actualizada correctamente', 'success', document.querySelector('.profile-photo-container'));
            } else {
                showAlert('Error al subir foto: ' + data.error, 'error', document.querySelector('.profile-photo-container'));
            }
        })
        .catch(err => {
            console.error(err);
            showAlert('Error de conexión', 'error', document.querySelector('.profile-photo-container'));
        });
    }
});

function showAlert(message, type, targetElement) {
    // Crear alerta dinámica
    const alertBox = document.createElement('div');
    alertBox.className = 'alert alert-' + type + ' show';
    alertBox.textContent = message;

    alertBox.style.marginTop = '10px';
    alertBox.style.marginBottom = '0px';

    // Insertar debajo del elemento indicado
    targetElement.insertAdjacentElement('afterend', alertBox);

    // Ocultar y eliminar después de 5 segundos
    setTimeout(() => {
        alertBox.classList.remove('show');
        alertBox.remove();
    }, 5000);
}

function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    
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
