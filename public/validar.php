<?php
session_start();
require "../config/conexion.php";

// Verificar que el formulario fue enviado
if (isset($_POST['usuario']) && isset($_POST['password'])) {
    $usuario  = $_POST['usuario'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM usuarios WHERE usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            $_SESSION['usuario'] = $row['usuario'];
            $_SESSION['rol']     = $row['rol'];

            header("Location: index.php");
            exit;
        }
    }

    header("Location: login.php?error=1");
    exit;

} else {
    // Si alguien entra directamente a validar.php sin enviar el formulario
    header("Location: login.php");
    exit;
}
