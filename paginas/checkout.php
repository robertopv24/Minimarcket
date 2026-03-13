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
// Si hay una orden siendo editada (flujo: Editar → Tienda → Carrito → Checkout),
// usamos su ID para mostrar abonos previos y datos de delivery.
$orderId = $_GET['order_id'] ?? $_SESSION['pos_editing_order_id'] ?? null;
$cartItems = [];
$orderData = [];

if ($orderId) {
    $orderData = $orderManager->getOrderById($orderId);
}

// Leer ítems del carrito siempre (la edición carga allí; también las nuevas ventas)
$cartItemsRaw = $cartManager->getCart($userId);

if ($orderId && !empty($orderData)) {
    // Si hay orden de referencia, usamos el carrito (que fue cargado desde esa orden)
    // y obtenemos el total directamente del carrito más actualizado
    if (!empty($cartItemsRaw)) {
        $cartItems = $cartItemsRaw;
        $totals = $cartManager->calculateTotal($cartItems);
        $totalUsd = $totals['total_usd'];
    } else {
        // Fallback: cargar ítems desde la orden
        $cartItems = $orderManager->getOrderItems($orderId);
        foreach ($cartItems as &$item) {
            $item['total_price'] = $item['price'] * $item['quantity'];
        }
        $totalUsd = $orderData['total_price'];
    }
} elseif (!empty($cartItemsRaw)) {
    // Nueva venta desde carrito
    $cartItems = $cartItemsRaw;
    $totals = $cartManager->calculateTotal($cartItems);
    $totalUsd = $totals['total_usd'];
    $orderId = null; // Aseguramos que no hay orden de referencia
} else {
    if (!empty($_GET['order_id'])) {
        // Acceso directo a checkout con order_id (ej: "Procesar Pago")
        $cartItems = $orderManager->getOrderItems($orderId);
        foreach ($cartItems as &$item) {
            $item['total_price'] = $item['price'] * $item['quantity'];
        }
        $totalUsd = $orderData['total_price'];
    } else {
        header("Location: tienda.php");
        exit;
    }
}

$rate = $config->get('exchange_rate');
$methods = $transactionManager->getPaymentMethods();

// Obtener datos del cliente/empleado para el proceso de cobro
if ($orderId && !empty($orderData)) {
    $sessionClientId   = $orderData['client_id']   ?? $_SESSION['pos_client_id']   ?? null;
    $sessionEmployeeId = $orderData['employee_id']  ?? $_SESSION['pos_employee_id'] ?? null;
} else {
    $sessionClientId   = $_SESSION['pos_client_id']   ?? null;
    $sessionEmployeeId = $_SESSION['pos_employee_id'] ?? null;
}

$sessionClientData = null;
if ($sessionClientId) {
    $sessionClientData = $creditManager->getClientById($sessionClientId);
}

$sessionEmployeeData = null;
if ($sessionEmployeeId) {
    $sessionEmployeeData = $userManager->getUserById($sessionEmployeeId);
}

// Calcular Saldo Pendiente y Gastos de Envío actuales
$alreadyPaid = 0;
$deliveryFeeInCart = 0;

// Calcular el fee que ya viene en el carrito/orden
foreach ($cartItems as $item) {
    if (isset($item['name']) && $item['name'] === 'Servicio Delivery') {
        $deliveryFeeInCart += (floatval($item['price'] ?? $item['price_usd'] ?? 0) * $item['quantity']);
    }
}

if ($orderId) {
    $alreadyPaid = $transactionManager->getTotalPaidByOrder($orderId);
}

// El Total Base es el total sin el cargo de delivery específico,
// para que el JS pueda sumarlo dinámicamente según la radio button.
$baseTotalUsd = $totalUsd - $deliveryFeeInCart;

// Saldo a Favor del Cliente (Deuda negativa)
$clientBalance = ($sessionClientData && ($sessionClientData['current_debt'] < -0.001)) ? abs($sessionClientData['current_debt']) : 0;
$amountRemaining = max(0, $totalUsd - $alreadyPaid - $clientBalance);

