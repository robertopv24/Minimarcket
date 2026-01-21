<?php
session_start();
// Production settings applied via autoload or server config
require_once '../templates/autoload.php';
require_once '../funciones/Csrf.php';

// Inicializar Rate Limiter
// 5 intentos máximo, ventana de 5 minutos (300s), bloqueo de 15 minutos (900s)
$rateLimiter = new RateLimiter(5, 300, 900);

// Limpiar archivos antiguos ocasionalmente (1% de probabilidad)
if (rand(1, 100) === 1) {
    $rateLimiter->cleanup();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
        $error = "Sesión inválida (CSRF), recarga la página.";
    } else {
        // Verificar rate limit ANTES de procesar login
        $limitCheck = $rateLimiter->check('login');

        if (!$limitCheck['allowed']) {
            $error = $limitCheck['message'];
        } else {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $user = $userManager->login($email, $password);

            if ($user) {
                // Login exitoso - resetear contador
                $rateLimiter->reset('login');

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

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
        } // End Rate Limit Check
    } // End CSRF check block
} // End POST block

// Si se generó algún error durante el proceso POST, lo pasamos a flash para mostrarlo
if (!empty($error)) {
    // Si no está la clase cargada (raro pq autoload), asegurar
    if (class_exists('SessionHelper')) {
        SessionHelper::setFlash('error', $error);
    }
}

require_once '../templates/header.php';

require_once '../templates/menu.php';




?>






<!-- Sign Up Start -->
<div class="container-fluid">
    <div class="row h-100 align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="col-12 col-sm-8 col-md-6 col-lg-5 col-xl-4">
            <div class="bg-secondary rounded p-4 p-sm-10 my-4 mx-3">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <a href="index.html" class="">
                        <h3 class="text-primary"><i class="fa fa-user-edit me-2"></i></h3>
                    </a>
                    <h3>Iniciar sesión</h3>
                </div>
                <form method="post" action="">
                    <?= Csrf::insertTokenField(); ?>
                    <div class="form-floating mb-3">
                        <input type="email" name="email" required class="form-control" id="floatingInput"
                            placeholder="name@example.com">
                        <label for="floatingInput">Email address</label>
                    </div>
                    <div class="form-floating mb-4">
                        <input type="password" name="password" required class="form-control" id="floatingPassword"
                            placeholder="Password">
                        <label for="floatingPassword">Password</label>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="exampleCheck1">
                            <label class="form-check-label" for="exampleCheck1">Check me out</label>
                        </div>
                        <a href="">Forgot Password</a>
                    </div>
                    <button type="submit" class="btn btn-primary py-3 w-100 mb-4">Iniciar Sesión</button>
                    <!-- <p class="text-center mb-0">Don't have an Account? <a href="register.php">Sign Up</a></p> -->
            </div>
            </form>
        </div>
    </div>
</div>
<!-- Sign Up End -->


<?php
require_once '../templates/footer.php';
?>