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

// Obtener el ID del usuario a editar
$usuarioId = $_GET['id'] ?? 0;

// Obtener la información del usuario
$usuario = $userManager->getUserById($usuarioId);

if (!$usuario) {
    $mensaje = '<div class="alert alert-danger">Usuario no encontrado.</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $documento = $_POST['documento'];
    $direccion = $_POST['direccion'];
    $rol = $_POST['rol'];

    $resultado = $userManager->updateUserProfile($usuarioId, $nombre, $email, $telefono, $documento, $direccion);

    if ($resultado === true) {
        $mensaje = '<div class="alert alert-success">Usuario actualizado con éxito.</div>';
        // Volver a obtener la información actualizada del usuario
        $usuario = $userManager->getUserById($usuarioId);
    } else {
        $mensaje = '<div class="alert alert-danger">' . $resultado . '</div>';
    }
}
?>

<div class="container mt-5">
    <h2>Editar Usuario</h2>
    <?php echo $mensaje; ?>
    <?php if ($usuario): ?>
        <form method="post" action="">
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre</label>
                <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo $usuario['name']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Correo Electrónico</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo $usuario['email']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" class="form-control" id="telefono" name="telefono" value="<?php echo $usuario['phone']; ?>">
            </div>
            <div class="mb-3">
                <label for="documento" class="form-label">Documento</label>
                <input type="text" class="form-control" id="documento" name="documento" value="<?php echo $usuario['document_id']; ?>">
            </div>
            <div class="mb-3">
                <label for="direccion" class="form-label">Dirección</label>
                <input type="text" class="form-control" id="direccion" name="direccion" value="<?php echo $usuario['address']; ?>">
            </div>
            <div class="mb-3">
                <label for="rol" class="form-label">Rol</label>
                <select class="form-select" id="rol" name="rol">
                    <option value="user" <?php echo ($usuario['role'] === 'user') ? 'selected' : ''; ?>>Usuario</option>
                    <option value="admin" <?php echo ($usuario['role'] === 'admin') ? 'selected' : ''; ?>>Administrador</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </form>
    <?php endif; ?>
</div>

<?php require_once '../templates/footer.php'; ?>
