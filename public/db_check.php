<?php
require "../config/conexion.php";

echo "<h2>Tables:</h2>";
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    echo $row[0] . "<br>";
    echo "<ul>";
    $cols = $conn->query("DESCRIBE " . $row[0]);
    while ($col = $cols->fetch_assoc()) {
        echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")</li>";
    }
    echo "</ul>";
}
?>
