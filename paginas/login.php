<?php
// session_start();
require_once '../templates/autoload.php';

use Minimarcket\Core\Container;
use Minimarcket\Core\Security\CsrfToken;
use Minimarcket\Core\Security\RateLimiterService;
use Minimarcket\Modules\User\Services\UserService;

$container = Container::getInstance();
$csrfToken = $container->get(CsrfToken::class);
$userService = $container->get(UserService::class);
// RateLimiterService might not be registered in Container yet if I didn't add it to definitions,
// but Container auto-resolves if it has no complex dependencies (it has params with defaults).
// However, to be safe and allow DI to work if registered, I'll try get().
// If RateLimiterService constructor params need custom values, better to register it or instantiate manually here if not in container config.
// Since I haven't edited Container definitions, I'll instantiate manually to pass custom params if needed, or rely on auto-wiring if defaults are fine.
// Defaults are 5, 300, 900. Matches logic.
$rateLimiter = new RateLimiterService(5, 300, 900);

// Limpiar archivos antiguos ocasionalmente (1% de probabilidad)
if (rand(1, 100) === 1) {
    $rateLimiter->cleanup();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $csrfToken->validateToken();

        // Verificar rate limit ANTES de procesar login
        $limitCheck = $rateLimiter->check('login');

        if (!$limitCheck['allowed']) {
            $error = $limitCheck['message'];
        } else {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $user = $userService->login($email, $password);

            if ($user) {
                // Login exitoso - resetear contador
                $rateLimiter->reset('login');

                // UserService::login already sets SESSION variables in my previous check (legacy support),
                // but explicit setting here is fine too if UserService didn't.
                // Looking at UserService::login earlier, it DOES set $_SESSION.

                // Redirigir según rol
                if ($user['role'] === 'admin') {
                    header('Location: ../admin/index.php');
                } else {
                    header('Location: tienda.php');
                }
                exit;
            } else {
                // Login fallido - registrar intento
                $rateLimiter->hit('login');

                // Obtener intentos restantes
                $newCheck = $rateLimiter->check('login');
                $remaining = $newCheck['remaining'];

                if ($remaining > 0) {
                    $error = "Credenciales incorrectas. Intentos restantes: $remaining";
                } else {
                    $error = "Demasiados intentos fallidos. Cuenta bloqueada temporalmente.";
                }
            }
        }
    } catch (Exception $e) {
        $error = "Error de seguridad: " . $e->getMessage();
    }
}

require_once '../templates/header.php';
// Menu is usually NOT shown on login page, but checking original code... 
// Line 69: require_once '../templates/menu.php';
// If the menu checks session and handles 'not logged in', it's fine. 
// But often login page should not have the main app menu.
// However, to preserve functionality, I'll keep it.
require_once '../templates/menu.php';
?>

<div class="container-fluid bg-light min-vh-100 d-flex align-items-center justify-content-center">
    <div class="col-12 col-sm-8 col-md-6 col-lg-4 col-xl-3">
        <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
            <div class="card-body p-4 p-sm-5">
                <div class="text-center mb-4">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow"
                        style="width: 60px; height: 60px;">
                        <i class="fa fa-user fa-2x"></i>
                    </div>
                    <h3 class="fw-bold text-dark">Iniciar Sesión</h3>
                    <p class="text-muted small">Bienvenido de nuevo</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger shadow-sm py-2 text-center small rounded-3 mb-4">
                        <i class="fa fa-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="">
                    <?= $csrfToken->insertTokenField(); ?>
                    <div class="form-floating mb-3">
                        <input type="email" name="email" required class="form-control rounded-3 border-0 bg-light"
                            id="floatingInput" placeholder="name@example.com">
                        <label for="floatingInput" class="text-muted">Correo Electrónico</label>
                    </div>
                    <div class="form-floating mb-4">
                        <input type="password" name="password" required class="form-control rounded-3 border-0 bg-light"
                            id="floatingPassword" placeholder="Password">
                        <label for="floatingPassword" class="text-muted">Contraseña</label>
                    </div>

                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="rememberCheck">
                            <label class="form-check-label small text-muted" for="rememberCheck">Recordarme</label>
                        </div>
                        <a href="password_reset.php" class="small text-primary text-decoration-none fw-bold">¿Olvidaste
                            tu contraseña?</a>
                    </div>

                    <button type="submit"
                        class="btn btn-primary py-3 w-100 mb-4 rounded-pill shadow-sm fw-bold hover-float">Ingresar</button>

                    <p class="text-center mb-0 small text-muted">¿No tienes cuenta? <a href="register.php"
                            class="text-primary text-decoration-none fw-bold">Regístrate</a></p>
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