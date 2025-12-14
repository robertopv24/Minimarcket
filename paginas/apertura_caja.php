<?php
// session_start();
require_once '../templates/autoload.php';

use Minimarcket\Core\Container;
use Minimarcket\Modules\Finance\Services\CashRegisterService;

$container = Container::getInstance();
$cashRegisterService = $container->get(CashRegisterService::class);

$userId = $_SESSION['user_id'] ?? null;

// Si ya tiene caja abierta, lo mandamos a trabajar (Tienda)
if ($userId && $cashRegisterService->hasOpenSession($userId)) {
    header("Location: tienda.php");
    exit;
}

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usd = $_POST['amount_usd'] ?? 0;
    $ves = $_POST['amount_ves'] ?? 0;

    $res = $cashRegisterService->openRegister($userId, $usd, $ves);

    if ($res['status']) {
        header("Location: tienda.php");
        exit;
    } else {
        $mensaje = $res['message'];
    }
}

require_once '../templates/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-primary text-white text-center py-4">
                    <div class="mb-2"><i class="fa fa-cash-register fa-3x opacity-75"></i></div>
                    <h3 class="fw-bold m-0">Apertura de Caja</h3>
                </div>
                <div class="card-body p-4 p-md-5 bg-white">
                    <p class="text-center text-secondary mb-4">Indica el dinero en efectivo inicial para comenzar el
                        turno.</p>

                    <?php if ($mensaje): ?>
                        <div class="alert alert-danger shadow-sm border-danger d-flex align-items-center mb-4">
                            <i class="fa fa-exclamation-circle me-2 fs-5"></i>
                            <div><?= htmlspecialchars($mensaje) ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted small text-uppercase">Fondo en Dólares
                                ($)</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i
                                        class="fa fa-dollar-sign"></i></span>
                                <input type="number" name="amount_usd"
                                    class="form-control border-start-0 ps-0 fw-bold text-primary" step="0.01"
                                    value="0.00" required>
                            </div>
                        </div>
                        <div class="mb-5">
                            <label class="form-label fw-bold text-muted small text-uppercase">Fondo en Bolívares
                                (Bs)</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light border-end-0 text-muted">Bs</span>
                                <input type="number" name="amount_ves"
                                    class="form-control border-start-0 ps-0 fw-bold text-primary" step="0.01"
                                    value="0.00" required>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg py-3 rounded-pill shadow hover-float">
                                <i class="fa fa-lock-open me-2"></i> Abrir Caja y Comenzar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="text-center mt-4">
                <a href="../index.php" class="text-decoration-none text-muted small"><i
                        class="fa fa-arrow-left me-1"></i> Volver al Inicio</a>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-float:hover {
        transform: translateY(-3px);
        transition: transform 0.2s;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15) !important;
    }
</style>