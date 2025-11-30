<?php
session_start();
require_once '../templates/autoload.php';

// 1. Validar Sesión de Caja
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
// Omitimos el menú lateral para enfocar en el pago (Estilo POS)
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-5 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fa fa-list-alt me-2"></i>Resumen del Pedido</h5>
                </div>
                <div class="list-group list-group-flush" style="max-height: 400px; overflow-y: auto;">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-start py-3">
                            <div class="me-auto">
                                <div class="fw-bold">
                                    <?= htmlspecialchars($item['name']) ?>
                                    <span class="badge bg-primary rounded-pill ms-1"><?= $item['quantity'] ?></span>
                                </div>

                                <div class="small mt-1">
                                    <?php
                                        // Acceder a la data agrupada que generamos en CartManager
                                        $grouped = $item['modifiers_grouped'] ?? [];

                                        // Si no hay datos agrupados (producto simple sin config), usar el global
                                        if (empty($grouped)) {
                                            $tag = ($item['consumption_type'] == 'takeaway') ? '🥡 Para Llevar' : '🍽️ Comer Aquí';
                                            echo "<span class='text-muted'>$tag</span>";
                                        } else {
                                            // Mostrar desglose granular
                                            foreach ($grouped as $idx => $data) {
                                                $icon = ($data['is_takeaway'] == 1) ? '🥡' : '🍽️';
                                                $text = ($data['is_takeaway'] == 1) ? 'Llevar' : 'Mesa';
                                                $extras = empty($data['desc']) ? '' : ' <span class="text-success">(' . implode(', ', $data['desc']) . ')</span>';
                                                echo "<div class='text-muted' style='font-size:0.85em'>#".($idx+1)." $icon $text $extras</div>";
                                            }
                                        }
                                    ?>
                                </div>
                            </div>
                            <span class="fw-bold">$<?= number_format($item['total_price'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fs-5">Total USD:</span>
                        <span class="fs-4 fw-bold text-success">$<?= number_format($totalUsd, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center text-muted small">
                        <span>Tasa: <?= $rate ?></span>
                        <span class="fw-bold text-dark fs-5"><?= number_format($totalUsd * $rate, 2) ?> Bs</span>
                    </div>
                </div>
            </div>

            <div class="d-grid mt-3">
                <a href="carrito.php" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left me-2"></i> Volver al Carrito
                </a>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card shadow border-primary">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fa fa-cash-register me-2"></i>Procesar Pago</h4>
                </div>
                <div class="card-body">
                    <form id="checkoutForm" action="process_checkout.php" method="POST">

                        <div class="row g-2 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nombre Cliente</label>
                                <input type="text" class="form-control" name="customer_name" placeholder="Consumidor Final" autofocus>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nota / Dirección</label>
                                <input type="text" class="form-control" name="shipping_address" value="Tienda Física">
                            </div>
                        </div>

                        <hr>

                        <h5 class="mb-3 text-primary">Métodos de Pago</h5>
                        <div class="alert alert-info py-2 small">
                            <i class="fa fa-info-circle"></i> Ingrese los montos recibidos en cada moneda. El sistema calculará el cambio.
                        </div>

                        <div class="row g-3">
                            <?php foreach ($methods as $method): ?>
                                <div class="col-md-6">
                                    <label class="form-label d-flex justify-content-between">
                                        <span><?= $method['name'] ?></span>
                                        <span class="badge bg-light text-dark border"><?= $method['currency'] ?></span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><?= $method['currency'] == 'USD' ? '$' : 'Bs' ?></span>
                                        <input type="number" step="0.01" class="form-control payment-input fs-5 fw-bold"
                                               name="payments[<?= $method['id'] ?>]"
                                               data-currency="<?= $method['currency'] ?>"
                                               placeholder="0.00">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-4 p-3 bg-light rounded border">
                            <div class="row text-center">
                                <div class="col-4 border-end">
                                    <small class="text-muted d-block">Total Pagado</small>
                                    <span id="totalPaidDisplay" class="fw-bold fs-5 text-primary">$0.00</span>
                                </div>
                                <div class="col-4 border-end">
                                    <small class="text-muted d-block">Restante</small>
                                    <span id="remainingDisplay" class="fw-bold fs-5 text-danger">$<?= number_format($totalUsd, 2) ?></span>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Cambio (Vuelto)</small>
                                    <span id="changeDisplay" class="fw-bold fs-5 text-success">$0.00</span>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button class="btn btn-success btn-lg py-3" type="submit" id="btnSubmit" disabled>
                                <span class="fs-4"><i class="fa fa-check-circle me-2"></i> COBRAR</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
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

        // Tolerancia de centavos (0.01) para evitar problemas de punto flotante
        if (remaining > 0.01) {
            document.getElementById('remainingDisplay').textContent = '$' + remaining.toFixed(2);
            document.getElementById('remainingDisplay').className = "fw-bold fs-5 text-danger";
            document.getElementById('changeDisplay').textContent = "$0.00";
            btnSubmit.disabled = true;
            btnSubmit.classList.add('btn-secondary');
            btnSubmit.classList.remove('btn-success');
        } else {
            change = Math.abs(remaining);
            document.getElementById('remainingDisplay').textContent = '$0.00';
            document.getElementById('remainingDisplay').className = "fw-bold fs-5 text-muted"; // Gris cuando está pago
            document.getElementById('changeDisplay').textContent = '$' + change.toFixed(2);

            btnSubmit.disabled = false;
            btnSubmit.classList.remove('btn-secondary');
            btnSubmit.classList.add('btn-success');
        }
    }

    inputs.forEach(input => {
        input.addEventListener('input', calculateTotals);
        input.addEventListener('keyup', calculateTotals);
    });
</script>

<?php require_once '../templates/footer.php'; ?>
