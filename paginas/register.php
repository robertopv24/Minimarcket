<?php
session_start();
require_once '../templates/autoload.php';

use Minimarcket\Core\Container;
use Minimarcket\Core\Security\CsrfToken;
use Minimarcket\Core\Security\RateLimiterService;
use Minimarcket\Modules\User\Services\UserService;

$container = Container::getInstance();
$csrfToken = $container->get(CsrfToken::class);
$userService = $container->get(UserService::class);
$rateLimiter = new RateLimiterService(3, 600, 1800);

// Limpiar archivos antiguos ocasionalmente
if (rand(1, 100) === 1) {
    $rateLimiter->cleanup();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $csrfToken->validateToken();

        // Verificar rate limit
        $limitCheck = $rateLimiter->check('register');

        if (!$limitCheck['allowed']) {
            $error = $limitCheck['message'];
        } else {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $document_id = $_POST['document_id'] ?? '';
            $address = $_POST['address'] ?? '';

            // Validaciones
            if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
                $error = 'Todos los campos son obligatorios.';
            } elseif ($password !== $confirmPassword) {
                $error = 'Las contraseñas no coinciden.';
            } elseif (strlen($password) < 6) {
                $error = 'La contraseña debe tener al menos 6 caracteres.';
            } else {
                // Crear usuario
                $result = $userService->createUser($name, $email, $password, $phone, $document_id, $address, 'user');

                if ($result === true) {
                    // Registro exitoso - resetear contador
                    $rateLimiter->reset('register');
                    $success = 'Usuario registrado exitosamente.';

                    // Auto-login después de registro
                    $user = $userService->login($email, $password);
                    if ($user) {
                        header('Location: tienda.php');
                        exit;
                    } else {
                        // Should not happen if creation was successful
                        header('Location: login.php?msg=registered');
                        exit;
                    }
                } else {
                    $rateLimiter->hit('register');
                    $error = $result; // Error message from UserService
                }
            }
        }
    } catch (Exception $e) {
        $rateLimiter->hit('register');
        $error = 'Error de sistema: ' . $e->getMessage();
    }
}

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container-fluid bg-light min-vh-100 d-flex align-items-center justify-content-center py-5">
    <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
        <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
            <div class="card-body p-4 p-sm-5">
                <div class="text-center mb-4">
                    <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow"
                        style="width: 60px; height: 60px;">
                        <i class="fa fa-user-plus fa-2x"></i>
                    </div>
                    <h3 class="fw-bold text-dark">Crear Cuenta</h3>
                    <p class="text-muted small">Únete a nuestra comunidad</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger shadow-sm py-2 text-center small rounded-3 mb-4">
                        <i class="fa fa-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success shadow-sm py-2 text-center small rounded-3 mb-4">
                        <i class="fa fa-check-circle me-1"></i> <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="">
                    <?= $csrfToken->insertTokenField(); ?>

                    <h5 class="text-success fw-bold mb-3 border-bottom pb-2 text-uppercase fs-6">Información Personal
                    </h5>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" name="name" class="form-control rounded-3 border-0 bg-light"
                                    id="floatingName" placeholder="Nombre" required>
                                <label for="floatingName" class="text-muted">Nombre Completo</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" name="document_id" class="form-control rounded-3 border-0 bg-light"
                                    id="floatingID" placeholder="ID" required>
                                <label for="floatingID" class="text-muted">Cédula / ID</label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="email" name="email" class="form-control rounded-3 border-0 bg-light"
                                    id="floatingEmail" placeholder="Email" required>
                                <label for="floatingEmail" class="text-muted">Correo Electrónico</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" name="phone" class="form-control rounded-3 border-0 bg-light"
                                    id="floatingPhone" placeholder="Teléfono" required>
                                <label for="floatingPhone" class="text-muted">Teléfono</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-floating mb-4">
                        <textarea name="address" class="form-control rounded-3 border-0 bg-light"
                            placeholder="Dirección" id="floatingAddress" style="height: 80px;" required></textarea>
                        <label for="floatingAddress" class="text-muted">Dirección de despacho</label>
                    </div>

                    <h5 class="text-primary fw-bold mb-3 border-bottom pb-2 text-uppercase fs-6">Seguridad</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="password" name="password" class="form-control rounded-3 border-0 bg-light"
                                    id="floatingPass" placeholder="Contraseña" required>
                                <label for="floatingPass" class="text-muted">Contraseña</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="password" name="confirm_password"
                                    class="form-control rounded-3 border-0 bg-light" id="floatingConfirm"
                                    placeholder="Confirmar" required>
                                <label for="floatingConfirm" class="text-muted">Confirmar Contraseña</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit"
                        class="btn btn-success py-3 w-100 mb-4 rounded-pill shadow-sm fw-bold hover-float">
                        Registrarse
                    </button>

                    <p class="text-center mb-0 small text-muted">¿Ya tienes cuenta? <a href="login.php"
                            class="text-success text-decoration-none fw-bold">Inicia Sesión</a></p>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-float:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.2s;
    }
</style>

<?php require_once '../templates/footer.php'; ?>