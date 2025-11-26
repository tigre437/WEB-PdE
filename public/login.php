<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Panel de Voluntarios</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body class="login-page">

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <i class="fas fa-hands-helping"></i>
                </div>
                <h1>Bienvenido</h1>
                <p>Panel de Gestión de Voluntarios</p>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Credenciales incorrectas. Por favor, inténtalo de nuevo.</span>
                </div>
            <?php endif; ?>

            <form action="validar.php" method="POST" id="loginForm">
                <div class="form-group">
                    <label for="usuario">Usuario</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input 
                            type="text" 
                            id="usuario" 
                            name="usuario" 
                            class="form-control" 
                            placeholder="Introduce tu usuario" 
                            required
                            autocomplete="username"
                        >
                        
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control password-input" 
                            placeholder="Introduce tu contraseña" 
                            required
                            autocomplete="current-password"
                        >
                        <i class="fas fa-lock input-icon"></i>
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="forgot-password">
                    <a href="#" id="forgotPasswordLink">
                        <i class="fas fa-question-circle"></i>
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>

                <button type="submit" class="btn-login">
                    Iniciar Sesión
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="footer-text">
                © 2025 Panel de Voluntarios
            </div>
        </div>
    </div>

    <!-- Modal for forgot password -->
    <div id="forgotPasswordModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeModal">&times;</span>
            <div class="modal-header">
                <i class="fas fa-info-circle"></i>
                <h2>Recuperar Contraseña</h2>
            </div>
            <div class="modal-body">
                <p>Para recuperar tu contraseña, por favor contacta con el administrador del sistema.</p>
                <p>El administrador podrá restablecer tu contraseña y proporcionarte nuevas credenciales de acceso.</p>
            </div>
            <button class="btn-modal-close" id="closeModalBtn">Entendido</button>
        </div>
    </div>

    <script>
        // Add loading state to button on submit
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = this.querySelector('.btn-login');
            btn.classList.add('loading');
            btn.innerHTML = 'Iniciando sesión...';
        });

        // Auto-focus on first input
        document.getElementById('usuario').focus();

        // Forgot password modal
        const modal = document.getElementById('forgotPasswordModal');
        const forgotLink = document.getElementById('forgotPasswordLink');
        const closeModal = document.getElementById('closeModal');
        const closeModalBtn = document.getElementById('closeModalBtn');

        forgotLink.addEventListener('click', function(e) {
            e.preventDefault();
            modal.style.display = 'block';
        });

        closeModal.addEventListener('click', function() {
            modal.style.display = 'none';
        });

        closeModalBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });

        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });

        function togglePassword() {
            const input = document.getElementById('password');
            const button = document.querySelector('.toggle-password');
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
