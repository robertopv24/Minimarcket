<?php
session_start();
require_once '../templates/autoload.php';
require_once '../templates/pos_check.php'; // SEGURIDAD POS

// 1. Validar Sesión de Caja
$userId = $_SESSION['user_id'] ?? null;
if (!$userId || !$cashRegisterManager->hasOpenSession($userId)) {
    header("Location: apertura_caja.php");
    exit;
}

// 2. Obtener datos de la compra (del carrito o de una orden pendiente)
$orderId = $_GET['order_id'] ?? null;
$cartItems = [];

if ($orderId) {
    // Si viene un order_id, cargamos los ítems de esa orden
    $cartItems = $orderManager->getOrderItems($orderId);
    $orderData = $orderManager->getOrderById($orderId);
    $totalUsd = $orderData['total_price'];

    // Simular estructura de carrito para compatibilidad con el resto de la página
    foreach ($cartItems as &$item) {
        $item['total_price'] = $item['price'] * $item['quantity'];
    }
} else {
    // Si no hay order_id, usamos el carrito normal
    $cartItems = $cartManager->getCart($userId);
    if (empty($cartItems)) {
        header("Location: tienda.php");
        exit;
    }
    $totals = $cartManager->calculateTotal($cartItems);
    $totalUsd = $totals['total_usd'];
}

$rate = $config->get('exchange_rate');
$methods = $transactionManager->getPaymentMethods();

// Obtener datos del cliente/empleado de la sesión para el modal de crédito
$sessionClientId = $_SESSION['pos_client_id'] ?? null;
$sessionClientData = null;
if ($sessionClientId) {
    $sessionClientData = $creditManager->getClientById($sessionClientId);
}

