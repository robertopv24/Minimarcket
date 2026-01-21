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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $telefono = $_POST['telefono'];
    $documento = $_POST['documento'];
    $direccion = $_POST['direccion'];
    $rol = $_POST['rol'];

    // Usamos el UserManager para crear (encripta la contraseña automáticamente)
    $resultado = $userManager->createUser($nombre, $email, $password, $telefono, $documento, $direccion, $rol);

    if ($resultado === true) {
        $mensaje = '<div class="alert alert-success"><i class="fa fa-check-circle"></i> Usuario creado con éxito. <a href="usuarios.php" class="alert-link">Volver a la lista</a></div>';
    } else {
        $mensaje = '<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> ' . $resultado . '</div>';
    }
}

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0"><i class="fa fa-user-plus"></i> Nuevo Usuario</h3>
                </div>
                <div class="card-body">
                    <?= $mensaje; ?>

                    <form method="post" action="">

                        <h5 class="text-success mb-3 border-bottom pb-2">Credenciales de Acceso</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Correo Electrónico</label>
                                <input type="email" class="form-control" name="email" placeholder="ejemplo@correo.com" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Contraseña Temporal</label>
                                <input type="password" class="form-control" name="password" placeholder="********" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Rol / Permisos</label>
                            <select class="form-select" name="rol" required>
                                <option value="user">🔵 Usuario / Cajero (Ventas y Caja)</option>
                                <option value="admin">🔴 Administrador (Acceso Total)</option>
                            </select>
                            <div class="form-text">El rol define a qué partes del sistema puede entrar esta persona.</div>
                        </div>

                        <h5 class="text-primary mb-3 border-bottom pb-2">Información Personal</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre Completo</label>
                                <input type="text" class="form-control" name="nombre" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Documento ID (Cédula)</label>
                                <input type="text" class="form-control" name="documento">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" class="form-control" name="telefono">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Dirección</label>
                                <textarea class="form-control" name="direccion" rows="1"></textarea>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-success btn-lg">Registrar Usuario</button>
                            <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>
