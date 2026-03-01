<?php
session_start();
require_once '../templates/autoload.php';

// Validar auth básica (cualquier user)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$userData = $userManager->getUserById($userId);

// Historial de Pagos
$stmt = $db->prepare("SELECT * FROM payroll_payments WHERE user_id = ? ORDER BY payment_date DESC LIMIT 20");
$stmt->execute([$userId]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Deudas Pendientes
$debts = $creditManager->getPendingEmployeeDebts($userId);
$totalDebt = 0;
foreach ($debts as $d)
    $totalDebt += ($d['amount'] - $d['paid_amount']);


require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-4 mb-5">
    <div class="row">
        <!-- PERFIL RESUMEN -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-primary">
                <div class="card-body text-center">
                    <img src="../uploads/profile_pics/<?= htmlspecialchars($userData['profile_pic'] ?? 'default.jpg') ?>"
                        class="rounded-circle mb-3" style="width: 100px; height: 100px; object-fit: cover;">
                    <h4><?= htmlspecialchars($userData['name']) ?></h4>
                    <span class="badge bg-secondary mb-2"><?= htmlspecialchars($userData['job_role']) ?></span>

                    <hr>
                    <div class="d-flex justify-content-between px-3">
                        <span>Salario Base:</span>
                        <span
                            class="fw-bold text-success">$<?= number_format($userData['salary_amount'] ?? 0, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between px-3 mt-2">
                        <span>Frecuencia:</span>
                        <span class="text-muted"><?= ucfirst($userData['salary_frequency'] ?? 'Monthly') ?></span>
                    </div>
                </div>
            </div>

            <!-- TARJETA DE DEUDA -->
            <div class="card shadow-sm mt-3 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fa fa-hand-holding-usd me-2"></i> Adelantos / Deudas</h5>
                </div>
                <div class="card-body text-center">
                    <h2 class="text-danger fw-bold">$<?= number_format($totalDebt, 2) ?></h2>
                    <p class="text-muted small">Se descontará de tus próximos pagos.</p>
                </div>
                <?php if (!empty($debts)): ?>
                    <ul class="list-group list-group-flush small text-start">
                        <?php foreach ($debts as $d): ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <span><?= htmlspecialchars($d['notes'] ?? 'Adelanto') ?></span>
                                <span
                                    class="fw-bold text-danger">$<?= number_format($d['amount'] - $d['paid_amount'], 2) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- HISTORIAL DE PAGOS -->
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fa fa-money-check-alt me-2"></i> Mis Recibos de Nómina</h4>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Periodo</th>
                                    <th>Bruto</th>
                                    <th>Deduc.</th>
                                    <th>Neto Recibido</th>
                                    <th>Notas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payments)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Aún no tienes pagos registrados.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($payments as $p):
                                        $neto = $p['amount'] - ($p['deductions_amount'] ?? 0);
                                        ?>
                                        <tr>
                                            <td><?= date('d M, Y', strtotime($p['payment_date'])) ?></td>
                                            <td class="small text-muted">
                                                <?= $p['period_start'] ? date('d/m', strtotime($p['period_start'])) . ' - ' . date('d/m', strtotime($p['period_end'])) : '-' ?>
                                            </td>
                                            <td>$<?= number_format($p['amount'], 2) ?></td>
                                            <td class="text-danger">
                                                <?php if ($p['deductions_amount'] > 0): ?>
                                                    - $<?= number_format($p['deductions_amount'], 2) ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td class="fw-bold text-success">$<?= number_format($neto, 2) ?></td>
                                            <td class="small text-muted fst-italic"><?= htmlspecialchars($p['notes']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>