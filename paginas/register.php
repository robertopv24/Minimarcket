<?php
// Production settings applied
require_once '../templates/autoload.php';

// Inicializar Rate Limiter para registro
// 3 intentos máximo, ventana de 10 minutos (600s), bloqueo de 30 minutos (1800s)
$rateLimiter = new RateLimiter(3, 600, 1800);

// Limpiar archivos antiguos ocasionalmente
if (rand(1, 100) === 1) {
    $rateLimiter->cleanup();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar rate limit
    $limitCheck = $rateLimiter->check('register');

    if (!$limitCheck['allowed']) {
        $error = $limitCheck['message'];
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        // The original form had phone, document_id, and address, but the new PHP logic doesn't use them for user creation.
        // Keeping them here for consistency with the form, though they are not passed to registerUser in the new logic.
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
            try {
                // Assuming registerUser in UserManager now handles only name, email, password
                // If phone, document_id, address are still needed, UserManager::registerUser needs to be updated
                $userId = $userManager->createUser($name, $email, $password, $phone, $document_id, $address);

                if ($userId) {
                    // Registro exitoso - resetear contador
                    $rateLimiter->reset('register');

                    $success = 'Usuario registrado exitosamente. Ya puedes iniciar sesión.';

                    // Auto-login después de registro
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_role'] = 'customer';

                    header('Location: tienda.php');
                    exit;
                } else {
                    // Registro fallido - contar intento
                    $rateLimiter->hit('register');
                    $error = 'Error al registrar usuario.';
                }
            } catch (Exception $e) {
                // Registro fallido - contar intento
                $rateLimiter->hit('register');
                $error = 'El email ya está registrado o hubo un error: ' . $e->getMessage(); // Added getMessage for more detail
            }
        }
    }
}

// 3. Cargamos la plantilla visual (Header y Menu)
require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container-fluid">
    <div class="row h-100 align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="col-12 col-sm-8 col-md-6 col-lg-5 col-xl-6">
            <div class="bg-secondary rounded p-4 p-sm-5 my-4 mx-3">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h3 class="text-primary"><i class="fa fa-user-plus me-2"></i>Registro</h3>
                </div>

                <?= $mensaje ?>

                <form method="post">
                    <div class="form-floating mb-3">
                        <input type="text" name="name" class="form-control" id="floatingText"
                            placeholder="Nombre Completo" required>
                        <label for="floatingText">Nombre</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="email" name="email" class="form-control" id="floatingInput"
                            placeholder="name@example.com" required>
                        <label for="floatingInput">Correo Electrónico</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="password" name="password" class="form-control" id="floatingPassword"
                            placeholder="Contraseña" required>
                        <label for="floatingPassword">Contraseña</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="text" name="phone" class="form-control" id="floatingPhone" placeholder="Teléfono"
                            required>
                        <label for="floatingPhone">Teléfono</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="text" name="document_id" class="form-control" id="floatingID"
                            placeholder="Documento de Identidad" required>
                        <label for="floatingID">Documento de Identidad</label>
                    </div>
                    <div class="form-floating mb-3">
                        <textarea name="address" class="form-control" placeholder="Dirección" id="floatingAddress"
                            style="height: 100px;" required></textarea>
                        <label for="floatingAddress">Dirección</label>
                    </div>

                    <button type="submit" class="btn btn-primary py-3 w-100 mb-4">Registrarse</button>
                    <p class="text-center mb-0">¿Ya tienes cuenta? <a href="login.php">Inicia Sesión</a></p>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>