<?php
session_start();
require_once '../templates/autoload.php';

// 1. Seguridad de Caja
$userId = $_SESSION['user_id'] ?? null;
if (!$userId || !$cashRegisterManager->hasOpenSession($userId)) {
    header("Location: apertura_caja.php");
    exit;
}

// 2. Obtener carrito
$cartItems = $cartManager->getCart($userId);
if (empty($cartItems)) {
    header("Location: tienda.php");
    exit;
}

$totals = $cartManager->calculateTotal($cartItems);
$totalUsd = $totals['total_usd'];
$rate = $config->get('exchange_rate');
$methods = $transactionManager->getPaymentMethods();

require_once '../templates/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-4 order-md-2 mb-4">
            <h4 class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-primary">Resumen de Venta</span>
                <span class="badge bg-primary rounded-pill"><?= count($cartItems) ?> ítems</span>
            </h4>
            <ul class="list-group mb-3">
                <li class="list-group-item d-flex justify-content-between lh-sm">
                    <div>
                        <h6 class="my-0 text-muted">Total a Pagar (USD)</h6>
                    </div>
                    <span class="text-success fw-bold fs-4">$<?= number_format($totalUsd, 2) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between lh-sm">
                    <div>
                        <h6 class="my-0 text-muted">En Bolívares</h6>
                        <small class="text-muted">Tasa: <?= $rate ?></small>
                    </div>
                    <span class="text-muted"><?= number_format($totalUsd * $rate, 2) ?> Bs</span>
                </li>
            </ul>

            <div class="card bg-light">
                <div class="card-body">
                    <h5 class="card-title text-center">Estado del Pago</h5>
                    <div class="d-flex text-warning justify-content-between">
                        <span>Pagado:</span>
                        <span id="totalPaidDisplay" class="fw-bold text-primary">$0.00</span>
                    </div>
                    <div class="d-flex text-warning justify-content-between mt-2">
                        <span>Restante:</span>
                        <span id="remainingDisplay" class="fw-bold text-danger">$<?= number_format($totalUsd, 2) ?></span>
                    </div>
                    <div class="d-flex text-warning justify-content-between mt-2 border-top pt-2">
                        <span class="fs-5">Vuelto:</span>
                        <span id="changeDisplay" class="fs-5 fw-bold text-warning">$0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8 order-md-1">
            <h4 class="mb-3">Procesar Pago</h4>
            <form id="checkoutForm" action="process_checkout.php" method="POST">

                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="form-label">Cliente (Opcional)</label>
                        <input type="text" class="form-control" name="customer_name" placeholder="Consumidor Final">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Dirección / Nota</label>
                        <input type="text" class="form-control" name="shipping_address" value="Tienda Física">
                    </div>
                </div>

                <hr class="my-4">

                <h4 class="mb-3">Métodos de Pago</h4>
                <div class="alert alert-info">
                    Ingresa los montos en cada método que use el cliente.
                </div>

                <?php foreach ($methods as $method): ?>
                    <div class="row mb-3 align-items-center">
                        <div class="col-md-4">
                            <label class="form-label mb-0 fw-bold">
                                <?= $method['name'] ?> (<?= $method['currency'] ?>)
                            </label>
                        </div>
                        <div class="col-md-8">
                            <div class="input-group">
                                <span class="input-group-text"><?= $method['currency'] == 'USD' ? '$' : 'Bs' ?></span>
                                <input type="number" step="0.01" class="form-control payment-input"
                                       name="payments[<?= $method['id'] ?>]"
                                       data-currency="<?= $method['currency'] ?>"
                                       placeholder="0.00">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <hr class="my-4">

                <button class="w-100 btn btn-success btn-lg" type="submit" id="btnSubmit" disabled>
                    <i class="fa fa-check-circle me-2"></i> Confirmar y Facturar
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    const totalOrderUsd = <?= $totalUsd ?>;
    const exchangeRate = <?= $rate ?>;
    const inputs = document.querySelectorAll('.payment-input');
    const btnSubmit = document.getElementById('btnSubmit');

    function calculateTotals() {
        let totalPaidUsd = 0;

        inputs.forEach(input => {
            let val = parseFloat(input.value) || 0;
            let currency = input.dataset.currency;

            if (currency === 'VES') {
                totalPaidUsd += (val / exchangeRate);
            } else {
                totalPaidUsd += val;
            }
        });

        // Actualizar UI
        document.getElementById('totalPaidDisplay').textContent = '$' + totalPaidUsd.toFixed(2);

        let remaining = totalOrderUsd - totalPaidUsd;
        let change = 0;

        if (remaining > 0.01) {
            document.getElementById('remainingDisplay').textContent = '$' + remaining.toFixed(2);
            document.getElementById('remainingDisplay').className = "fw-bold text-danger";
            document.getElementById('changeDisplay').textContent = "$0.00";
            btnSubmit.disabled = true; // No dejar pagar si falta dinero
        } else {
            change = Math.abs(remaining);
            document.getElementById('remainingDisplay').textContent = '$0.00';
            document.getElementById('remainingDisplay').className = "fw-bold text-warning";
            document.getElementById('changeDisplay').textContent = '$' + change.toFixed(2);
            btnSubmit.disabled = false; // Habilitar botón
        }
    }

    inputs.forEach(input => {
        input.addEventListener('input', calculateTotals);
    });
</script>

<?php require_once '../templates/footer.php'; ?>
