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

// Calcular diferencias de efectivo
$diffUsd = $session['closing_balance_usd'] - $session['calculated_usd'];
$diffVes = $session['closing_balance_ves'] - $session['calculated_ves'];

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
                                <td class="text-start">Sistema (Fondo + Ventas)</td>
                                <td><?= number_format($session['calculated_usd'], 2) ?></td>
                                <td><?= number_format($session['calculated_ves'], 2) ?></td>
                            </tr>
                            <tr>
                                <td class="text-start">Cajero (Conteo Real)</td>
                                <td class="fw-bold"><?= number_format($session['closing_balance_usd'], 2) ?></td>
                                <td class="fw-bold"><?= number_format($session['closing_balance_ves'], 2) ?></td>
                            </tr>
                            <tr class="<?= ($diffUsd != 0 || $diffVes != 0) ? 'table-danger' : 'table-success' ?>">
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

                    <?php if ($diffUsd != 0 || $diffVes != 0): ?>
                        <div class="alert alert-warning small mb-0 border-warning">
                            <i class="fa fa-exclamation-triangle"></i> <strong>Atención:</strong> El efectivo contado no coincide con el sistema.
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

                        $finalTotal = $m['total'];
                        $badgeFondo = '';

                        // CORRECCIÓN: Usamos $m['method_name'] que es como viene de la BD
                        if ($m['method_name'] === 'Efectivo USD') {
                            $finalTotal += $session['opening_balance_usd'];
                            if ($session['opening_balance_usd'] > 0) {
                                $badgeFondo = '<span class="badge bg-warning text-dark ms-1" style="font-size: 0.6rem;">+ Fondo $' . number_format($session['opening_balance_usd'], 2) . '</span>';
                            }
                        }

                        if ($m['method_name'] === 'Efectivo VES') {
                            $finalTotal += $session['opening_balance_ves'];
                            if ($session['opening_balance_ves'] > 0) {
                                $badgeFondo = '<span class="badge bg-warning text-dark ms-1" style="font-size: 0.6rem;">+ Fondo ' . number_format($session['opening_balance_ves'], 2) . '</span>';
                            }
                        }
                    ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa <?= $icon ?> text-muted me-2"></i>
                                <strong><?= $m['method_name'] ?></strong>
                                <?php if($isCash): ?>
                                    <span class="badge bg-dark rounded-pill ms-2" style="font-size: 0.6rem;">CAJA</span>
                                <?php else: ?>
                                    <span class="badge bg-info text-dark rounded-pill ms-2" style="font-size: 0.6rem;">BANCO</span>
                                <?php endif; ?>
                                <?= $badgeFondo ?>
                            </div>
                            <span class="fs-5 text-end">
                                <?= number_format($finalTotal, 2) ?>
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
