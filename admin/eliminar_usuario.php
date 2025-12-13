<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../templates/autoload.php';

use Minimarcket\Core\Container;
use Minimarcket\Modules\User\Services\UserService;
use Minimarcket\Core\Security\CsrfToken;

global $app;
$container = $app->getContainer();
$userService = $container->get(UserService::class);
$csrfToken = $container->get(CsrfToken::class);

$sessionManager = $container->get(\Minimarcket\Core\Session\SessionManager::class);

if (!$sessionManager->isAuthenticated() || $sessionManager->get('user_role') !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

$mensaje = '';
$usuarioId = $_GET['id'] ?? 0;
$usuario = $userService->getUserById($usuarioId);

if (!$usuario) {
    echo '<div class="container mt-5"><div class="alert alert-danger shadow-sm rounded-3">Usuario no encontrado.</div><a href="usuarios.php" class="btn btn-primary rounded-pill px-4">Volver</a></div>';
    exit;
}

// Evitar suicidio digital (Admin borrándose a sí mismo)
if ($usuario['id'] == $sessionManager->get('user_id')) {
    echo '<div class="container mt-5"><div class="alert alert-danger shadow-sm rounded-3">No puedes eliminar tu propia cuenta mientras estás conectado.</div><a href="usuarios.php" class="btn btn-primary rounded-pill px-4">Volver</a></div>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirmar'])) {
        try {
            $csrfToken->validateToken();
            $resultado = $userService->deleteUser($usuarioId);

            if ($resultado === true) {
                header("Location: usuarios.php?msg=deleted");
                exit();
            } else {
                $mensaje = '<div class="alert alert-danger shadow-sm rounded-3"><i class="fa fa-exclamation-circle me-2"></i> Error al eliminar: ' . $resultado . '</div>';
            }
        } catch (Exception $e) {
            $mensaje = '<div class="alert alert-danger shadow-sm rounded-3"><i class="fa fa-exclamation-circle me-2"></i> Error: ' . $e->getMessage() . '</div>';
        }
    } elseif (isset($_POST['cancelar'])) {
        header("Location: usuarios.php");
        exit();
    }
}

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-danger text-white text-center py-4">
                    <h3 class="mb-0 fw-bold"><i class="fa fa-exclamation-triangle me-2"></i> Eliminar Usuario</h3>
                </div>
                <div class="card-body text-center p-5 bg-white">
                    <?= $mensaje; ?>

                    <div class="mb-4">
                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                            style="width: 80px; height: 80px;">
                            <i class="fa fa-user-times fa-3x text-danger opacity-75"></i>
                        </div>
                        <h4 class="text-dark fw-bold">¿Estás seguro?</h4>
                        <p class="text-muted">Estás a punto de eliminar al siguiente usuario:</p>
                    </div>

                    <div
                        class="alert alert-light border shadow-sm d-inline-block text-start px-5 py-3 rounded-3 w-100 mb-4">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fa fa-user text-secondary me-3"></i>
                            <div>
                                <small class="text-muted d-block uppercase fw-bold"
                                    style="font-size: 0.7rem;">Nombre</small>
                                <span class="fw-bold text-dark"><?= htmlspecialchars($usuario['name']) ?></span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="fa fa-envelope text-secondary me-3"></i>
                            <div>
                                <small class="text-muted d-block uppercase fw-bold"
                                    style="font-size: 0.7rem;">Email</small>
                                <span class="text-dark"><?= htmlspecialchars($usuario['email']) ?></span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fa fa-id-badge text-secondary me-3"></i>
                            <div>
                                <small class="text-muted d-block uppercase fw-bold"
                                    style="font-size: 0.7rem;">Rol</small>
                                <span
                                    class="badge bg-secondary text-white rounded-pill px-3"><?= strtoupper($usuario['role']) ?></span>
                            </div>
                        </div>
                    </div>

                    <p
                        class="text-danger small fw-bold bg-danger bg-opacity-10 p-2 rounded-2 border border-danger border-opacity-25">
                        <i class="fa fa-warning me-1"></i> Esta acción no se puede deshacer.
                    </p>

                    <form method="post" action="" class="mt-4 d-grid gap-2 d-md-flex justify-content-center">
                        <?= $csrfToken->insertTokenField(); ?>
                        <button type="submit" name="cancelar"
                            class="btn btn-outline-secondary px-4 rounded-pill">Cancelar</button>
                        <button type="submit" name="confirmar"
                            class="btn btn-danger px-4 rounded-pill shadow-sm fw-bold hover-scale">
                            Sí, Eliminar Definitivamente
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-scale:hover {
        transform: scale(1.05);
        transition: transform 0.2s;
    }
</style>

<?php require_once '../templates/footer.php'; ?>