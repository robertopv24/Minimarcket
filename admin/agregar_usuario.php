<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../templates/autoload.php';

use Minimarcket\Core\Container;
use Minimarcket\Modules\User\Services\UserService;

global $app;
$container = $app->getContainer();
$userService = $container->get(UserService::class);

$sessionManager = $container->get(\Minimarcket\Core\Session\SessionManager::class);

if (!$sessionManager->isAuthenticated() || $sessionManager->get('user_role') !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $telefono = $_POST['telefono'];
    $documento = $_POST['documento'];
    $direccion = $_POST['direccion'];
    $rol = $_POST['rol'];

    // Usamos el UserService para crear (encripta la contraseña automáticamente)
    $resultado = $userService->createUser($nombre, $email, $password, $telefono, $documento, $direccion, $rol);

    if ($resultado === true) {
        $mensaje = '<div class="alert alert-success shadow-sm rounded-3"><i class="fa fa-check-circle me-2"></i> Usuario creado con éxito. <a href="usuarios.php" class="alert-link">Volver a la lista</a></div>';
    } else {
        $mensaje = '<div class="alert alert-danger shadow-sm rounded-3"><i class="fa fa-exclamation-circle me-2"></i> ' . $resultado . '</div>';
    }
}

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-success text-white py-3 px-4">
                    <h3 class="mb-0 fw-bold"><i class="fa fa-user-plus me-2"></i> Nuevo Usuario</h3>
                </div>
                <div class="card-body p-4 p-md-5 bg-white">
                    <?= $mensaje; ?>

                    <form method="post" action="">

                        <h5 class="text-success fw-bold mb-4 border-bottom pb-2 d-flex align-items-center">
                            <span
                                class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-2"
                                style="width: 30px; height: 30px; font-size: 0.9rem;">1</span>
                            Credenciales de Acceso
                        </h5>

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary">Correo Electrónico</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i
                                            class="fa fa-envelope text-muted"></i></span>
                                    <input type="email" class="form-control bg-light border-0" name="email"
                                        placeholder="ejemplo@correo.com" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary">Contraseña Temporal</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i
                                            class="fa fa-key text-muted"></i></span>
                                    <input type="password" class="form-control bg-light border-0" name="password"
                                        placeholder="********" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-5">
                            <label class="form-label fw-bold text-secondary">Rol / Permisos</label>
                            <select class="form-select form-select-lg border-success border-opacity-25 shadow-sm"
                                name="rol" required>
                                <option value="user">🔵 Usuario / Cajero (Ventas y Caja)</option>
                                <option value="admin">🔴 Administrador (Acceso Total)</option>
                            </select>
                            <div class="form-text mt-2"><i class="fa fa-info-circle me-1"></i>El rol define a qué partes
                                del sistema puede entrar esta persona.</div>
                        </div>

                        <h5 class="text-primary fw-bold mb-4 border-bottom pb-2 d-flex align-items-center">
                            <span
                                class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2"
                                style="width: 30px; height: 30px; font-size: 0.9rem;">2</span>
                            Información Personal
                        </h5>

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary">Nombre Completo</label>
                                <input type="text" class="form-control bg-light border-0" name="nombre"
                                    placeholder="Juan Pérez" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary">Documento ID (Cédula)</label>
                                <input type="text" class="form-control bg-light border-0" name="documento"
                                    placeholder="V-12345678">
                            </div>
                        </div>

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary">Teléfono</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i
                                            class="fa fa-phone text-muted"></i></span>
                                    <input type="text" class="form-control bg-light border-0" name="telefono"
                                        placeholder="0414-1234567">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary">Dirección</label>
                                <textarea class="form-control bg-light border-0" name="direccion" rows="1"
                                    placeholder="Dirección de habitación"></textarea>
                            </div>
                        </div>

                        <div class="d-grid gap-3 mt-5 d-md-flex justify-content-md-end">
                            <a href="usuarios.php" class="btn btn-outline-secondary px-4 rounded-pill">Cancelar</a>
                            <button type="submit" class="btn btn-success btn-lg px-5 rounded-pill shadow hover-float">
                                <i class="fa fa-save me-2"></i> Registrar Usuario
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

    .form-control:focus,
    .form-select:focus {
        box-shadow: none;
        border-color: var(--bs-primary);
        background-color: #fff;
    }
</style>

<?php require_once '../templates/footer.php'; ?>