// Delivery Fees from Config
$deliveryBase = floatval($config->get('delivery_base_cost', 0.00));
$feeA = 0; // Tier A is usually Llevar/Pick-up
$feeB = $deliveryBase;
$feeC = $deliveryBase * 2;

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
                        if ($item['name'] === 'Servicio Delivery') continue; // Ocultar del loop de productos
                        $cId = $item['id'];
                        $isCombo = ($item['product_type'] == 'compound');
                        $groupedMods = $item['modifiers_grouped'] ?? [];
                        ?>
                        <div class="list-group-item p-0 border-0 mb-2 shadow-sm rounded overflow-hidden">
                            <div
                                class="bg-dark bg-opacity-50 px-3 py-2 border-bottom border-secondary d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-info small">
                                    <i class="fa <?= $isCombo ? 'fa-cubes' : 'fa-tag' ?> me-1"></i>
                                    <?= htmlspecialchars($item['name']) ?>
                                </span>
                                <span
                                    class="badge bg-secondary text-white border-0 fw-bold">x<?= $item['quantity'] ?></span>
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
                        <span class="fs-5">Subtotal:</span>
                        <div class="text-end">
                            <div class="fs-5 fw-bold text-dark">$<?= number_format($baseTotalUsd, 2) ?></div>
                        </div>
                    </div>
                    <div id="deliveryRow" class="d-flex justify-content-between align-items-center border-top pt-2 mt-2" style="display:none !important;">
                        <span class="fs-6">Cargo Delivery:</span>
                        <div class="text-end">
                            <div id="summaryDeliveryFee" class="fs-6 fw-bold text-primary">$0.00</div>
                        </div>
                    </div>
                    <?php if ($alreadyPaid > 0): ?>
                    <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2">
                        <span class="fs-6 text-muted">Abonado:</span>
                        <div class="text-end">
                            <div class="fs-6 fw-bold text-info">$<?= number_format($alreadyPaid, 2) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2">
                        <span class="fs-4"><?= $alreadyPaid > 0 ? 'Por Pagar:' : 'Total:' ?></span>
                        <div class="text-end">
                            <div id="summaryTotalUsd" class="fs-3 fw-bold text-success">$<?= number_format($amountRemaining + $clientBalance, 2) ?></div>
                            <div id="summaryTotalVes" class="small text-muted"><?= number_format(($amountRemaining + $clientBalance) * $rate, 2) ?> Bs</div>
                        </div>
                    </div>
                    <?php if ($clientBalance > 0): ?>
                    <div id="clientBalanceRow" class="d-flex justify-content-between align-items-center border-top pt-1 mt-1 text-primary">
                        <span class="small fw-bold animate__animated animate__pulse animate__infinite"><i class="fa fa-star me-1"></i> Saldo a Favor Aplicado:</span>
                        <div class="text-end">
                            <div class="small fw-bold">-$<?= number_format($clientBalance, 2) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between align-items-center border-dark border-top pt-2 mt-2">
                        <span class="fs-4 fw-bold">Diferencia a Cobrar:</span>
                        <div class="text-end">
                            <div id="summaryRemainingUsd" class="fs-4 fw-bold text-danger">$<?= number_format($amountRemaining, 2) ?></div>
                            <div id="summaryRemainingVes" class="small text-muted"><?= number_format($amountRemaining * $rate, 2) ?> Bs</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-grid mt-3">
                <a href="carrito.php" class="btn btn-outline-info fw-bold py-2 shadow-sm">
                    <i class="fa fa-arrow-left me-2"></i> Volver al Carrito
                </a>
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
                                <label class="form-label small text-white-50 fw-bold">Nombre del Cliente</label>
                                <input type="text" class="form-control bg-dark text-white border-secondary fw-bold"
                                    name="customer_name" 
                                    value="<?= htmlspecialchars($orderId ? ($orderData['customer_note'] ?? '') : ($_SESSION['pos_client_name'] ?? '')) ?>"
                                    placeholder="Nombre del Cliente">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-white-50 fw-bold">Tipo de Consumo</label>
                                <div class="btn-group w-100" role="group">
                                    <?php 
                                    $ctype = $orderId ? ($orderData['consumption_type'] ?? 'dine_in') : 'dine_in';
                                    ?>
                                    <input type="radio" class="btn-check" name="consumption_type" id="type_dine_in" value="dine_in" <?= $ctype == 'dine_in' ? 'checked' : '' ?> onchange="toggleDeliveryFields()">
                                    <label class="btn btn-outline-info" for="type_dine_in">Local</label>

                                    <input type="radio" class="btn-check" name="consumption_type" id="type_takeaway" value="takeaway" <?= $ctype == 'takeaway' ? 'checked' : '' ?> onchange="toggleDeliveryFields()">
                                    <label class="btn btn-outline-warning" for="type_takeaway">Llevar</label>

                                    <input type="radio" class="btn-check" name="consumption_type" id="type_delivery" value="delivery" <?= $ctype == 'delivery' ? 'checked' : '' ?> onchange="toggleDeliveryFields()">
                                    <label class="btn btn-outline-success" for="type_delivery">Domicilio</label>
                                </div>
                            </div>
                        </div>

                        <?php 
                        $dtier = $orderId ? ($orderData['delivery_tier'] ?? 'A') : 'A';
                        $daddress = ($orderId && $ctype == 'delivery') ? ($orderData['shipping_address'] ?? '') : '';
                        ?>
                        <div id="deliveryFields" style="display: <?= $ctype == 'delivery' ? 'block' : 'none' ?>; background: rgba(30, 41, 59, 0.4); border: 1px solid rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px);" class="p-4 mb-4 rounded-4 shadow-sm animate__animated animate__fadeIn">
                            <div class="row g-2 align-items-center">
                                <div class="col-md-12 text-center">
                                    <label class="form-label small text-info text-uppercase fw-bold mb-3 d-block" style="letter-spacing: 1px;">
                                        <i class="fa fa-truck-loading me-2"></i>Tipo de Servicio
                                    </label>
                                    <select name="delivery_tier" id="delivery_tier" class="form-select bg-dark text-white border-info border-opacity-25 fw-bold mx-auto shadow-sm" style="max-width:320px; border-radius: 12px; height: 45px;" onchange="calculate()">
                                        <option value="A" data-fee="<?= $feeA ?>" <?= $dtier == 'A' ? 'selected' : '' ?>>📍 Tipo A (Gratis)</option>
                                        <option value="B" data-fee="<?= $feeB ?>" <?= $dtier == 'B' ? 'selected' : '' ?>>🚚 Tipo B ($<?= number_format($feeB, 2) ?>)</option>
                                        <option value="C" data-fee="<?= $feeC ?>" <?= $dtier == 'C' ? 'selected' : '' ?>>🚀 Tipo C ($<?= number_format($feeC, 2) ?>)</option>
                                    </select>
                                    <input type="hidden" name="shipping_address" id="shipping_address" value="Delivery">
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h6 class="text-info fw-bold mb-3"><i class="fa fa-money-bill-wave me-2"></i> Ingrese Montos
                            Recibidos:</h6>
                        <div class="row g-3" id="paymentMethodsContainer">
                            <?php foreach ($methods as $method):
                                $isPagoMovil = (strpos(strtolower($method['name']), 'pago móvil') !== false || strpos(strtolower($method['name']), 'pagomovil') !== false);
                                $isZelle = (strpos(strtolower($method['name']), 'zelle') !== false);
                                $isDigital = $isPagoMovil || $isZelle;
                                ?>
                                <div class="col-md-6 method-card" id="method-card-<?= $method['id'] ?>">
                                    <div class="card p-2 border-0 bg-dark bg-opacity-25 shadow-sm mb-2 h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge bg-primary text-white small px-3">
                                                <?= $method['name'] ?>
                                            </span>
                                            <?php if ($isDigital): ?>
                                                <button type="button" class="btn btn-sm btn-info py-0 px-2 text-dark fw-bold"
                                                    onclick="addPaymentRow(<?= $method['id'] ?>, <?= $isPagoMovil ? 'true' : 'false' ?>, <?= $isZelle ? 'true' : 'false' ?>)">
                                                    <i class="fa fa-plus small"></i> AGREGAR
                                                </button>
                                            <?php endif; ?>
                                        </div>

                                        <div class="rows-container" id="rows-<?= $method['id'] ?>">
                                            <div class="payment-row mb-3 pb-3 border-bottom border-light-subtle">
                                                <div class="input-group">
                                                    <input type="number" step="0.01"
                                                        class="form-control payment-input fw-bold text-end text-white bg-dark"
                                                        name="payments[<?= $method['id'] ?>][]"
                                                        data-currency="<?= $method['currency'] ?>"
                                                        data-method-name="<?= htmlspecialchars($method['name']) ?>"
                                                        placeholder="0.00">
                                                    <span
                                                        class="input-group-text bg-secondary text-white border-0"><?= $method['currency'] ?></span>
                                                </div>

                                                <!-- Detalles para Pago Móvil o Zelle -->
                                                <div class="mt-2 extra-details" style="display:none;">
                                                    <?php if ($isPagoMovil): ?>
                                                        <input type="text"
                                                            class="form-control form-control-sm bg-dark text-white border-secondary"
                                                            name="payment_details[<?= $method['id'] ?>][reference][]"
                                                            placeholder="# Referencia (8-10 dígitos)" data-required="true">
                                                    <?php elseif ($isZelle): ?>
                                                        <input type="text"
                                                            class="form-control form-control-sm mb-1 bg-dark text-white border-secondary"
                                                            name="payment_details[<?= $method['id'] ?>][reference][]"
                                                            placeholder="Código Conf.">
                                                        <input type="text"
                                                            class="form-control form-control-sm bg-dark text-white border-secondary"
                                                            name="payment_details[<?= $method['id'] ?>][sender][]"
                                                            placeholder="Remitente" data-required="true">
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-4 p-3 rounded border border-secondary"
                            style="background-color: rgba(0,0,0,0.4);">
                            <div class="row text-center align-items-center">
                                <div class="col-md-4 border-end">
                                    <small class="text-white-50 d-block text-uppercase fw-bold">Total Recibido</small>
                                    <div id="paidUsd" class="fs-4 fw-bold text-info">$0.00</div>
                                    <div id="paidVes" class="small text-white-50">0.00 Bs</div>
                                </div>

                                <div class="col-md-4 border-end" id="colRemaining">
                                    <small class="text-white-50 d-block text-uppercase fw-bold">Falta por Pagar</small>
                                    <div id="remainUsd" class="fs-4 fw-bold text-danger">
                                        $<?= number_format($totalUsd, 2) ?></div>
                                    <div id="remainVes" class="small text-danger">
                                        <?= number_format($totalUsd * $rate, 2) ?> Bs
                                    </div>
                                </div>

                                <div class="col-md-4" id="colChange" style="opacity: 0.5;">
                                    <small class="text-white-50 d-block text-uppercase fw-bold">Su Cambio</small>
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
                                <?php foreach ($methods as $m): ?>
                                    <option value="<?= $m['id'] ?>">
                                        Entregar en <?= $m['name'] ?> (<?= $m['currency'] ?>)
                                    </option>
                                <?php endforeach; ?>
                                <?php if ($sessionClientId): ?>
                                    <option value="store_credit" class="fw-bold text-primary">
                                        Abonar a cuenta (Saldo a Favor cliente)
                                    </option>
                                <?php endif; ?>
                            </select>
                            <div class="form-text text-muted">El sistema registrará la salida de dinero de esta cuenta.
                            </div>
                        </div>

                        <!-- SECCIÓN CRÉDITO Y BENEFICIOS -->
                        <div class="mt-4">
                            <button type="button" class="btn btn-outline-info w-100 fw-bold py-2 shadow-sm"
                                data-bs-toggle="modal" data-bs-target="#modalCredit">
                                <i class="fa fa-handshake me-2"></i> Procesar Crédito o Beneficio
                            </button>
                        </div>

                        <!-- Inputs Ocultos para Crédito -->
                        <input type="hidden" name="is_credit" id="is_credit" value="0">
                        <input type="hidden" name="credit_client_id" id="credit_client_id" value="<?= $sessionClientId ?>">
                        <input type="hidden" name="credit_employee_id" id="credit_employee_id" value="<?= $sessionEmployeeId ?>">
                        <input type="hidden" name="credit_type" id="credit_type" value="<?= $sessionEmployeeId ? 'employee_credit' : ($sessionClientId ? 'client_credit' : '') ?>">
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
    const initialTotalUsd = <?= $baseTotalUsd ?>;
    const alreadyPaidUsd = <?= $alreadyPaid ?>;
    const clientBalanceUsd = <?= $clientBalance ?>;
    let currentDeliveryFee = 0;
    let totalOrderUsd = initialTotalUsd;
    const rate = <?= $rate ?>;
    const btnSubmit = document.getElementById('btnSubmit');
    const divChangeMethod = document.getElementById('changeMethodContainer');
    const selectChangeMethod = document.querySelector('select[name="change_method_id"]');

    // Credit Logic Vars
    let selectedClientId = <?= $sessionClientId ?: 'null' ?>;
    let selectedEmpId = <?= $sessionEmployeeId ?: 'null' ?>;
    let currentCreditType = selectedEmpId ? 'employee_credit' : 'client_credit';

    function addPaymentRow(methodId, isPagoMovil, isZelle) {
        const container = document.getElementById('rows-' + methodId);
        const row = document.createElement('div');
        row.className = 'payment-row mb-3 pb-3 border-bottom border-light-subtle animate__animated animate__fadeIn';

        const currency = container.querySelector('.payment-input').dataset.currency;
        const methodName = container.querySelector('.payment-input').dataset.methodName;

        let extraFields = '';
        if (isPagoMovil) {
            extraFields = `<input type="text" class="form-control form-control-sm bg-dark text-white border-secondary" name="payment_details[${methodId}][reference][]" placeholder="# Referencia (8-10 dígitos)" data-required="true">`;
        } else if (isZelle) {
            extraFields = `
                <input type="text" class="form-control form-control-sm mb-1 bg-dark text-white border-secondary" name="payment_details[${methodId}][reference][]" placeholder="Código Conf.">
                <input type="text" class="form-control form-control-sm bg-dark text-white border-secondary" name="payment_details[${methodId}][sender][]" placeholder="Remitente" data-required="true">
            `;
        }

        row.innerHTML = `
            <div class="d-flex gap-1 align-items-center mb-2">
                <div class="input-group">
                    <input type="number" step="0.01" class="form-control payment-input fw-bold text-end text-white bg-dark border-secondary" name="payments[${methodId}][]" data-currency="${currency}" data-method-name="${methodName}" placeholder="0.00">
                    <span class="input-group-text bg-secondary text-white border-0">${currency}</span>
                </div>
                <button type="button" class="btn btn-sm btn-danger text-white shadow-sm" onclick="this.closest('.payment-row').remove(); calculate();">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <div class="mt-2 extra-details">
                ${extraFields}
            </div>
        `;

        container.appendChild(row);

        // Re-enlazar eventos a los nuevos inputs
        const newInputs = row.querySelectorAll('.payment-input');
        newInputs.forEach(initInputEvent);
    }

    function initInputEvent(input) {
        input.addEventListener('input', () => {
            calculate();
            const row = input.closest('.payment-row');
            const detailsDiv = row.querySelector('.extra-details');
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
    }

    function toggleDeliveryFields() {
        const isDelivery = document.getElementById('type_delivery').checked;
        const fields = document.getElementById('deliveryFields');
        const row = document.getElementById('deliveryRow');
        
        if (isDelivery) {
            fields.style.display = 'block';
            row.style.setProperty('display', 'flex', 'important');
        } else {
            fields.style.display = 'none';
            row.style.setProperty('display', 'none', 'important');
        }
        calculate();
    }

    function calculate() {
        let paidUsd = 0;
        const allInputs = document.querySelectorAll('.payment-input');

        // 0. Actualizar Total con Delivery Fee
        const isDelivery = document.getElementById('type_delivery').checked;
        if (isDelivery) {
            const tierSelect = document.getElementById('delivery_tier');
            const selectedOption = tierSelect.options[tierSelect.selectedIndex];
            currentDeliveryFee = parseFloat(selectedOption.dataset.fee) || 0;
        } else {
            currentDeliveryFee = 0;
        }
        
        totalOrderUsd = initialTotalUsd + currentDeliveryFee;
        let remainingDue = Math.max(0, totalOrderUsd - alreadyPaidUsd);

        // Actualizar UI del resumen
        document.getElementById('summaryDeliveryFee').textContent = '$' + currentDeliveryFee.toFixed(2);
        document.getElementById('summaryTotalUsd').textContent = '$' + remainingDue.toFixed(2);
        document.getElementById('summaryTotalVes').textContent = (remainingDue * rate).toLocaleString('es-VE', { minimumFractionDigits: 2 }) + ' Bs';

        // 1. Sumar Pagos (Convirtiendo todo a USD base)
        allInputs.forEach(input => {
            let val = parseFloat(input.value) || 0;
            if (input.dataset.currency === 'VES') {
                paidUsd += (val / rate);
            } else {
                paidUsd += val;
            }
        });

        // 2. Calcular Diferencia (Lo que falta por pagar hoy)
        // Se resta lo ya pagado y el saldo a favor del cliente
        let diff = (totalOrderUsd - alreadyPaidUsd - clientBalanceUsd) - paidUsd;
        let epsilon = 0.005; // Ajuste por redondeo
        let isCredit = document.getElementById('is_credit').value == '1';

        if (isCredit) {
            paidUsd = totalOrderUsd;
            diff = 0;
        }

        document.getElementById('paidUsd').textContent = '$' + paidUsd.toFixed(2);
        document.getElementById('paidVes').textContent = (paidUsd * rate).toLocaleString('es-VE', { minimumFractionDigits: 2 }) + ' Bs';

        if (diff > epsilon && !isCredit) {
            document.getElementById('remainUsd').textContent = '$' + diff.toFixed(2);
            document.getElementById('remainVes').textContent = (diff * rate).toLocaleString('es-VE', { minimumFractionDigits: 2 }) + ' Bs';
            
            // Actualizar también el resumen a la derecha
            const summaryRemUsd = document.getElementById('summaryRemainingUsd');
            if(summaryRemUsd) {
                summaryRemUsd.textContent = '$' + diff.toFixed(2);
                document.getElementById('summaryRemainingVes').textContent = (diff * rate).toLocaleString('es-VE', { minimumFractionDigits: 2 }) + ' Bs';
            }

            document.getElementById('changeUsd').textContent = "$0.00";
            document.getElementById('changeVes').textContent = "0.00 Bs";
            document.getElementById('colRemaining').style.opacity = "1";
            document.getElementById('colChange').style.opacity = "0.3";
            divChangeMethod.style.display = "none";
            selectChangeMethod.required = false;
            btnSubmit.disabled = true;
            btnSubmit.className = "btn btn-secondary btn-lg py-3";
            btnSubmit.innerHTML = '<i class="fa fa-lock me-2"></i> Complete el Pago';
        } else {
            let change = Math.abs(diff);
            if (diff > 0) change = 0; // Evitar mostrar cambio si falta centavos

            document.getElementById('remainUsd').textContent = "$0.00";
            document.getElementById('remainVes').textContent = "0.00 Bs";

            // Actualizar resumen
            const summaryRemUsd = document.getElementById('summaryRemainingUsd');
            if(summaryRemUsd) {
                summaryRemUsd.textContent = "$0.00";
                document.getElementById('summaryRemainingVes').textContent = "0.00 Bs";
            }

            document.getElementById('changeUsd').textContent = '$' + change.toFixed(2);
            document.getElementById('changeVes').textContent = (change * rate).toLocaleString('es-VE', { minimumFractionDigits: 2 }) + ' Bs';
            document.getElementById('colRemaining').style.opacity = "0.3";
            document.getElementById('colChange').style.opacity = "1";
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

    // Inicializar eventos para inputs existentes
    document.querySelectorAll('.payment-input').forEach(initInputEvent);

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
        document.querySelectorAll('.payment-input').forEach(i => i.disabled = true);

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
        document.querySelectorAll('.extra-details input[data-required="true"]').forEach(field => {
            const row = field.closest('.payment-row');
            const amount = parseFloat(row.querySelector('.payment-input').value) || 0;

            if (amount > 0 && !field.value.trim()) {
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

        // Validar campos de Delivery
        const isDelivery = document.getElementById('type_delivery').checked;
        if (isDelivery) {
            const address = document.getElementById('shipping_address').value.trim();
            if (address.length < 5) {
                alert("⚠️ Por favor, ingrese una dirección de entrega válida.");
                document.getElementById('shipping_address').focus();
                e.preventDefault();
                return false;
            }
        }

        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i> Procesando...';
    });
    // Si ya existe empleado en sesión, activar el tab de empleado y asignar tipo
    if (selectedEmpId) {
        try {
            var empTabEl = document.querySelector('#creditTabs a[href="#tabEmployee"]');
            var empTab = new bootstrap.Tab(empTabEl);
            empTab.show();
            currentCreditType = 'employee_credit';
        } catch (e) { console.error("Error activating emp tab:", e); }
    }

    // Inicializar campos de delivery si ya vienen marcados
    if (document.getElementById('type_delivery').checked) {
        toggleDeliveryFields();
    }
</script>

<?php require_once '../templates/footer.php'; ?>