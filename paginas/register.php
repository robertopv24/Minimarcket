<?php
session_start();
require_once '../clases/config.php';
require_once '../funciones/usuarios.php';

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $phone = $_POST['phone'];
    $document_id = $_POST['document_id'];
    $address = $_POST['address'];

    // Call the registerUser function (it's already defined in includes/functions.php)
    if (registerUser($name, $email, $password, $phone, $document_id, $address)) {
        echo "User registered successfully!";
    } else {
        echo "Error registering user.";
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Registro</title>
</head>
<body>
    <h1>Registro</h1>
    <form method="post">
        <label>Nombre:</label>
        <input type="text" name="name" required><br>
        <label>Email:</label>
        <input type="email" name="email" required><br>
        <label>Contraseña:</label>
        <input type="password" name="password" required><br>
        <label>Teléfono:</label>
        <input type="text" name="phone" required><br>
        <label>Documento:</label>
        <input type="text" name="document_id" required><br>
        <label>Dirección:</label>
        <textarea name="address" required></textarea><br>
        <button type="submit">Registrar</button>
    </form>
</body>
</html>
