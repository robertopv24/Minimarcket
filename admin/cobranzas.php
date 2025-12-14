<?php
// admin/cobranzas.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../templates/autoload.php';

use Minimarcket\Core\Container;
use Minimarcket\Modules\User\Services\UserService;
use Minimarcket\Modules\Finance\Services\CreditService;
use Minimarcket\Modules\Finance\Services\TransactionService;
use Minimarcket\Modules\Finance\Services\CashRegisterService;
use Minimarcket\Core\Security\CsrfToken;

$container = Container::getInstance();
$userService = $container->get(UserService::class);
$creditService = $container->get(CreditService::class);
$transactionService = $container->get(TransactionService::class);
$cashRegisterService = $container->get(CashRegisterService::class);
$csrfToken = $container->get(CsrfToken::class);

// Validar Admin o Cajero
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$currentUser = $userService->getUserById($_SESSION['user_id']);
if (!$currentUser || !in_array($currentUser['role'], ['admin', 'cashier'])) {
    header("Location: ../index.php");
    exit;
}

// Procesar Abono
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_debt'])) {
    // Validar CSRF
    try {
        $csrfToken->validateToken();
    } catch (Exception $e) {
        die("Error de seguridad: " . $e->getMessage());
    }

    require_once '../funciones/debug_logger.php';
    clearDebugLog(); // Limpiar log anterior
    
    $arId = $_POST['ar_id'];
    $amount = filter_var($_POST['pay_amount'], FILTER_VALIDATE_FLOAT);
    $paymentMethodId = $_POST['payment_method_id'] ?? null;

    // Debug logging
    debugLog("=== PAYMENT DEBUG ===");
    debugLog("AR ID: $arId");
    debugLog("Amount: $amount");
    debugLog("Payment Method ID: $paymentMethodId");

    if ($amount > 0 && $paymentMethodId) {
        // Buscar sesión de caja activa del usuario actual
        $userId = $_SESSION['user_id'];
        $sessionStatus = $cashRegisterService->getStatus($userId);

        debugLog("User ID: $userId");
        debugLog("Session Status: " . ($sessionStatus ? 'OPEN' : 'CLOSED'));

        if (!$sessionStatus) {
            $error = "⚠️ No tienes una caja abierta. Debes abrir caja primero.";
            debugLog("ERROR: No open cash session");
        } else {
            $sessionId = $sessionStatus['id'];
            
            // Registrar pago (ahora incluye transacción automáticamente)
            try {
                debugLog("Calling payDebt...");
                // Note: payDebt signature: ($arId, $amountToPay, $paymentMethodId, $paymentRef = '', $paymentCurrency = 'USD', $userId = 1, $sessionId = 1)
                $result = $creditService->payDebt($arId, $amount, $paymentMethodId, '', 'USD', $userId, $sessionId);
                debugLog("payDebt result: " . ($result === true ? 'TRUE' : 'FALSE/Error'));

                if ($result === true) {
                    $success = "✅ Abono registrado correctamente. Dinero ingresado a caja.";
                    debugLog("SUCCESS: Payment processed");
                    // Recargar página para ver cambios
                    // Use meta refresh or JS instead of header to avoid issues with output sent if any debug echoes happened
                    // But here we rely on header since debugLog writes to file.
                    header("Location: " . $_SERVER['PHP_SELF'] . "?client_id=" . ($_GET['client_id'] ?? ''));
                    exit;
                } else {
                    $error = "❌ Error al procesar el pago: " . (is_string($result) ? $result : "Verifica los datos.");
                    debugLog("ERROR: payDebt returned: $result");
                }
            } catch (Exception $e) {
                $error = "❌ Error: " . $e->getMessage();
                debugLog("EXCEPTION: " . $e->getMessage());
                debugLog("Stack trace: " . $e->getTraceAsString());
            }
        }
    } else {
        $error = "❌ Debe ingresar un monto válido y seleccionar un método de pago.";
        debugLog("ERROR: Invalid amount or payment method");
    }
}

