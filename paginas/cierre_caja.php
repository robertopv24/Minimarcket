<?php
// session_start();
require_once '../templates/autoload.php';
require_once '../templates/pos_check.php'; // SEGURIDAD POS

use Minimarcket\Core\Container;
use Minimarcket\Modules\Finance\Services\CashRegisterService;

$container = Container::getInstance();
$cashRegisterService = $container->get(CashRegisterService::class);

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header("Location: login.php");
    exit;
}

// Obtener datos del turno
$report = $cashRegisterService->getSessionReport($userId);

// Si no hay reporte, es que no tiene caja abierta
if (!$report) {
    header("Location: tienda.php");
    exit;
}

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $countedUsd = $_POST['counted_usd'];
    $countedVes = $_POST['counted_ves'];

    $res = $cashRegisterService->closeRegister($userId, $countedUsd, $countedVes);

    if ($res['status']) {
        echo "<script>alert('✅ Caja cerrada correctamente. Turno finalizado.'); window.location='logout.php';</script>";
        exit;
    } else {
        $mensaje = $res['message'];
    }
}

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-4">
                <div
                    class="card-header bg-danger text-white d-flex justify-content-between align-items-center py-3 px-4">
                    <h3 class="mb-0 fw-bold"><i class="fa fa-stop-circle me-2"></i>Cierre de Turno</h3>
                    <span class="badge bg-white text-danger fw-bold px-3 py-2 rounded-pill shadow-sm">
                        <i class="far fa-clock me-1"></i> <?= date('h:i A', strtotime($report['opened_at'])) ?>
                    </span>
                </div>

                <div class="card-body p-4 bg-white">
                    <?php if ($mensaje): ?>
                        <div class="alert alert-danger shadow-sm border-danger d-flex align-items-center mb-4">
                            <i class="fa fa-exclamation-triangle me-2 fs-4"></i>
                            <div><?= htmlspecialchars($mensaje) ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-light border border-secondary border-opacity-10 rounded-3 mb-4">
                        <h5 class="text-secondary fw-bold mb-3 d-flex align-items-center">
                            <i class="fa fa-cash-register me-2"></i> El Sistema Espera:
                        </h5>
                        <div class="row text-center g-0">
                            <div class="col-6 border-end">
                                <h2 class="fw-bold text-success mb-0">$<?= number_format($report['expected_usd'], 2) ?>
                                </h2>
                                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem;">Efectivo
                                    USD</small>
                            </div>
                            <div class="col-6">
                                <h2 class="fw-bold text-primary mb-0"><?= number_format($report['expected_ves'], 2) ?>
                                    Bs</h2>
                                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem;">Efectivo
                                    VES</small>
                            </div>
                        </div>
                    </div>

                    <form method="POST"
                        onsubmit="return confirm('¿Estás seguro de cerrar la caja? Esta acción es irreversible.');">
                        <h5 class="mb-3 fw-bold text-dark"><i class="fa fa-money-bill-wave me-2 text-warning"></i>Arqueo
                            de Caja (Conteo Físico)</h5>
                        <p class="text-muted small mb-4">Por favor, cuenta el dinero físico en la gaveta e ingrésalo
                            abajo para verificar diferencias.</p>

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary">Total contado en USD ($)</label>
                                <div class="input-group input-group-lg shadow-sm">
                                    <span class="input-group-text bg-success text-white border-0"><i
                                            class="fa fa-dollar-sign"></i></span>
                                    <input type="number" name="counted_usd"
                                        class="form-control border-0 bg-light fw-bold text-dark" step="0.01" required
                                        placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary">Total contado en VES (Bs)</label>
                                <div class="input-group input-group-lg shadow-sm">
                                    <span class="input-group-text bg-primary text-white border-0">Bs</span>
                                    <input type="number" name="counted_ves"
                                        class="form-control border-0 bg-light fw-bold text-dark" step="0.01" required
                                        placeholder="0.00">
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-3 d-md-flex justify-content-md-end">
                            <a href="tienda.php" class="btn btn-outline-secondary btn-lg px-4 rounded-pill">
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-danger btn-lg px-5 rounded-pill shadow hover-red">
                                <i class="fa fa-lock me-2"></i> Cerrar Caja Definitivamente
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-light border-0 py-3">
                    <h5 class="mb-0 fw-bold text-secondary"><i class="fa fa-history me-2"></i>Movimientos del Turno</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="ps-4">Hora</th>
                                    <th>Tipo</th>
                                    <th>Monto</th>
                                    <th>Método</th>
                                    <th class="pe-4">Descripción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report['movements'] as $mov): ?>
                                    <tr>
                                        <td class="ps-4 text-muted small"><?= date('H:i', strtotime($mov['created_at'])) ?>
                                        </td>
                                        <td>
                                            <?php if ($mov['type'] == 'income'): ?>
                                                <span
                                                    class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">Ingreso</span>
                                            <?php else: ?>
                                                <span
                                                    class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3">Egreso</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold text-dark">
                                            <?= number_format($mov['amount'], 2) ?>
                                            <span class="small text-muted"><?= $mov['currency'] ?></span>
                                        </td>
                                        <td class="small"><?= $mov['method_name'] ?></td>
                                        <td class="pe-4 small text-secondary"><?= $mov['description'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($report['movements'])): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted fst-italic">Sin movimientos
                                            registrados.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-red:hover {
        background-color: #c82333;
        transform: translateY(-2px);
        transition: all 0.2s;
    }
</style>

<?php require_once '../templates/footer.php'; ?>