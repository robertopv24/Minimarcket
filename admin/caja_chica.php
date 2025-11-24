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

// Procesar Movimientos Manuales (Retiro/Depósito)
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type']; // deposit / withdrawal
    $amount = $_POST['amount'];
    $currency = $_POST['currency'];
    $description = $_POST['description'];
    $origin = ($type == 'deposit') ? 'manual_deposit' : 'owner_withdrawal';

    $res = $vaultManager->registerMovement($type, $origin, $amount, $currency, $description, $_SESSION['user_id']);

    if ($res === true) {
        $mensaje = '<div class="alert alert-success">Movimiento registrado con éxito.</div>';
    } else {
        $mensaje = '<div class="alert alert-danger">Error: ' . $res . '</div>';
    }
}

$balance = $vaultManager->getBalance();

// Obtener últimos movimientos
$stmt = $db->query("SELECT * FROM vault_movements ORDER BY created_at DESC LIMIT 20");
$movements = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5">
    <h2 class="text-center mb-4">🏦 Caja Chica (Tesorería Central)</h2>

    <?= $mensaje ?>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-success text-white text-center shadow">
                <div class="card-body">
                    <h3>$<?= number_format($balance['balance_usd'], 2) ?></h3>
                    <p>Saldo Acumulado (USD)</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-info text-white text-center shadow">
                <div class="card-body">
                    <h3><?= number_format($balance['balance_ves'], 2) ?> Bs</h3>
                    <p>Saldo Acumulado (VES)</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">Registrar Movimiento Manual (Retiro/Aporte)</div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tipo</label>
                    <select name="type" class="form-select">
                        <option value="withdrawal">🔴 Retiro (Gasto/Ganancia)</option>
                        <option value="deposit">🟢 Depósito (Aporte)</option>
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
                    <label class="form-label">Descripción</label>
                    <input type="text" name="description" class="form-control" placeholder="Ej: Pago de Luz, Retiro de Socio..." required>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary w-100">Registrar Movimiento</button>
                </div>
            </form>
        </div>
    </div>

    <h4>Historial de Movimientos</h4>
    <table class="table table-striped">
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
                    <td><?= $mov['origin'] ?></td>
                    <td><?= $mov['description'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../templates/footer.php'; ?>
