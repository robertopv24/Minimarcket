<?php
session_start();
require_once '../templates/autoload.php';

// Si ya tiene caja abierta, lo mandamos a trabajar (Tienda)
if (isset($_SESSION['user_id']) && $cashRegisterManager->hasOpenSession($_SESSION['user_id'])) {
    header("Location: tienda.php");
    exit;
}

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usd = $_POST['amount_usd'] ?? 0;
    $ves = $_POST['amount_ves'] ?? 0;
    $res = $cashRegisterManager->openRegister($_SESSION['user_id'], $usd, $ves);

    if ($res['status']) {
        SessionHelper::setFlash('success', 'Caja abierta exitosamente. ¡Buen turno!');
        header("Location: tienda.php");
        exit;
    } else {
        SessionHelper::setFlash('error', $res['message']);
    }
}

require_once '../templates/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h3>🔐 Apertura de Caja</h3>
                </div>
                <div class="card-body">
                    <p class="text-center">Por favor, indica el dinero en efectivo con el que inicias el turno.</p>

                    <?php if ($mensaje): ?>
                        <div class="alert alert-danger"><?= $mensaje ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Fondo en Dólares ($ Efectivo)</label>
                            <input type="number" name="amount_usd" class="form-control" step="0.01" value="0.00"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fondo en Bolívares (Bs Efectivo)</label>
                            <input type="number" name="amount_ves" class="form-control" step="0.01" value="0.00"
                                required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg">Abrir Caja y Comenzar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>