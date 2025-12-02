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
?>

<div class="container mt-4 mb-5">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fa fa-list-alt me-2"></i>Resumen</h5>
                </div>
                <div class="list-group list-group-flush" style="max-height: 400px; overflow-y: auto;">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-start py-2">
                            <div class="me-auto">
                                <div class="fw-bold small"><?= htmlspecialchars($item['name']) ?> x<?= $item['quantity'] ?></div>
                                <?php
                                    $grouped = $item['modifiers_grouped'] ?? [];
                                    if (!empty($grouped)) {
                                        foreach ($grouped as $idx => $data) {
                                            $icon = ($data['is_takeaway'] == 1) ? '🥡' : '🍽️';
                                            echo "<div class='text-muted' style='font-size:0.75em'>$icon #".($idx+1)."</div>";
                                        }
                                    }
                                ?>
                            </div>
                            <span class="fw-bold small">$<?= number_format($item['total_price'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fs-5">Total:</span>
                        <div class="text-end">
                            <div class="fs-4 fw-bold text-success">$<?= number_format($totalUsd, 2) ?></div>
                            <div class="small text-muted"><?= number_format($totalUsd * $rate, 2) ?> Bs</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-grid mt-3">
                <a href="carrito.php" class="btn btn-outline-secondary">⬅ Volver al Carrito</a>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow border-primary">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fa fa-cash-register me-2"></i>Caja</h4>
                </div>
                <div class="card-body">
                    <form id="checkoutForm" action="process_checkout.php" method="POST">

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="customer_name" placeholder="Nombre Cliente (Opcional)">
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="shipping_address" value="Tienda Física" placeholder="Nota/Mesa">
                            </div>
                        </div>

                        <hr>

                        <h6 class="text-primary mb-3">Ingrese Montos Recibidos:</h6>
                        <div class="row g-3">
                            <?php foreach ($methods as $method): ?>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text w-50 small" style="font-size: 0.85rem;">
                                            <?= $method['name'] ?>
                                        </span>
                                        <input type="number" step="0.01" class="form-control payment-input fw-bold text-end"
                                               name="payments[<?= $method['id'] ?>]"
                                               data-currency="<?= $method['currency'] ?>"
                                               placeholder="0.00">
                                        <span class="input-group-text"><?= $method['currency'] ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-4 p-3 rounded border" style="background-color: #f8f9fa;">
                            <div class="row text-center align-items-center">
                                <div class="col-md-4 border-end">
                                    <small class="text-muted d-block text-uppercase fw-bold">Total Recibido</small>
                                    <div id="paidUsd" class="fs-4 fw-bold text-primary">$0.00</div>
                                    <div id="paidVes" class="small text-muted">0.00 Bs</div>
                                </div>

                                <div class="col-md-4 border-end" id="colRemaining">
                                    <small class="text-muted d-block text-uppercase fw-bold">Falta por Pagar</small>
                                    <div id="remainUsd" class="fs-4 fw-bold text-danger">$<?= number_format($totalUsd, 2) ?></div>
                                    <div id="remainVes" class="small text-danger"><?= number_format($totalUsd * $rate, 2) ?> Bs</div>
                                </div>

                                <div class="col-md-4" id="colChange" style="opacity: 0.5;">
                                    <small class="text-muted d-block text-uppercase fw-bold">Su Cambio</small>
                                    <div id="changeUsd" class="fs-4 fw-bold text-success">$0.00</div>
                                    <div id="changeVes" class="small text-success fw-bold">0.00 Bs</div>
                                </div>
                            </div>
                        </div>

                        <div id="changeMethodContainer" class="mt-3 p-3 bg-warning bg-opacity-10 border border-warning rounded" style="display:none;">
                            <label class="form-label fw-bold text-dark"><i class="fa fa-hand-holding-usd"></i> ¿Cómo entregas el vuelto?</label>
                            <select name="change_method_id" class="form-select border-warning">
                                <option value="">Seleccione origen del dinero...</option>
                                <?php foreach ($methods as $m):
                                    // Solo mostramos métodos de Efectivo o Pago Móvil para dar vuelto
                                    // Asumimos que Zelle no se usa para dar vuelto comúnmente, pero lo dejamos abierto.
                                ?>
                                    <option value="<?= $m['id'] ?>">
                                        Entregar en <?= $m['name'] ?> (<?= $m['currency'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text text-muted">El sistema registrará la salida de dinero de esta cuenta.</div>
                        </div>

                        <div class="d-grid mt-4">
                            <button class="btn btn-secondary btn-lg py-3" type="submit" id="btnSubmit" disabled>
                                <i class="fa fa-lock me-2"></i> Complete el Pago
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
    const rate = <?= $rate ?>;
    const inputs = document.querySelectorAll('.payment-input');
    const btnSubmit = document.getElementById('btnSubmit');
    const divChangeMethod = document.getElementById('changeMethodContainer');
    const selectChangeMethod = document.querySelector('select[name="change_method_id"]');

    function calculate() {
        let paidUsd = 0;

        // 1. Sumar Pagos (Convirtiendo todo a USD base)
        inputs.forEach(input => {
            let val = parseFloat(input.value) || 0;
            if (input.dataset.currency === 'VES') {
                paidUsd += (val / rate);
            } else {
                paidUsd += val;
            }
        });

        // 2. Calcular Diferencia
        // Usamos una pequeña tolerancia (epsilon) para errores de flotantes
        let diff = totalOrderUsd - paidUsd;
        let epsilon = 0.001;

        // 3. Actualizar Visuales (Pagado)
        document.getElementById('paidUsd').textContent = '$' + paidUsd.toFixed(2);
        document.getElementById('paidVes').textContent = (paidUsd * rate).toLocaleString('es-VE', {minimumFractionDigits: 2}) + ' Bs';

        // 4. Lógica de Estados
        if (diff > epsilon) {
            // AÚN FALTA DINERO
            document.getElementById('remainUsd').textContent = '$' + diff.toFixed(2);
            document.getElementById('remainVes').textContent = (diff * rate).toLocaleString('es-VE', {minimumFractionDigits: 2}) + ' Bs';

            document.getElementById('changeUsd').textContent = "$0.00";
            document.getElementById('changeVes').textContent = "0.00 Bs";

            // Estilos
            document.getElementById('colRemaining').style.opacity = "1";
            document.getElementById('colChange').style.opacity = "0.3";
            divChangeMethod.style.display = "none";
            selectChangeMethod.required = false;

            // Botón bloqueado
            btnSubmit.disabled = true;
            btnSubmit.className = "btn btn-secondary btn-lg py-3";
            btnSubmit.innerHTML = '<i class="fa fa-lock me-2"></i> Complete el Pago';

        } else {
            // PAGO COMPLETO O SOBRANTE (VUELTO)
            let change = Math.abs(diff);

            document.getElementById('remainUsd').textContent = "$0.00";
            document.getElementById('remainVes').textContent = "0.00 Bs";

            document.getElementById('changeUsd').textContent = '$' + change.toFixed(2);
            document.getElementById('changeVes').textContent = (change * rate).toLocaleString('es-VE', {minimumFractionDigits: 2}) + ' Bs';

            // Estilos
            document.getElementById('colRemaining').style.opacity = "0.3";
            document.getElementById('colChange').style.opacity = "1";

            // Activar botón
            btnSubmit.disabled = false;
            btnSubmit.className = "btn btn-success btn-lg py-3 shadow";
            btnSubmit.innerHTML = '<i class="fa fa-check-circle me-2"></i> <strong>CONFIRMAR VENTA</strong>';

            // Lógica de Vuelto: ¿Hay cambio real? (> 0.01)
            if (change > 0.01) {
                divChangeMethod.style.display = "block";
                selectChangeMethod.required = true; // Obligatorio decir cómo diste el vuelto
            } else {
                divChangeMethod.style.display = "none";
                selectChangeMethod.required = false;
            }
        }
    }

    inputs.forEach(input => {
        input.addEventListener('input', calculate);
        input.addEventListener('keyup', calculate);
    });
</script>

<?php require_once '../templates/footer.php'; ?>
