<?php
session_start();
require_once '../templates/autoload.php';
require_once '../templates/pos_check.php'; // SEGURIDAD POS

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header("Location: login.php");
    exit;
}

// Obtener datos del turno
$report = $cashRegisterManager->getSessionReport($userId);

// Si no hay reporte, es que no tiene caja abierta
if (!$report) {
    header("Location: tienda.php");
    exit;
}

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $countedUsd = $_POST['counted_usd'];
    $countedVes = $_POST['counted_ves'];

    $res = $cashRegisterManager->closeRegister($userId, $countedUsd, $countedVes);

    if ($res['status']) {
        // Redirigir a logout con mensaje de éxito
        SessionHelper::setFlash('success', '✅ Caja cerrada correctamente. Turno finalizado.');
        header("Location: logout.php");
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
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">🛑 Cierre de Turno</h3>
                    <span>Abierto desde: <?= date('h:i A', strtotime($report['opened_at'])) ?></span>
                </div>

                <div class="card-body">
                    <?php if ($mensaje): ?>
                        <div class="alert alert-danger"><?= $mensaje ?></div>
                    <?php endif; ?>

                    <div class="alert alert-info">
                        <h5 class="text-muted">💰 El Sistema Espera:</h5>
                        <div class="row text-center">
                            <div class="col-6 border-end">
                                <h3 class="fw-bold text-muted">$<?= number_format($report['expected_usd'], 2) ?></h3>
                                <small class="text-muted">Efectivo Dólares</small>
                            </div>
                            <div class="col-6">
                                <h3 class="fw-bold text-muted"><?= number_format($report['expected_ves'], 2) ?> Bs</h3>
                                <small class="text-muted">Efectivo Bolívares</small>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <form method="POST" id="formCierreCaja" onsubmit="return confirmCierre(event)">
                        <h4 class="mb-3">💸 Arqueo de Caja (Conteo Físico)</h4>
                        <p class="text-muted">Por favor, cuenta el dinero físico en la gaveta e ingrésalo abajo.</p>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Total contado en USD ($)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-success text-white">$</span>
                                    <input type="number" name="counted_usd" class="form-control form-control-lg"
                                        step="0.01" required placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Total contado en VES (Bs)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-primary text-white">Bs</span>
                                    <input type="number" name="counted_ves" class="form-control form-control-lg"
                                        step="0.01" required placeholder="0.00">
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-grid gap-2">
                            <button type="submit" class="btn btn-danger btn-lg">
                                <i class="fa fa-lock me-2"></i> Cerrar Caja Definitivamente
                            </button>
                            <a href="tienda.php" class="btn btn-outline-secondary">Cancelar y Volver a Tienda</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-4">
                <h5>📝 Movimientos del Turno</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Tipo</th>
                                <th>Monto</th>
                                <th>Método</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report['movements'] as $mov): ?>
                                <tr>
                                    <td><?= date('H:i', strtotime($mov['created_at'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $mov['type'] == 'income' ? 'success' : 'danger' ?>">
                                            <?= $mov['type'] == 'income' ? 'Ingreso' : 'Egreso' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= number_format($mov['amount'], 2) ?>
                                        <?= $mov['currency'] ?>
                                    </td>
                                    <td><?= $mov['method_name'] ?></td>
                                    <td><small><?= $mov['description'] ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmCierre(event) {
        event.preventDefault(); // Detener envío
        Swal.fire({
            title: '¿Cerrar Caja?',
            text: "Esta acción es irreversible y finalizará tu turno.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, cerrar caja',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('formCierreCaja').submit();
            }
        });
        return false;
    }
</script>

<?php require_once '../templates/footer.php'; ?>