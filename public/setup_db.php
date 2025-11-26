<?php
require "../config/conexion.php";

$sql = "CREATE TABLE IF NOT EXISTS voluntarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE,
    dni VARCHAR(20),
    telefono VARCHAR(20),
    foto VARCHAR(255),
    anotaciones TEXT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'voluntarios' created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}
?>
