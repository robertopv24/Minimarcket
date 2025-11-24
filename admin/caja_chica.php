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

// Procesar Movimientos Manuales
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $amount = $_POST['amount'];
    $currency = $_POST['currency'];
    $description = $_POST['description'];
    $origin = ($type == 'deposit') ? 'manual_deposit' : 'owner_withdrawal';

    $res = $vaultManager->registerMovement($type, $origin, $amount, $currency, $description, $_SESSION['user_id']);

    if ($res === true) {
        $mensaje = '<div class="alert alert-success">Movimiento de efectivo registrado con éxito.</div>';
    } else {
        $mensaje = '<div class="alert alert-danger">Error: ' . $res . '</div>';
    }
}

// 1. Saldo Físico (Bóveda)
$balanceVault = $vaultManager->getBalance();

// 2. Saldos Totales por Método (Digitales y Bancos)
$sqlBalances = "SELECT pm.name, pm.type, pm.currency,
                SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE -t.amount END) as total_balance
                FROM payment_methods pm
                LEFT JOIN transactions t ON pm.id = t.payment_method_id
                GROUP BY pm.id, pm.name, pm.type, pm.currency
                HAVING total_balance != 0";
$stmt = $db->query($sqlBalances);
$globalBalances = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. OBTENER HISTORIAL DE MOVIMIENTOS (¡ESTO FALTABA!)
$stmtMovements = $db->query("SELECT * FROM vault_movements ORDER BY created_at DESC LIMIT 20");
$movements = $stmtMovements->fetchAll(PDO::FETCH_ASSOC);

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>🏦 Tesorería General</h2>
        <a href="reportes_caja.php" class="btn btn-outline-dark">
            <i class="fa fa-history"></i> Ver Historial de Cierres de Caja
        </a>
    </div>

    <?= $mensaje ?>

    <h5 class="text-muted border-bottom pb-2 mb-3">💵 Efectivo en Custodia (Bóveda)</h5>
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-success text-white text-center shadow">
                <div class="card-body">
                    <h2 class="fw-bold">$<?= number_format($balanceVault['balance_usd'], 2) ?></h2>
                    <p class="mb-0">Dólares Físicos</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-info text-white text-center shadow">
                <div class="card-body">
                    <h2 class="fw-bold"><?= number_format($balanceVault['balance_ves'], 2) ?> Bs</h2>
                    <p class="mb-0">Bolívares Físicos</p>
                </div>
            </div>
        </div>
    </div>

    <h5 class="text-muted border-bottom pb-2 mb-3">💳 Saldos en Cuentas (Calculados)</h5>
    <div class="row mb-4">
        <?php foreach ($globalBalances as $bal):
            if ($bal['type'] == 'cash') continue;
        ?>
            <div class="col-md-3 mb-3">
                <div class="card border-start border-4 border-primary shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted small"><?= htmlspecialchars($bal['name']) ?></h6>
                        <h4 class="text-primary fw-bold">
                            <?= number_format($bal['total_balance'], 2) ?>
                            <small class="text-dark fs-6"><?= $bal['currency'] ?></small>
                        </h4>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card mb-5 shadow-sm">
        <div class="card-header bg-secondary text-white">
            <i class="fa fa-hand-holding-usd"></i> Operaciones Manuales (Solo Efectivo/Bóveda)
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Acción</label>
                    <select name="type" class="form-select">
                        <option value="withdrawal">🔴 Retirar Dinero (Gasto/Dueño)</option>
                        <option value="deposit">🟢 Ingresar Dinero (Aporte)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Monto</label>
                    <input type="number" name="amount" step="0.01" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Moneda</label>
                    <select name="currency" class="form-select">
                        <option value="USD">USD ($)</option>
                        <option value="VES">VES (Bs)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Concepto</label>
                    <input type="text" name="description" class="form-control" placeholder="Ej: Pago nómina, Retiro personal..." required>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-dark px-5">Registrar</button>
                </div>
            </form>
        </div>
    </div>

    <h4 class="mb-3">📜 Historial de Movimientos de Bóveda</h4>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Monto</th>
                            <th>Origen</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($movements)): ?>
                            <?php foreach ($movements as $mov): ?>
                                <tr>
                                    <td><?= date('d/m H:i', strtotime($mov['created_at'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $mov['type'] == 'deposit' ? 'success' : 'danger' ?>">
                                            <?= $mov['type'] == 'deposit' ? 'Entrada' : 'Salida' ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold">
                                        <?= number_format($mov['amount'], 2) ?> <?= $mov['currency'] ?>
                                    </td>
                                    <td>
                                        <?php
                                            $origenLabel = match($mov['origin']) {
                                                'session_close' => '<span class="badge bg-info text-dark">Cierre Caja</span>',
                                                'supplier_payment' => '<span class="badge bg-warning text-dark">Pago Prov.</span>',
                                                'manual_deposit' => '<span class="badge bg-success">Aporte</span>',
                                                'owner_withdrawal' => '<span class="badge bg-danger">Retiro</span>',
                                                default => $mov['origin']
                                            };
                                            echo $origenLabel;
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($mov['description']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No hay movimientos registrados aún.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>
