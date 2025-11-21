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

$mensaje = '';

// Obtener el ID del usuario a eliminar
$usuarioId = $_GET['id'] ?? 0;

// Obtener la información del usuario
$usuario = $userManager->getUserById($usuarioId);

if (!$usuario) {
    $mensaje = '<div class="alert alert-danger">Usuario no encontrado.</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirmar'])) {
        $resultado = $userManager->deleteUser($usuarioId);

        if ($resultado === true) {
            $mensaje = '<div class="alert alert-success">Usuario eliminado con éxito.</div>';
            // Redirigir a la lista de usuarios después de la eliminación
            header("Location: usuarios.php");
            exit();
        } else {
            $mensaje = '<div class="alert alert-danger">Error al eliminar el usuario.</div>';
        }
    } elseif (isset($_POST['cancelar'])) {
        // Redirigir a la lista de usuarios si se cancela la eliminación
        header("Location: usuarios.php");
        exit();
    }
}

require_once '../templates/header.php';
require_once '../templates/menu.php';


?>

<div class="container mt-5">
    <h2>Eliminar Usuario</h2>
    <?php echo $mensaje; ?>
    <?php if ($usuario): ?>
        <p>¿Estás seguro de que deseas eliminar al usuario "<?php echo $usuario['name']; ?>"?</p>
        <form method="post" action="">
            <button type="submit" name="confirmar" class="btn btn-danger">Confirmar</button>
            <button type="submit" name="cancelar" class="btn btn-secondary">Cancelar</button>
        </form>
    <?php endif; ?>
</div>

<?php require_once '../templates/footer.php'; ?>
