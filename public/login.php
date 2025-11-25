<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>

<form action="validar.php" method="POST">
    <input type="text" name="usuario" placeholder="Usuario" required>
    <br>
    <input type="password" name="password" placeholder="Contraseña" required>
    <br>
    <button type="submit">Entrar</button>
</form>

</body>
</html>
