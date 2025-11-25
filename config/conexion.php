<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "pde";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error: " . $conn->connect_error);
}
