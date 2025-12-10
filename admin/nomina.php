<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../templates/autoload.php';

session_start();
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

$msg = '';

// PROCESAR PAGO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pay') {
    $empId = $_POST['employee_id'];
    $amount = $_POST['amount'];
    $method = $_POST['payment_method_id'];
    $notes = $_POST['notes'];

    // Fechas (Simplificado: asume pago de HOY)
    $payDate = date('Y-m-d');

    $res = $payrollManager->registerPayment($empId, $amount, $payDate, null, null, $method, $notes, $_SESSION['user_id']);

    if (is_numeric($res)) {
        $msg = '<div class="alert alert-success">Pago registrado con éxito. ID: ' . $res . '</div>';
    } else {
        $msg = '<div class="alert alert-danger">' . $res . '</div>';
    }
}

$filterRole = $_GET['role'] ?? null;
$employees = $payrollManager->getPayrollStatus($filterRole);
$paymentMethods = $transactionManager->getPaymentMethods();
$history = $payrollManager->getHistory(10);

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<style>
    /* Mejorar legibilidad de textos para tema oscuro */
    label,
    .form-label {
        font-weight: 600 !important;
        color: var(--text-main, #f8fafc) !important;
        font-size: 15px !important;
        margin-bottom: 0.5rem;
        text-shadow: 0 1px 2px rgba(255, 255, 255, 0.3);
    }

    .small,
    small {
        font-size: 13px !important;
        font-weight: 500;
        color: var(--text-muted, #94a3b8) !important;
    }

    .table {
        font-size: 14px;
    }

    .table thead th {
        font-weight: 700;
        color: var(--text-main, #f8fafc) !important;
        background-color: rgba(255, 255, 255, 0.05) !important;
    }

    .btn {
        font-weight: 600;
    }

    .card-header {
        font-weight: 700;
    }
</style>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>💰 Gestión de Nómina</h2>
        <div>
            <a href="usuarios.php" class="btn btn-outline-primary"><i class="fa fa-users"></i> Configurar Empleados</a>
        </div>
    </div>

    <?= $msg ?>

    <!-- FILTROS -->
    <div class="card mb-4 bg-light">
        <div class="card-body py-2">
            <form method="GET" class="d-flex align-items-center gap-3">
                <label class="fw-bold">Filtrar:</label>
                <select name="role" class="form-select w-auto" onchange="this.form.submit()">
                    <option value="">Todos los Roles</option>
                    <option value="kitchen" <?= $filterRole == 'kitchen' ? 'selected' : '' ?>>Cocina (Producción)</option>
                    <option value="cashier" <?= $filterRole == 'cashier' ? 'selected' : '' ?>>Cajeros</option>
                    <option value="manager" <?= $filterRole == 'manager' ? 'selected' : '' ?>>Gerencia</option>
                </select>
                <?php if ($filterRole): ?>
                    <a href="nomina.php" class="text-secondary"><i class="fa fa-times"></i> Limpiar</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="row">
        <!-- LISTA DE PAGOS PENDIENTES -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Empleados y Estado de Pago</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th>Rol Contable</th>
                                <th>Salario Base</th>
                                <th>Estado</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $emp): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($emp['name']) ?></div>
                                        <small class="text-muted"><?= ucfirst($emp['salary_frequency']) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($emp['job_role'] == 'kitchen'): ?>
                                            <span class="badge bg-warning text-dark"><i class="fa fa-fire"></i> Cocina</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><i class="fa fa-user"></i> Personal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold text-success">
                                        $<?= number_format($emp['salary_amount'], 2) ?>
                                        <?php if ($emp['pending_debt'] > 0): ?>
                                            <div class="text-danger small mt-1" title="Deuda Pendiente">
                                                <i class="fa fa-arrow-down"></i> Deuda:
                                                $<?= number_format($emp['pending_debt'], 2) ?>
                                            </div>
                                            <div class="text-primary small fw-bold border-top mt-1 pt-1">
                                                Neto: $<?= number_format($emp['net_salary'], 2) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($emp['status'] == 'paid'): ?>
                                            <span class="badge bg-success">Al día</span>
                                        <?php elseif ($emp['status'] == 'due'): ?>
                                            <span class="badge bg-info text-dark">Pendiente</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">ATRASADO</span>
                                        <?php endif; ?>
                                        <div class="small text-muted" style="font-size: 0.75rem">
                                            Vence: <?= date('d/m', strtotime($emp['next_payment_due'])) ?>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-success"
                                            onclick="openPayModal(<?= htmlspecialchars(json_encode($emp)) ?>)">
                                            <i class="fa fa-money-bill"></i> Pagar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($employees)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-3">No hay empleados con salario configurado.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- HISTORIAL RECIENTE -->
        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Historial Reciente</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($history as $h): ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= htmlspecialchars($h['employee_name']) ?></strong>
                                    <div class="small text-muted"><?= date('d/m/Y', strtotime($h['payment_date'])) ?></div>
                                </div>
                                <span class="text-danger fw-bold">-$<?= number_format($h['amount'], 2) ?></span>
                            </div>
                            <small class="text-muted fst-italic"><?= htmlspecialchars($h['notes']) ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pagar -->
<div class="modal fade" id="modalPay" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Registrar Pago de Nómina</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="pay">
                <input type="hidden" name="employee_id" id="payEmpId">

                <h4 class="text-center mb-3" id="payEmpName">Nombre Empleado</h4>

                <div class="mb-3">
                    <label class="form-label">Monto a Pagar ($)</label>
                    <input type="number" step="0.01" name="amount" id="payAmount"
                        class="form-control form-control-lg text-center fw-bold" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Origen de Fondos (Método de Pago)</label>
                    <select name="payment_method_id" class="form-select" required>
                        <?php foreach ($paymentMethods as $pm): ?>
                            <option value="<?= $pm['id'] ?>"><?= $pm['name'] ?> (<?= $pm['currency'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notas / Observaciones</label>
                    <textarea name="notes" class="form-control" rows="2"
                        placeholder="Ej: Pago quincena 1 Noviembre"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success w-100">CONFIRMAR PAGO</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openPayModal(emp) {
        document.getElementById('payEmpId').value = emp.id;
        document.getElementById('payEmpName').innerText = emp.name;
        document.getElementById('payAmount').value = emp.salary_amount; // Sugerir salario base
        new bootstrap.Modal(document.getElementById('modalPay')).show();
    }
</script>

<?php require_once '../templates/footer.php'; ?>