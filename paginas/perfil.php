<?php
session_start();

require_once '../templates/autoload.php';

use Minimarcket\Core\Container;
use Minimarcket\Modules\User\Services\UserService;
use Minimarcket\Core\Security\CsrfToken;

$container = Container::getInstance();
$userService = $container->get(UserService::class);
$csrfToken = $container->get(CsrfToken::class);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user = $userService->getUserById($_SESSION['user_id']);
if (!$user) {
    header('Location: logout.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $csrfToken->validateToken();

        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $document_id = $_POST['document_id'] ?? '';
        $address = $_POST['address'] ?? '';

        // Update basic profile
        // Note: updateUserProfile requires all params: id, name, email, phone, document, address
        $result = $userService->updateUserProfile($user['id'], $name, $email, $phone, $document_id, $address);

        if ($result === true) {
            $message = '<div class="alert alert-success shadow-sm rounded-3"><i class="fa fa-check-circle me-2"></i> Perfil actualizado correctamente.</div>';
            // Refresh user data
            $user = $userService->getUserById($user['id']);
            $_SESSION['user_name'] = $user['name']; // Update session
        } else {
            $message = '<div class="alert alert-danger shadow-sm rounded-3"><i class="fa fa-exclamation-circle me-2"></i> ' . $result . '</div>';
        }
    } catch (Exception $e) {
        $message = '<div class="alert alert-danger shadow-sm rounded-3"><i class="fa fa-exclamation-circle me-2"></i> Error: ' . $e->getMessage() . '</div>';
    }
}

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div
                    class="card-header bg-primary text-white py-3 px-4 d-flex justify-content-between align-items-center">
                    <h3 class="mb-0 fw-bold"><i class="fa fa-user-circle me-2"></i> Mi Perfil</h3>
                    <span
                        class="badge bg-white text-primary rounded-pill px-3 py-2 fw-bold text-uppercase"><?= htmlspecialchars($user['role'] ?? 'user') ?></span>
                </div>
                <div class="card-body p-4 p-md-5 bg-white">
                    <?= $message ?>

                    <div class="row mb-5 align-items-center">
                        <div class="col-auto">
                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center shadow-inner"
                                style="width: 100px; height: 100px;">
                                <i class="fa fa-user fa-4x text-muted"></i>
                            </div>
                        </div>
                        <div class="col">
                            <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($user['name']) ?></h4>
                            <p class="text-muted mb-0"><?= htmlspecialchars($user['email']) ?></p>
                            <p class="text-muted small mb-0"><i class="fa fa-phone me-1"></i>
                                <?= htmlspecialchars($user['phone'] ?? 'Sin teléfono') ?></p>
                        </div>
                    </div>

                    <form action="" method="POST">
                        <?= $csrfToken->insertTokenField(); ?>

                        <h5 class="text-primary fw-bold mb-4 border-bottom pb-2">Información Personal</h5>

                        <div class="row g-4 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary">Nombre Completo</label>
                                <input type="text" name="name" class="form-control bg-light border-0"
                                    value="<?= htmlspecialchars($user['name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary">Correo Electrónico</label>
                                <input type="email" name="email" class="form-control bg-light border-0"
                                    value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                        </div>

                        <div class="row g-4 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary">Teléfono</label>
                                <input type="text" name="phone" class="form-control bg-light border-0"
                                    value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary">Documento ID</label>
                                <input type="text" name="document_id" class="form-control bg-light border-0"
                                    value="<?= htmlspecialchars($user['document_id'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-5">
                            <label class="form-label fw-bold text-secondary">Dirección</label>
                            <textarea name="address" class="form-control bg-light border-0"
                                rows="2"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>

                        <div class="d-grid gap-3 d-md-flex justify-content-md-end">
                            <a href="tienda.php" class="btn btn-outline-secondary px-4 rounded-pill">Volver</a>
                            <button type="submit" class="btn btn-primary btn-lg px-5 rounded-pill shadow hover-float">
                                <i class="fa fa-save me-2"></i> Actualizar Perfil
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-float:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.2s;
    }

    .form-control:focus {
        box-shadow: none;
        border-color: var(--bs-primary);
        background-color: #fff;
    }

    .shadow-inner {
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.06);
    }
</style>

<?php require_once '../templates/footer.php'; ?>