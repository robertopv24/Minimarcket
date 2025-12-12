<?php
// admin/editar_usuario.php
require_once '../templates/autoload.php';

use Minimarcket\Core\Container;
use Minimarcket\Modules\User\Services\UserService;
use Minimarcket\Core\Security\CsrfToken;

$container = Container::getInstance();
$userService = $container->get(UserService::class);
$csrfToken = $container->get(CsrfToken::class);

session_start();
if (!isset($_SESSION['user_id']) || $userService->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $csrfToken->validateToken();

        $nombre = $_POST['nombre'];
        $email = $_POST['email'];
        $telefono = $_POST['telefono'];
        $documento = $_POST['documento'];
        $direccion = $_POST['direccion'];

        $jobRole = $_POST['job_role'] ?? 'other';
        $salaryFreq = $_POST['salary_frequency'] ?? 'monthly';
        $salaryAmount = $_POST['salary_amount'] ?? 0;

        // Actualizamos perfil
        $res1 = $userService->updateUserProfile($usuarioId, $nombre, $email, $telefono, $documento, $direccion);

        // Actualizamos nómina
        $res2 = $userService->updatePayrollData($usuarioId, $jobRole, $salaryFreq, $salaryAmount);

        if ($res1 === true && $res2 === true) {
            $mensaje = '<div class="alert alert-success shadow-sm rounded-3"><i class="fa fa-check-circle me-2"></i> Datos actualizados correctamente.</div>';
            $usuario = $userService->getUserById($usuarioId); // Refrescar datos
        } else {
            $err = ($res1 !== true) ? $res1 : (($res2 !== true) ? $res2 : 'Error desconocido');
            $mensaje = '<div class="alert alert-danger shadow-sm rounded-3"><i class="fa fa-exclamation-circle me-2"></i> ' . $err . '</div>';
        }
    } catch (Exception $e) {
        $mensaje = '<div class="alert alert-danger shadow-sm rounded-3"><i class="fa fa-exclamation-circle me-2"></i> ' . $e->getMessage() . '</div>';
    }
}

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-warning text-dark py-3 px-4">
                    <h3 class="mb-0 fw-bold"><i class="fa fa-user-edit me-2"></i> Editar Usuario</h3>
                </div>
                <div class="card-body p-4 p-md-5 bg-white">
                    <?= $mensaje; ?>

                    <form method="post" action="">
                        <?= $csrfToken->insertTokenField(); ?>

                        <h5 class="text-primary fw-bold mb-4 border-bottom pb-2 d-flex align-items-center">
                            <span
                                class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2"
                                style="width: 30px; height: 30px; font-size: 0.9rem;">1</span>
                            Datos Personales
                        </h5>

                        <div class="row g-4 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary">Nombre</label>
                                <input type="text" class="form-control bg-light border-0" name="nombre"
                                    value="<?= htmlspecialchars($usuario['name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary">Correo Electrónico</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i
                                            class="fa fa-envelope text-muted"></i></span>
                                    <input type="email" class="form-control bg-light border-0" name="email"
                                        value="<?= htmlspecialchars($usuario['email']) ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary">Teléfono</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i
                                            class="fa fa-phone text-muted"></i></span>
                                    <input type="text" class="form-control bg-light border-0" name="telefono"
                                        value="<?= htmlspecialchars($usuario['phone']) ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary">Documento ID</label>
                                <input type="text" class="form-control bg-light border-0" name="documento"
                                    value="<?= htmlspecialchars($usuario['document_id']) ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary">Dirección</label>
                            <textarea class="form-control bg-light border-0" name="direccion"
                                rows="2"><?= htmlspecialchars($usuario['address']) ?></textarea>
                        </div>

                        <div class="mb-5">
                            <label class="form-label fw-bold text-secondary">Rol de Sistema</label>
                            <input type="text" class="form-control bg-light border-0 text-muted fst-italic"
                                name="readonly_role"
                                value="<?= strtoupper($usuario['role']) == 'ADMIN' ? 'ADMINISTRADOR' : 'USUARIO/CAJERO' ?>"
                                disabled readonly>
                            <div class="form-text mt-2"><i class="fa fa-lock me-1"></i>El rol de sistema no se puede
                                cambiar desde aquí.</div>
                        </div>

                        <h5 class="text-success fw-bold mb-4 border-bottom pb-2 d-flex align-items-center">
                            <span
                                class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-2"
                                style="width: 30px; height: 30px; font-size: 0.9rem;">2</span>
                            Datos de Nómina
                        </h5>

                        <div class="row g-4 mb-3">
                            <div class="col-md-6">
                                <label for="job_role" class="form-label fw-bold text-secondary">Rol Funcional</label>
                                <select name="job_role" id="job_role"
                                    class="form-select border-success border-opacity-25 shadow-sm">
                                    <option value="">-- Sin asignar --</option>
                                    <option value="kitchen" <?= ($usuario['job_role'] ?? '') == 'kitchen' ? 'selected' : '' ?>>Cocina</option>
                                    <option value="delivery" <?= ($usuario['job_role'] ?? '') == 'delivery' ? 'selected' : '' ?>>Delivery</option>
                                    <option value="cashier" <?= ($usuario['job_role'] ?? '') == 'cashier' ? 'selected' : '' ?>>Cajero</option>
                                    <option value="manager" <?= ($usuario['job_role'] ?? '') == 'manager' ? 'selected' : '' ?>>Gerente / Encargado</option>
                                </select>
                                <small class="text-muted d-block mt-1">Define el rol operativo para reportes.</small>
                            </div>

                            <!-- Frecuencia Pago -->
                            <div class="col-md-6">
                                <label for="salary_frequency" class="form-label fw-bold text-secondary">Frecuencia de
                                    Pago</label>
                                <select name="salary_frequency" id="salary_frequency"
                                    class="form-select border-success border-opacity-25 shadow-sm">
                                    <option value="">-- Sin configurar --</option>
                                    <option value="weekly" <?= ($usuario['salary_frequency'] ?? '') == 'weekly' ? 'selected' : '' ?>>Semanal</option>
                                    <option value="biweekly" <?= ($usuario['salary_frequency'] ?? '') == 'biweekly' ? 'selected' : '' ?>>Quincenal</option>
                                    <option value="monthly" <?= ($usuario['salary_frequency'] ?? '') == 'monthly' ? 'selected' : '' ?>>Mensual</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-4 mb-3">
                            <!-- Salario Base -->
                            <div class="col-md-6 mb-3">
                                <label for="salary_amount" class="form-label fw-bold text-secondary">Salario Base
                                    ($)</label>
                                <div class="input-group">
                                    <span
                                        class="input-group-text bg-success text-white border-success border-opacity-25">$</span>
                                    <input type="number" name="salary_amount" id="salary_amount"
                                        class="form-control border-success border-opacity-25" step="0.01"
                                        value="<?= htmlspecialchars($usuario['salary_amount'] ?? '0') ?>"
                                        placeholder="0.00">
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-3 mt-5 d-md-flex justify-content-md-end">
                            <a href="usuarios.php" class="btn btn-outline-secondary px-4 rounded-pill">Cancelar</a>
                            <button type="submit" class="btn btn-warning btn-lg px-5 rounded-pill shadow hover-float">
                                <i class="fa fa-save me-2"></i> Guardar Cambios
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
        border-color: var(--bs-warning);
        background-color: #fff;
    }
</style>

<?php require_once '../templates/footer.php'; ?>