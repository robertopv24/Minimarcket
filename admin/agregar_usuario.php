<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_name']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../paginas/login.php");
    exit();
}

require_once '../templates/autoload.php';
require_once '../templates/header.php';
require_once '../templates/menu.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $telefono = $_POST['telefono'];
    $documento = $_POST['documento'];
    $direccion = $_POST['direccion'];
    $rol = $_POST['rol'];

    $resultado = $userManager->createUser($nombre, $email, $password, $telefono, $documento, $direccion, $rol);

    if ($resultado === true) {
        $mensaje = '<div class="alert alert-success">Usuario creado con éxito.</div>';
    } else {
        $mensaje = '<div class="alert alert-danger">' . $resultado . '</div>';
    }
}
?>

<div class="container mt-5">
    <h2>Agregar Usuario</h2>
    <?php echo $mensaje; ?>
    <form method="post" action="">
        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="nombre" name="nombre" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Correo Electrónico</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Contraseña</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="mb-3">
            <label for="telefono" class="form-label">Teléfono</label>
            <input type="text" class="form-control" id="telefono" name="telefono">
        </div>
        <div class="mb-3">
            <label for="documento" class="form-label">Documento</label>
            <input type="text" class="form-control" id="documento" name="documento">
        </div>
        <div class="mb-3">
            <label for="direccion" class="form-label">Dirección</label>
            <input type="text" class="form-control" id="direccion" name="direccion">
        </div>
        <div class="mb-3">
            <label for="rol" class="form-label">Rol</label>
            <select class="form-select" id="rol" name="rol">
                <option value="user">Usuario</option>
                <option value="admin">Administrador</option>
            </select>
        </div>
        <button type="submit" class="btn btn-success">Agregar</button>
    </form>
</div>

<?php require_once '../templates/footer.php'; ?>
