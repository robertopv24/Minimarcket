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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $documento = $_POST['documento'];
    $direccion = $_POST['direccion'];
    // Nota: El cambio de rol requeriría una función específica en UserManager si no está en updateUserProfile.
    // Asumimos que updateUserProfile actualiza datos básicos. Si necesitas cambiar rol, habría que actualizar la clase UserManager o hacer un SQL directo aquí (aunque lo ideal es el Manager).
    // Por ahora, mantenemos la edición de perfil básico.

    $resultado = $userManager->updateUserProfile($usuarioId, $nombre, $email, $telefono, $documento, $direccion);

    if ($resultado === true) {
        $mensaje = '<div class="alert alert-success">Datos actualizados correctamente.</div>';
        $usuario = $userManager->getUserById($usuarioId); // Refrescar datos
    } else {
        $mensaje = '<div class="alert alert-danger">' . $resultado . '</div>';
    }
}

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h3 class="mb-0"><i class="fa fa-user-edit"></i> Editar Usuario</h3>
                </div>
                <div class="card-body">
                    <?= $mensaje; ?>

                    <form method="post" action="">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nombre</label>
                                <input type="text" class="form-control" name="nombre" value="<?= htmlspecialchars($usuario['name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Correo Electrónico</label>
                                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" class="form-control" name="telefono" value="<?= htmlspecialchars($usuario['phone']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Documento ID</label>
                                <input type="text" class="form-control" name="documento" value="<?= htmlspecialchars($usuario['document_id']) ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Dirección</label>
                            <textarea class="form-control" name="direccion" rows="2"><?= htmlspecialchars($usuario['address']) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Rol Actual</label>
                            <input type="text" class="form-control" value="<?= strtoupper($usuario['role']) ?>" disabled readonly>
                            <div class="form-text">Para cambiar el rol o la contraseña, contacta al soporte técnico o base de datos (por seguridad).</div>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-warning btn-lg">Guardar Cambios</button>
                            <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>
