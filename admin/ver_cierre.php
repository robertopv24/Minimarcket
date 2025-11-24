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

$sessionId = $_GET['id'] ?? 0;
$data = $cashRegisterManager->getSessionDetailsById($sessionId);

if (!$data) {
    echo '<div class="container mt-5"><div class="alert alert-danger">Cierre no encontrado.</div><a href="reportes_caja.php" class="btn btn-secondary">Volver</a></div>';
    exit;
}

$session = $data['info'];
$methods = $data['methods'];

// --- RECALCULAR ESPERADO (CORREGIDO - SIN DUPLICIDAD) ---
// La variable $methods ya incluye la transacción de "Apertura de Caja" (Adjustment).
// Por lo tanto, la suma de $methods es el total absoluto que debe haber en caja.

$expectedUsd = 0;
$expectedVes = 0;

foreach ($methods as $m) {
    // Sumamos lo que dicen las transacciones registradas (que ya incluyen el fondo)
    if ($m['method_name'] === 'Efectivo USD') {
        $expectedUsd += $m['total'];
    }
    if ($m['method_name'] === 'Efectivo VES') {
        $expectedVes += $m['total'];
    }
}

// Si por alguna razón no hubo transacciones de efectivo (ni siquiera fondo), usamos el opening_balance
if ($expectedUsd == 0 && $session['opening_balance_usd'] > 0) $expectedUsd = $session['opening_balance_usd'];
if ($expectedVes == 0 && $session['opening_balance_ves'] > 0) $expectedVes = $session['opening_balance_ves'];

// Calcular diferencias reales
$diffUsd = $session['closing_balance_usd'] - $expectedUsd;
$diffVes = $session['closing_balance_ves'] - $expectedVes;

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>🔎 Detalle de Cierre #<?= $session['id'] ?></h2>
        <a href="reportes_caja.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Volver</a>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-4">
                    <small class="text-muted">Cajero</small>
                    <h5 class="text-muted fw-bold"><?= htmlspecialchars($session['cashier_name']) ?></h5>
                </div>
                <div class="col-md-4">
                    <small class="text-muted">Apertura</small>
                    <h5 class="text-muted"><?= date('d/m/Y h:i A', strtotime($session['opened_at'])) ?></h5>
                </div>
                <div class="col-md-4">
                    <small class="text-muted">Cierre</small>
                    <h5 class="text-muted"><?= date('d/m/Y h:i A', strtotime($session['closed_at'])) ?></h5>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow border-danger mb-4">
                <div class="card-header bg-danger text-white fw-bold">
                    <i class="fa fa-money-bill-wave"></i> Cuadre de Efectivo (Físico)
                </div>
                <div class="card-body">
                    <table class="table table-bordered text-center">
                        <thead class="table-light">
                            <tr>
                                <th>Concepto</th>
                                <th>Dólares ($)</th>
                                <th>Bolívares (Bs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-start">
                                    Sistema (Fondo + Ventas)
                                </td>
                                <td class="fw-bold"><?= number_format($expectedUsd, 2) ?></td>
                                <td class="fw-bold"><?= number_format($expectedVes, 2) ?></td>
                            </tr>
                            <tr>
                                <td class="text-start">Cajero (Conteo Real)</td>
                                <td><?= number_format($session['closing_balance_usd'], 2) ?></td>
                                <td><?= number_format($session['closing_balance_ves'], 2) ?></td>
                            </tr>
                            <tr class="<?= (abs($diffUsd) > 0.5 || abs($diffVes) > 1) ? 'table-warning' : 'table-success' ?>">
                                <td class="text-start fw-bold">Diferencia</td>
                                <td class="fw-bold">
                                    <?= ($diffUsd > 0 ? '+' : '') . number_format($diffUsd, 2) ?>
                                </td>
                                <td class="fw-bold">
                                    <?= ($diffVes > 0 ? '+' : '') . number_format($diffVes, 2) ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <?php if (abs($diffUsd) > 0.5 || abs($diffVes) > 1): ?>
                        <div class="alert alert-warning small mb-0 border-warning">
                            <i class="fa fa-exclamation-triangle"></i> <strong>Atención:</strong> El efectivo contado no coincide.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success small mb-0 text-center">
                            <i class="fa fa-check-circle"></i> Cuadre Perfecto
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header bg-dark text-white fw-bold">
                    <i class="fa fa-chart-pie"></i> Totales por Método de Pago
                </div>
                <ul class="list-group list-group-flush">
                    <?php
                    foreach ($methods as $m):
                        $icon = $m['type'] == 'cash' ? 'fa-money-bill' : 'fa-university';
                        $isCash = $m['type'] == 'cash';

                        // ELIMINAMOS LA SUMA MANUAL DEL FONDO
                        // $m['total'] ya viene con la transacción de apertura incluida desde la DB
                        $displayTotal = $m['total'];

                        $badgeFondo = '';
                        // Solo mostramos la etiqueta visual para informar, pero NO sumamos nada extra
                        if ($m['method_name'] === 'Efectivo USD' && $session['opening_balance_usd'] > 0) {
                            $badgeFondo = '<span class="badge bg-warning text-dark ms-1" style="font-size: 0.6rem;" title="Incluido en el total">Inc. Fondo $' . number_format($session['opening_balance_usd'], 2) . '</span>';
                        }
                        if ($m['method_name'] === 'Efectivo VES' && $session['opening_balance_ves'] > 0) {
                            $badgeFondo = '<span class="badge bg-warning text-dark ms-1" style="font-size: 0.6rem;" title="Incluido en el total">Inc. Fondo ' . number_format($session['opening_balance_ves'], 2) . '</span>';
                        }
                    ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa <?= $icon ?> text-muted me-2"></i>
                                <strong><?= htmlspecialchars($m['method_name']) ?></strong>
                                <?php if($isCash): ?>
                                    <span class="badge bg-secondary rounded-pill ms-2" style="font-size: 0.6rem;">CAJA</span>
                                <?php else: ?>
                                    <span class="badge bg-info text-dark rounded-pill ms-2" style="font-size: 0.6rem;">BANCO</span>
                                <?php endif; ?>
                                <?= $badgeFondo ?>
                            </div>
                            <span class="fs-5 text-end">
                                <?= number_format($displayTotal, 2) ?>
                                <small class="fs-6 text-muted"><?= $m['currency'] ?></small>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="card-footer text-muted small">
                    * Estos montos incluyen ventas, vueltos y el fondo de caja inicial.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>
