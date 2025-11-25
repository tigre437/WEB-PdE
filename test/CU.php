<?php
require "../config/conexion.php"; // conexión a la BD

$usuario = "admin";   // nombre del usuario
$password = "admin";        // contraseña en texto plano
$rol = 1;         // rol

// Crear hash
$hash = password_hash($password, PASSWORD_DEFAULT);

// Insertar en la base de datos
$sql = "INSERT INTO usuarios (usuario, password, rol) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $usuario, $hash, $rol);

if ($stmt->execute()) {
    echo "Usuario creado correctamente.";
} else {
    echo "Error: " . $stmt->error;
}