$sessionEmployeeId = $_SESSION['pos_employee_id'] ?? null;
$sessionEmployeeData = null;
if ($sessionEmployeeId) {
    $sessionEmployeeData = $userManager->getUserById($sessionEmployeeId);
}

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-4 mb-5">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fa fa-list-alt me-2"></i>Resumen</h5>
                </div>
                <div class="list-group list-group-flush shadow-sm">
                    <?php foreach ($cartItems as $item):
                        $cId = $item['id'];
                        $isCombo = ($item['product_type'] == 'compound');
                        $groupedMods = $item['modifiers_grouped'] ?? [];
                        ?>
                        <div class="list-group-item p-0 border-0 mb-2 shadow-sm rounded overflow-hidden">
                            <div class="bg-light px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-primary small">
                                    <i class="fa <?= $isCombo ? 'fa-cubes' : 'fa-tag' ?> me-1"></i>
                                    <?= htmlspecialchars($item['name']) ?>
                                </span>
                                <span class="badge bg-white text-dark border fw-bold">x<?= $item['quantity'] ?></span>
                            </div>

                            <div class="p-2">
                                <?php
                                if ($isCombo) {
                                    $components = $productManager->getProductComponents($item['product_id']);
                                    $idx = 0;
                                    foreach ($components as $comp) {
                                        $qty = intval($comp['quantity']);

                                        // Obtener nombre del sub-ítem
                                        if ($comp['component_type'] == 'product') {
                                            $subP = $productManager->getProductById($comp['component_id']);
                                            $subName = $subP['name'];
                                        } elseif ($comp['component_type'] == 'manufactured') {
                                            $stmtMan = $db->prepare("SELECT name FROM manufactured_products WHERE id = ?");
                                            $stmtMan->execute([$comp['component_id']]);
                                            $subName = $stmtMan->fetchColumn() ?: 'Item Cocina';
                                        } else {
                                            $subName = 'Ingrediente';
                                        }

                                        for ($i = 0; $i < $qty; $i++) {
                                            $myMods = $groupedMods[$idx] ?? ['is_takeaway' => 0, 'desc' => []];
                                            $icon = ($myMods['is_takeaway'] == 1) ? '🥡' : '🍽️';
                                            ?>
                                            <div class="ps-2 py-1 mb-1 border-start border-3 <?= ($myMods['is_takeaway'] == 1) ? 'border-warning' : 'border-info' ?>"
                                                style="font-size: 0.8rem;">
                                                <div class="d-flex justify-content-between">
                                                    <span><strong><?= $icon ?>                 <?= htmlspecialchars($subName) ?></strong></span>
                                                </div>
                                                <?php if (!empty($myMods['desc'])): ?>
                                                    <div class="text-muted small ps-2 mt-1">
                                                        <?php foreach ($myMods['desc'] as $d): ?>
                                                            <div class="lh-1 mb-1">• <?= htmlspecialchars($d) ?></div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <?php
                                            $idx++;
                                        }
                                    }
                                } else {
                                    // Simple Product
                                    $myMods = $groupedMods[0] ?? ['is_takeaway' => 0, 'desc' => []];
                                    $icon = ($myMods['is_takeaway'] == 1) ? '🥡' : '🍽️';
                                    ?>
                                    <div class="ps-2 py-1 border-start border-3 <?= ($myMods['is_takeaway'] == 1) ? 'border-warning' : 'border-info' ?>"
                                        style="font-size: 0.8rem;">
                                        <?php if (!empty($myMods['desc'])): ?>
                                            <div class="text-muted small">
                                                <?php foreach ($myMods['desc'] as $d): ?>
                                                    <div class="lh-1 mb-1">• <?= htmlspecialchars($d) ?></div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-muted small fst-italic">
                                                <?= ($myMods['is_takeaway'] == 1) ? 'Orden para llevar' : 'Sin modificaciones' ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                            <div class="bg-light px-3 py-1 text-end border-top">
                                <span class="fw-bold text-success small">Subtotal:
                                    $<?= number_format($item['total_price'], 2) ?></span>
                            </div>
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

                        <input type="hidden" name="order_id" value="<?= $orderId ?>">

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="customer_name"
                                    placeholder="Nota (Opcional)">
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="shipping_address"
                                    value="<?= htmlspecialchars($orderId ? ($orderData['shipping_address'] ?? '') : ($_SESSION['pos_client_name'] ?? '')) ?>"
                                    placeholder="Nombre Del Cliente" <?= $orderId ? 'readonly' : '' ?>>
                            </div>
                        </div>

                        <hr>

                        <h6 class="text-primary mb-3">Ingrese Montos Recibidos:</h6>
                        <div class="row g-3">
                            <?php foreach ($methods as $method):
                                $isPagoMovil = (strpos(strtolower($method['name']), 'pago móvil') !== false || strpos(strtolower($method['name']), 'pagomovil') !== false);
                                $isZelle = (strpos(strtolower($method['name']), 'zelle') !== false);
                                ?>
                                <div class="col-md-6">
                                    <div class="card p-2 border-0 bg-light-subtle shadow-sm mb-2">
                                        <div class="input-group">
                                            <span class="input-group-text w-50 small" style="font-size: 0.85rem;">
                                                <?= $method['name'] ?>
                                            </span>
                                            <input type="number" step="0.01"
                                                class="form-control payment-input fw-bold text-end"
                                                name="payments[<?= $method['id'] ?>]"
                                                data-currency="<?= $method['currency'] ?>"
                                                data-method-name="<?= htmlspecialchars($method['name']) ?>"
                                                placeholder="0.00">
                                            <span class="input-group-text"><?= $method['currency'] ?></span>
                                        </div>

                                        <!-- Detalles adicionales para Pago Móvil o Zelle -->
                                        <div class="mt-2 extra-details" id="details-<?= $method['id'] ?>"
                                            style="display:none;">
                                            <?php if ($isPagoMovil): ?>
                                                <input type="text" class="form-control form-control-sm"
                                                    name="payment_details[<?= $method['id'] ?>][reference]"
                                                    placeholder="Número de Movimiento (# Referencia)" data-required="true">
                                            <?php elseif ($isZelle): ?>
                                                <input type="text" class="form-control form-control-sm mb-1"
                                                    name="payment_details[<?= $method['id'] ?>][reference]"
                                                    placeholder="Código de Confirmación">
                                                <input type="text" class="form-control form-control-sm"
                                                    name="payment_details[<?= $method['id'] ?>][sender]"
                                                    placeholder="Nombre del Remitente" data-required="true">
                                            <?php endif; ?>
                                        </div>
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
                                    <div id="remainUsd" class="fs-4 fw-bold text-danger">
                                        $<?= number_format($totalUsd, 2) ?></div>
                                    <div id="remainVes" class="small text-danger">
                                        <?= number_format($totalUsd * $rate, 2) ?> Bs
                                    </div>
                                </div>

                                <div class="col-md-4" id="colChange" style="opacity: 0.5;">
                                    <small class="text-muted d-block text-uppercase fw-bold">Su Cambio</small>
                                    <div id="changeUsd" class="fs-4 fw-bold text-success">$0.00</div>
                                    <div id="changeVes" class="small text-success fw-bold">0.00 Bs</div>
                                </div>
                            </div>
                        </div>

                        <div id="changeMethodContainer"
                            class="mt-3 p-3 bg-warning bg-opacity-10 border border-warning rounded"
                            style="display:none;">
                            <label class="form-label fw-bold text-dark"><i class="fa fa-hand-holding-usd"></i> ¿Cómo
                                entregas el vuelto?</label>
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
                            <div class="form-text text-muted">El sistema registrará la salida de dinero de esta cuenta.
                            </div>
                        </div>

                        <!-- SECCIÓN CRÉDITO Y BENEFICIOS -->
                        <div class="mt-4">
                            <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal"
                                data-bs-target="#modalCredit">
                                <i class="fa fa-handshake me-2"></i> Procesar Crédito o Beneficio
                            </button>
                        </div>

                        <!-- Inputs Ocultos para Crédito -->
                        <input type="hidden" name="is_credit" id="is_credit" value="0">
                        <input type="hidden" name="credit_client_id" id="credit_client_id">
                        <input type="hidden" name="credit_employee_id" id="credit_employee_id">
                        <input type="hidden" name="credit_type" id="credit_type" value="">
                        <!-- 'client_credit', 'employee_credit', 'benefit' -->
                        <input type="hidden" name="admin_password" id="admin_password_input">

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

<!-- MODAL CRÉDITOS -->
<div class="modal fade" id="modalCredit" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fa fa-file-invoice-dollar"></i> Crédito / Beneficio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs mb-3" id="creditTabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#tabClient"
                            onclick="setCreditType('client_credit')">
                            <i class="fa fa-user"></i> Cliente Externo
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tabEmployee"
                            onclick="setCreditType('employee_credit')">
                            <i class="fa fa-id-badge"></i> Empleado (Nómina)
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Tab Cliente -->
                    <div class="tab-pane fade show active" id="tabClient">
                        <div class="mb-3 position-relative">
                            <label class="form-label">Buscar Cliente:</label>
                            <input type="text" class="form-control" id="searchClientInput"
                                placeholder="Nombre o Cédula...">
                            <div class="form-text">Escriba al menos 2 caracteres y seleccione de la lista</div>
                            <div class="list-group mt-2" id="clientResults"
                                style="position: absolute; z-index: 1050; max-height: 200px; overflow-y: auto; display: none; width: 100%; box-shadow: 0 4px 6px rgba(0,0,0,0.3);">
                            </div>
                        </div>
                        <div id="selectedClientInfo" class="alert alert-info <?= $sessionClientData ? '' : 'd-none' ?>">
                            <strong>Cliente:</strong> <span
                                id="selClientName"><?= $sessionClientData ? htmlspecialchars($sessionClientData['name']) : '' ?></span><br>
                            <small>Límite: $<span
                                    id="selClientLimit"><?= $sessionClientData ? number_format($sessionClientData['credit_limit'], 2, '.', '') : '0.00' ?></span>
                                | Deuda: $<span
                                    id="selClientDebt"><?= $sessionClientData ? number_format($sessionClientData['current_debt'], 2, '.', '') : '0.00' ?></span></small>
                        </div>
                    </div>

                    <!-- Tab Empleado -->
                    <div class="tab-pane fade" id="tabEmployee">
                        <div class="mb-3 position-relative">
                            <label class="form-label">Buscar Empleado:</label>
                            <input type="text" class="form-control" id="searchEmpInput" placeholder="Nombre...">
                            <div class="form-text">Escriba al menos 2 caracteres y seleccione de la lista</div>
                            <div class="list-group mt-2" id="empResults"
                                style="position: absolute; z-index: 1050; max-height: 200px; overflow-y: auto; display: none; width: 100%; box-shadow: 0 4px 6px rgba(0,0,0,0.3);">
                            </div>
                        </div>
                        <div id="selectedEmpInfo"
                            class="alert alert-warning border-3 <?= $sessionEmployeeData ? '' : 'd-none' ?>">
                            <strong>Empleado:</strong> <span
                                id="selEmpName"><?= $sessionEmployeeData ? htmlspecialchars($sessionEmployeeData['name']) : '' ?></span><br>
                            <small class="text-muted"><i class="fa fa-briefcase"></i> Rol: <span
                                    id="selEmpRole"><?= $sessionEmployeeData ? htmlspecialchars($sessionEmployeeData['job_role']) : '' ?></span></small>
                        </div>

                        <div class="mb-3 p-3 rounded-3 border-2 border shadow-sm transition-all" id="benefitContainer"
                            style="background-color: #fff; border: 1px solid #dee2e6;">
                            <div class="form-check form-switch d-flex justify-content-between align-items-center p-0">
                                <div>
                                    <label class="form-check-label fw-bold h5 mb-0" for="chkBenefit">
                                        <i class="fa fa-gift text-primary"></i> Es Beneficio de Empresa
                                    </label>
                                    <div class="form-text mt-1 text-muted">La cuenta se registrará como Gasto Operativo
                                        (Sin cobro al empleado).</div>
                                </div>
                                <input class="form-check-input ms-3" type="checkbox" id="chkBenefit"
                                    style="width: 3.5em; height: 1.75em;" onchange="toggleBenefit(this)">
                            </div>
                        </div>
                    </div>
                </div>

                <hr>
                <!-- AUTORIZACIÓN ADMIN -->
                <div class="bg-light p-3 rounded border">
                    <label class="form-label fw-bold text-danger"><i class="fa fa-user-shield"></i> Autorización de
                        Supervisor</label>
                    <input type="password" class="form-control" id="modalAdminPass"
                        placeholder="Ingrese Contraseña Admin">
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="applyCredit()">Aplicar Cargo</button>
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

    // Credit Logic Vars
    let selectedClientId = <?= $sessionClientId ?: 'null' ?>;
    let selectedEmpId = <?= $sessionEmployeeId ?: 'null' ?>;
    let currentCreditType = selectedEmpId ? 'employee_credit' : 'client_credit';

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
        let diff = totalOrderUsd - paidUsd;
        let epsilon = 0.001;
        let isCredit = document.getElementById('is_credit').value == '1';

        // Si es credito, forzamos "Pagado" visualmente
        if (isCredit) {
            paidUsd = totalOrderUsd;
            diff = 0;
        }

        // 3. Actualizar Visuales (Pagado)
        document.getElementById('paidUsd').textContent = '$' + paidUsd.toFixed(2);
        document.getElementById('paidVes').textContent = (paidUsd * rate).toLocaleString('es-VE', { minimumFractionDigits: 2 }) + ' Bs';

        // 4. Lógica de Estados
        if (diff > epsilon && !isCredit) {
            // AÚN FALTA DINERO
            document.getElementById('remainUsd').textContent = '$' + diff.toFixed(2);
            document.getElementById('remainVes').textContent = (diff * rate).toLocaleString('es-VE', { minimumFractionDigits: 2 }) + ' Bs';

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
            // PAGO COMPLETO O SOBRANTE (VUELTO) O CRÉDITO
            let change = Math.abs(diff);

            document.getElementById('remainUsd').textContent = "$0.00";
            document.getElementById('remainVes').textContent = "0.00 Bs";

            document.getElementById('changeUsd').textContent = '$' + change.toFixed(2);
            document.getElementById('changeVes').textContent = (change * rate).toLocaleString('es-VE', { minimumFractionDigits: 2 }) + ' Bs';

            // Estilos
            document.getElementById('colRemaining').style.opacity = "0.3";
            document.getElementById('colChange').style.opacity = "1";

            // Activar botón
            btnSubmit.disabled = false;

            if (isCredit) {
                btnSubmit.className = "btn btn-info btn-lg py-3 shadow text-white";
                let typeLabel = (document.getElementById('chkBenefit').checked && currentCreditType === 'employee_credit') ? 'BENEFICIO' : 'CRÉDITO';
                btnSubmit.innerHTML = `<i class="fa fa-file-signature me-2"></i> <strong>CONFIRMAR ${typeLabel}</strong>`;
                divChangeMethod.style.display = "none";
                selectChangeMethod.required = false;
            } else {
                btnSubmit.className = "btn btn-success btn-lg py-3 shadow";
                btnSubmit.innerHTML = '<i class="fa fa-check-circle me-2"></i> <strong>CONFIRMAR VENTA</strong>';

                // Lógica de Vuelto
                if (change > 0.01) {
                    divChangeMethod.style.display = "block";
                    selectChangeMethod.required = true;
                } else {
                    divChangeMethod.style.display = "none";
                    selectChangeMethod.required = false;
                }
            }
        }
    }

    inputs.forEach(input => {
        input.addEventListener('input', () => {
            calculate();
            // Mostrar/ocultar campos de referencia según si hay monto
            const methodId = input.name.match(/payments\[(\d+)\]/)?.[1];
            if (!methodId) return;
            const detailsDiv = document.getElementById('details-' + methodId);
            if (!detailsDiv) return;
            const val = parseFloat(input.value) || 0;
            if (val > 0) {
                detailsDiv.style.display = 'block';
            } else {
                detailsDiv.style.display = 'none';
                detailsDiv.querySelectorAll('input').forEach(i => i.value = '');
            }
        });
        input.addEventListener('keyup', calculate);
    });

    // --- CREDIT MODAL LOGIC ---

    function setCreditType(type) {
        currentCreditType = type;
        // Reset selections logic if needed
    }

    function toggleBenefit(chk) {
        const container = document.getElementById('benefitContainer');
        if (chk.checked) {
            currentCreditType = 'benefit';
            container.style.backgroundColor = '#e7f1ff';
            container.style.borderColor = '#0d6efd';
            container.classList.add('animated-pulse');
        } else {
            currentCreditType = 'employee_credit';
            container.style.backgroundColor = '#fff';
            container.style.borderColor = '#dee2e6';
            container.classList.remove('animated-pulse');
        }
    }

    // Client Search
    const searchClientInput = document.getElementById('searchClientInput');
    const clientResultsDiv = document.getElementById('clientResults');
    const selectedClientInfo = document.getElementById('selectedClientInfo');

    searchClientInput.addEventListener('input', function () {
        // Clear selection when user types
        selectedClientId = null;
        selectedClientInfo.classList.add('d-none');

        let q = this.value.trim();
        if (q.length < 2) {
            clientResultsDiv.style.display = 'none';
            return;
        }

        fetch('ajax/search_clients.php?q=' + encodeURIComponent(q), {
            credentials: 'same-origin'
        })
            .then(r => {
                console.log('Response status:', r.status);
                if (!r.ok) {
                    throw new Error('HTTP error ' + r.status);
                }
                return r.json();
            })
            .then(data => {
                console.log('Client search results:', data);
                let html = '';
                if (data.error) {
                    html = '<div class="list-group-item text-danger">Error: ' + data.error + '</div>';
                } else if (data.length === 0) {
                    html = '<div class="list-group-item text-muted">No se encontraron clientes</div>';
                } else {
                    data.forEach(c => {
                        const escapedName = c.name.replace(/'/g, "\\'");
                        html += `<a href="#" class="list-group-item list-group-item-action" style="cursor: pointer;" onclick="event.preventDefault(); selectClient(${c.id}, '${escapedName}', ${c.credit_limit}, ${c.current_debt})">
                            <strong>${c.name}</strong><br>
                            <small class="text-muted">${c.document_id || 'Sin documento'} | Límite: $${c.credit_limit} | Deuda: $${c.current_debt}</small>
                        </a>`;
                    });
                }
                clientResultsDiv.innerHTML = html;
                clientResultsDiv.style.display = 'block';
            })
            .catch(err => {
                console.error('Error searching clients:', err);
                clientResultsDiv.innerHTML = '<div class="list-group-item text-danger">Error: ' + err.message + '</div>';
                clientResultsDiv.style.display = 'block';
            });
    });

    window.selectClient = function (id, name, limit, debt) {
        selectedClientId = id;
        document.getElementById('selClientName').innerText = name;
        document.getElementById('selClientLimit').innerText = limit;
        document.getElementById('selClientDebt').innerText = debt;

        selectedClientInfo.classList.remove('d-none');
        clientResultsDiv.style.display = 'none';
        searchClientInput.value = name;
        searchClientInput.classList.add('is-valid');
        searchClientInput.classList.remove('is-invalid');
    };

    // Employee Search
    const searchEmpInput = document.getElementById('searchEmpInput');
    const empResultsDiv = document.getElementById('empResults');
    const selectedEmpInfo = document.getElementById('selectedEmpInfo');

    searchEmpInput.addEventListener('input', function () {
        // Clear selection when user types
        selectedEmpId = null;
        selectedEmpInfo.classList.add('d-none');

        let q = this.value.trim();
        if (q.length < 2) {
            empResultsDiv.style.display = 'none';
            return;
        }

        fetch('ajax/search_employees.php?q=' + encodeURIComponent(q), {
            credentials: 'same-origin'
        })
            .then(r => r.json())
            .then(data => {
                let html = '';
                if (data.length === 0) {
                    html = '<div class="list-group-item text-muted">No se encontraron empleados</div>';
                } else {
                    data.forEach(u => {
                        const escapedName = u.name.replace(/'/g, "\\'");
                        const escapedRole = (u.job_role || '').replace(/'/g, "\\'");
                        html += `<a href="#" class="list-group-item list-group-item-action" style="cursor: pointer;" onclick="event.preventDefault(); selectEmp(${u.id}, '${escapedName}', '${escapedRole}')">
                            <strong>${u.name}</strong><br>
                            <small class="text-muted">Rol: ${u.job_role || 'N/A'}</small>
                        </a>`;
                    });
                }
                empResultsDiv.innerHTML = html;
                empResultsDiv.style.display = 'block';
            })
            .catch(err => {
                console.error('Error searching employees:', err);
                empResultsDiv.innerHTML = '<div class="list-group-item text-danger">Error al buscar</div>';
                empResultsDiv.style.display = 'block';
            });
    });

    window.selectEmp = function (id, name, role) {
        selectedEmpId = id;
        document.getElementById('selEmpName').innerText = name;
        document.getElementById('selEmpRole').innerText = role;

        selectedEmpInfo.classList.remove('d-none');
        empResultsDiv.style.display = 'none';
        searchEmpInput.value = name;
        searchEmpInput.classList.add('is-valid');
        searchEmpInput.classList.remove('is-invalid');
    };

    function applyCredit() {
        const pass = document.getElementById('modalAdminPass').value;
        if (!pass) {
            alert("Debe ingresar la contraseña de Administrador.");
            return;
        }

        if (currentCreditType === 'client_credit') {
            if (!selectedClientId) {
                alert("Debe seleccionar un cliente de la lista de resultados.");
                searchClientInput.classList.add('is-invalid');
                searchClientInput.focus();
                return;
            }

            // VALIDACIÓN DE LÍMITE
            const limit = parseFloat(document.getElementById('selClientLimit').innerText) || 0;
            const debt = parseFloat(document.getElementById('selClientDebt').innerText) || 0;
            const available = limit - debt;

            if (limit > 0 && totalOrderUsd > (available + 0.01)) {
                alert(`⛔ CRÉDITO DENEGADO: El total del pedido ($${totalOrderUsd.toFixed(2)}) excede el crédito disponible ($${available.toFixed(2)}).`);
                return;
            }
        }
        if ((currentCreditType === 'employee_credit' || currentCreditType === 'benefit') && !selectedEmpId) {
            alert("Debe seleccionar un empleado de la lista de resultados.");
            searchEmpInput.classList.add('is-invalid');
            searchEmpInput.focus();
            return;
        }

        // Fill Hidden Fields
        document.getElementById('is_credit').value = '1';
        document.getElementById('credit_client_id').value = selectedClientId || '';
        document.getElementById('credit_employee_id').value = selectedEmpId || '';
        document.getElementById('credit_type').value = currentCreditType;
        document.getElementById('admin_password_input').value = pass;

        // Hide Modal
        var myModalEl = document.getElementById('modalCredit');
        var modal = bootstrap.Modal.getInstance(myModalEl);
        modal.hide();

        // Disable Payment Inputs to avoid confusion
        inputs.forEach(i => i.disabled = true);

        // Recalculate to update button
        calculate();

        // Auto-scroll to button
        btnSubmit.scrollIntoView();
        alert("Modo Crédito activado. Presione CONFIRMAR para finalizar.");
    }

    // Validación antes de enviar
    document.getElementById('checkoutForm').addEventListener('submit', function (e) {
        if (btnSubmit.disabled) {
            e.preventDefault();
            return false;
        }

        // Validar campos obligatorios de Pago Móvil y Zelle
        let isValid = true;
        document.querySelectorAll('.extra-details:visible input[data-required="true"]').forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('is-invalid');
                $(field).effect('shake');
            } else {
                field.classList.remove('is-invalid');
            }
        });

        if (!isValid) {
            alert("⚠️ Por favor, complete la información de referencia o remitente para los pagos correspondientes.");
            e.preventDefault();
            return false;
        }

        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i> Procesando...';
    });
    // Si ya hay empleado en sesión, activar el tab de empleado y asignar tipo
    if (selectedEmpId) {
        try {
            var empTabEl = document.querySelector('#creditTabs a[href="#tabEmployee"]');
            var empTab = new bootstrap.Tab(empTabEl);
            empTab.show();
            currentCreditType = 'employee_credit';
        } catch (e) { console.error("Error activating emp tab:", e); }
    }
</script>

<?php require_once '../templates/footer.php'; ?>