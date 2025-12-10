<?php
// Production settings applied via autoload

require_once '../templates/autoload.php';
require_once '../funciones/Csrf.php';

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
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) die("CSRF Invalid");
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $documento = $_POST['documento'];
    $direccion = $_POST['direccion'];
    // Nota: El cambio de rol requeriría una función específica en UserManager si no está en updateUserProfile.
    // Asumimos que updateUserProfile actualiza datos básicos. Si necesitas cambiar rol, habría que actualizar la clase UserManager o hacer un SQL directo aquí (aunque lo ideal es el Manager).
    // Por ahora, mantenemos la edición de perfil básico.

    $jobRole = $_POST['job_role'] ?? 'other';
    $salaryFreq = $_POST['salary_frequency'] ?? 'monthly';
    $salaryAmount = $_POST['salary_amount'] ?? 0;

    // Actualizamos perfil y datos de nomina (idealmente en un solo metodo, pero aqui extendemos la logica)
    // Primero, basicos:
    $res1 = $userManager->updateUserProfile($usuarioId, $nombre, $email, $telefono, $documento, $direccion);

    // Segundo, nomina (SQL directo temporal si UserManager no tiene el metodo, 
    // pero lo correcto es agregarlo a UserManager. Por brevedad ejecutaremos UPDATE aqui para cumplir sin reescribir todo UserManager).
    // NOTA: En produccion, agregar metodo updatePayrollData($uid, ...) a UserManager.
    try {
        $stmt = $db->prepare("UPDATE users SET job_role = ?, salary_frequency = ?, salary_amount = ? WHERE id = ?");
        $stmt->execute([$jobRole, $salaryFreq, $salaryAmount, $usuarioId]);
        $res2 = true;
    } catch (Exception $e) {
        $res2 = "Error nomina: " . $e->getMessage();
    }

    if ($res1 === true && $res2 === true) {
        $resultado = true;
    } else {
        $resultado = "Error: " . ($res1 !== true ? $res1 : $res2);
    }

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
                        <?= Csrf::insertTokenField(); ?>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nombre</label>
                                <input type="text" class="form-control" name="nombre"
                                    value="<?= htmlspecialchars($usuario['name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Correo Electrónico</label>
                                <input type="email" class="form-control" name="email"
                                    value="<?= htmlspecialchars($usuario['email']) ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" class="form-control" name="telefono"
                                    value="<?= htmlspecialchars($usuario['phone']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Documento ID</label>
                                <input type="text" class="form-control" name="documento"
                                    value="<?= htmlspecialchars($usuario['document_id']) ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Dirección</label>
                            <textarea class="form-control" name="direccion"
                                rows="2"><?= htmlspecialchars($usuario['address']) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Rol de Sistema</label>
                            <input type="text" class="form-control" name="readonly_role"
                                value="<?= strtoupper($usuario['role']) ?>" disabled readonly>
                            <div class="form-text">Define permisos de acceso (Admin vs Cajero).</div>
                        </div>

                        <hr class="my-4">
                        <h5 class="text-success border-bottom pb-2"><i class="fa fa-money-bill-wave"></i> Datos de
                            Nómina y Costos</h5>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="job_role" class="form-label">Rol Funcional (Contabilidad)</label>
                                <select name="job_role" id="job_role" class="form-select">
                                    <option value="">-- Sin asignar --</option>
                                    <option value="kitchen" <?= ($usuario['job_role'] ?? '') == 'kitchen' ? 'selected' : '' ?>>Cocina</option>
                                    <option value="delivery" <?= ($usuario['job_role'] ?? '') == 'delivery' ? 'selected' : '' ?>>Delivery</option>
                                    <option value="cashier" <?= ($usuario['job_role'] ?? '') == 'cashier' ? 'selected' : '' ?>>Cajero</option>
                                    <option value="manager" <?= ($usuario['job_role'] ?? '') == 'manager' ? 'selected' : '' ?>>Gerente / Encargado (Gasto Gral)</option>
                                </select>
                                <small class="text-muted">Define el rol operativo para clasificar gastos de
                                    nómina.</small>
                            </div>

                            <!-- Frecuencia Pago -->
                            <div class="col-md-6">
                                <label for="salary_frequency" class="form-label">Frecuencia de Pago</label>
                                <select name="salary_frequency" id="salary_frequency" class="form-select">
                                    <option value="">-- Sin configurar --</option>
                                    <option value="weekly" <?= ($usuario['salary_frequency'] ?? '') == 'weekly' ? 'selected' : '' ?>>Semanal</option>
                                    <option value="biweekly" <?= ($usuario['salary_frequency'] ?? '') == 'biweekly' ? 'selected' : '' ?>>Quincenal</option>
                                    <option value="monthly" <?= ($usuario['salary_frequency'] ?? '') == 'monthly' ? 'selected' : '' ?>>Mensual</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <!-- Salario Base -->
                            <div class="col-md-6 mb-3">
                                <label for="salary_amount" class="form-label">Salario Base ($)</label>
                                <input type="number" name="salary_amount" id="salary_amount" class="form-control"
                                    step="0.01" value="<?= htmlspecialchars($usuario['salary_amount'] ?? '0') ?>"
                                    placeholder="0.00">
                            </div>
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