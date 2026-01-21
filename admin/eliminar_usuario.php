<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../templates/autoload.php';

session_start();
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

$mensaje = '';
$usuarioId = $_GET['id'] ?? 0;
$usuario = $userManager->getUserById($usuarioId);

if (!$usuario) {
    echo '<div class="container mt-5"><div class="alert alert-danger">Usuario no encontrado.</div><a href="usuarios.php" class="btn btn-primary">Volver</a></div>';
    exit;
}

// Evitar suicidio digital (Admin borrándose a sí mismo)
if ($usuario['id'] == $_SESSION['user_id']) {
    echo '<div class="container mt-5"><div class="alert alert-danger">No puedes eliminar tu propia cuenta mientras estás conectado.</div><a href="usuarios.php" class="btn btn-primary">Volver</a></div>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirmar'])) {
        $resultado = $userManager->deleteUser($usuarioId);

        if ($resultado === true) {
            header("Location: usuarios.php?msg=deleted");
            exit();
        } else {
            $mensaje = '<div class="alert alert-danger">Error al eliminar: ' . $resultado . '</div>';
        }
    } elseif (isset($_POST['cancelar'])) {
        header("Location: usuarios.php");
        exit();
    }
}

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-danger shadow">
                <div class="card-header bg-danger text-white text-center">
                    <h3 class="mb-0"><i class="fa fa-exclamation-triangle"></i> Eliminar Usuario</h3>
                </div>
                <div class="card-body text-center">
                    <?= $mensaje; ?>

                    <p class="lead">¿Estás seguro de que deseas eliminar al siguiente usuario?</p>

                    <div class="alert alert-secondary d-inline-block text-start px-5">
                        <p class="mb-1"><strong>Nombre:</strong> <?= htmlspecialchars($usuario['name']) ?></p>
                        <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($usuario['email']) ?></p>
                        <p class="mb-0"><strong>Rol:</strong> <?= strtoupper($usuario['role']) ?></p>
                    </div>

                    <p class="text-danger mt-3 small">
                        Esta acción no se puede deshacer. El usuario perderá acceso al sistema inmediatamente.
                    </p>

                    <form method="post" action="" class="mt-4">
                        <button type="submit" name="cancelar" class="btn btn-secondary me-2">Cancelar</button>
                        <button type="submit" name="confirmar" class="btn btn-danger px-4">Sí, Eliminar Definitivamente</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>