// Obtener Clientes con Deuda > 0
$debtors = $creditService->getDebtors();

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
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
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

    .table tbody td {
        color: var(--text-main, #f8fafc) !important;
    }

    .btn {
        font-weight: 600;
    }

    .card-header {
        font-weight: 700;
    }

    /* Fix para headers con bg-warning */
    .card-header.bg-warning h5,
    .card-header.bg-warning .text-dark {
        color: #000000ff !important;
        font-weight: 700;
    }

    .list-group-item {
        font-size: 14px;
    }

    .fw-bold {
        font-weight: 700 !important;
    }

    /* Mejorar contraste en modales */
    .modal-body p,
    .modal-body strong {
        color: var(--text-main, #f8fafc) !important;
        color: #333 !important; /* Force dark text for modal body on light background if modal is light */
    }
    
    /* If modal is dark, override above */
    [data-bs-theme="dark"] .modal-body p,
    [data-bs-theme="dark"] .modal-body strong {
         color: #f8fafc !important;
    }

    /* Asegurar que los inputs sean legibles */
    .form-control,
    .form-select {
        font-size: 14px;
        font-weight: 500;
    }

    /* Fix específico para header con bg-warning bg-opacity-25 */
    .bg-warning.bg-opacity-25 h5,
    .bg-warning.bg-opacity-25 .text-dark {
        color: #ebe9e9ff !important;
    }

    /* Fix para botón outline-secondary */
    .btn-outline-secondary {
        color: var(--text-main, #f8fafc) !important;
        border-color: var(--text-muted, #94a3b8) !important;
    }

    .btn-outline-secondary:hover {
        background-color: rgba(255, 255, 255, 0.1) !important;
        color: var(--text-main, #f8fafc) !important;
        border-color: var(--text-main, #f8fafc) !important;
    }
</style>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fa fa-wallet me-2"></i> Gestión de Cobranzas</h2>
        <a href="clientes.php" class="btn btn-outline-secondary">Gestionar Clientes</a>
    </div>

    <?php if (isset($success))
        echo "<div class='alert alert-success'>$success</div>"; ?>
    <?php if (isset($error))
        echo "<div class='alert alert-danger'>$error</div>"; ?>

    <div class="row">
        <!-- LISTADO DE DEUDORES -->
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-header bg-warning bg-opacity-25">
                    <h5 class="mb-0 text-dark">Clientes con Saldo Pendiente</h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php if (empty($debtors)): ?>
                        <div class="p-3 text-center text-muted">No hay clientes con deuda.</div>
                    <?php else: ?>
                        <?php foreach ($debtors as $c): ?>
                            <a href="?client_id=<?= $c['id'] ?>"
                                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= (isset($_GET['client_id']) && $_GET['client_id'] == $c['id']) ? 'active' : '' ?>">
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($c['name']) ?></div>
                                    <small
                                        class="<?= (isset($_GET['client_id']) && $_GET['client_id'] == $c['id']) ? 'text-white' : 'text-muted' ?>">Tlf:
                                        <?= $c['phone'] ?></small>
                                </div>
                                <span class="badge bg-danger rounded-pill">$<?= number_format($c['current_debt'], 2) ?></span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- DETALLE Y PAGO -->
        <div class="col-md-7">
            <?php if (isset($_GET['client_id'])):
                $cId = $_GET['client_id'];
                $client = $creditService->getClientById($cId);
                // Obtener deudas detallo
                $debts = $creditService->getPendingDebtsByClient($cId); // Uses Service!
                ?>
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between">
                        <h4 class="mb-0"><?= htmlspecialchars($client['name']) ?></h4>
                        <span class="fs-5">Deuda Total:
                            <strong>$<?= number_format($client['current_debt'], 2) ?></strong></span>
                    </div>
                    <div class="card-body">
                        <h6 class="text-muted mb-3">Cuentas por Pagar</h6>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Ref. Orden</th>
                                    <th>Fecha</th>
                                    <th>Monto Orig.</th>
                                    <th>Pagado</th>
                                    <th>Saldo</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($debts as $d):
                                    $pending = $d['amount'] - $d['paid_amount'];
                                    ?>
                                    <tr>
                                        <td>#<?= $d['order_id'] ?? 'S/R' ?></td>
                                        <td><?= date('d/m/y', strtotime($d['created_at'])) ?></td>
                                        <td><?= number_format($d['amount'], 2) ?></td>
                                        <td><?= number_format($d['paid_amount'], 2) ?></td>
                                        <td class="fw-bold text-danger"><?= number_format($pending, 2) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-success"
                                                onclick="openPayModal(<?= $d['id'] ?>, <?= $d['order_id'] ?? 0 ?>, <?= $d['amount'] ?>, <?= $pending ?>)">
                                                <i class="fa fa-hand-holding-usd"></i> Abonar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info border-info">
                    <i class="fa fa-arrow-left me-2"></i> Seleccione un cliente para ver sus cuentas y registrar pagos.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MODAL ÚNICO PARA ABONOS (Fuera de la tabla) -->
<div class="modal fade" id="modalPayDebt" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">💵 Registrar Abono</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <?= $csrfToken->insertTokenField() ?>
                <div class="modal-body">
                    <input type="hidden" name="ar_id" id="modal_ar_id">

                    <p><strong>Orden:</strong> #<span id="modal_order_id"></span></p>
                    <p><strong>Deuda Original:</strong> $<span id="modal_amount"></span></p>
                    <p><strong>Pendiente:</strong> <span class="text-danger fs-5 fw-bold">$<span
                                id="modal_pending"></span></span></p>
                    <hr>

                    <div class="mb-3">
                        <label class="form-label" style="color:#000;">Método de Pago</label>
                        <select name="payment_method_id" class="form-select" required>
                            <option value="">-- Seleccionar --</option>
                            <?php
                            // Uses Service!
                            $methods = $transactionService->getPaymentMethods();
                            foreach ($methods as $m):
                                ?>
                                <option value="<?= $m['id'] ?>">
                                    <?= htmlspecialchars($m['name']) ?> (<?= $m['currency'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label style="color:#000;">Monto a Abonar ($)</label>
                        <input type="number" step="0.01" name="pay_amount" id="modal_pay_amount"
                            class="form-control form-control-lg" required>
                        <small class="text-muted">Máx: $<span id="modal_max_amount"></span></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="pay_debt" class="btn btn-success">✅ Registrar Pago</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openPayModal(arId, orderId, amount, pending) {
        // Poblar los campos del modal
        document.getElementById('modal_ar_id').value = arId;
        document.getElementById('modal_order_id').textContent = orderId || 'S/R';
        document.getElementById('modal_amount').textContent = amount.toFixed(2);
        document.getElementById('modal_pending').textContent = pending.toFixed(2);
        document.getElementById('modal_max_amount').textContent = pending.toFixed(2);

        // Configurar el input de pago
        const payInput = document.getElementById('modal_pay_amount');
        payInput.max = pending;
        payInput.value = '';
        payInput.placeholder = 'Ej: ' + pending.toFixed(2);

        // Abrir el modal
        const modal = new bootstrap.Modal(document.getElementById('modalPayDebt'));
        modal.show();
    }
</script>

<?php require_once '../templates/footer.php'; ?>