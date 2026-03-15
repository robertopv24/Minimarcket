<?php
require_once '../templates/autoload.php';
session_start();

if (!isset($_SESSION['user_id']))
    die("Acceso denegado");

$orderId = $_GET['id'] ?? 0;
$order = $orderManager->getOrderById($orderId);

if (!$order)
    die("Orden no encontrada");

$items = $orderManager->getOrderItems($orderId);
$companyName = $GLOBALS['config']->get('site_name');

// ---------------------------------------------------------
// DATOS FINANCIEROS
// ---------------------------------------------------------

$sessionId = $_GET['session_id'] ?? 0;
$sessionFilter = ($sessionId > 0) ? " AND t.cash_session_id = ? " : "";
$params = ($sessionId > 0) ? [$orderId, $sessionId] : [$orderId];

// 1. Pagos Recibidos (Filtrar por sesión si existe)
$sqlPay = "SELECT pm.name as method, t.amount, t.currency
           FROM transactions t
           JOIN payment_methods pm ON t.payment_method_id = pm.id
           WHERE t.reference_type = 'order' AND t.reference_id = ?
           AND t.type = 'income' $sessionFilter";
$stmtPay = $db->prepare($sqlPay);
$stmtPay->execute($params);
$payments = $stmtPay->fetchAll(PDO::FETCH_ASSOC);

// 2. Vuelto Real o Saldo a Favor (Filtrar por sesión si existe)
$sqlChange = "SELECT t.amount, t.currency, t.reference_type, t.description, pm.name as method
              FROM transactions t
              LEFT JOIN payment_methods pm ON t.payment_method_id = pm.id
              WHERE t.reference_id = ? AND t.reference_type = 'order'
              AND t.type = 'expense' $sessionFilter
              ORDER BY t.id ASC";
$stmtChange = $db->prepare($sqlChange);
$stmtChange->execute($params);
$changeTxs = $stmtChange->fetchAll(PDO::FETCH_ASSOC);

// 3. Pago Real en esta transacción (pasado por process_checkout)
$newPaidUsd = floatval($_GET['new_paid_usd'] ?? 0);

// 4. Calcular "Abonos Previos" (Todo lo neto ANTES de esta sesión)
$alreadyPaidNetPrev = 0;
if ($sessionId > 0) {
    $sqlPrev = "SELECT 
                    SUM(CASE WHEN type = 'income' THEN amount_usd_ref ELSE 0 END) - 
                    SUM(CASE WHEN type = 'expense' THEN amount_usd_ref ELSE 0 END) as net_prev
                FROM transactions 
                WHERE reference_type = 'order' AND reference_id = ? AND cash_session_id != ?";
    $stmtPrev = $db->prepare($sqlPrev);
    $stmtPrev->execute([$orderId, $sessionId]);
    $alreadyPaidNetPrev = floatval($stmtPrev->fetchColumn() ?: 0);
}

// 5. Calcular Pago Bruto Acumulado (Para lógica legacy si falla lo anterior)
$totalIncomeGross = 0;
$allIncomeSql = "SELECT amount, currency FROM transactions WHERE reference_type = 'order' AND reference_id = ? AND type = 'income'";
$stmtAll = $db->prepare($allIncomeSql);
$stmtAll->execute([$orderId]);
foreach ($stmtAll->fetchAll() as $inc) {
    if ($inc['currency'] == 'VES') {
        $rate = $config->get('exchange_rate');
        $totalIncomeGross += ($inc['amount'] / $rate);
    } else {
        $totalIncomeGross += $inc['amount'];
    }
}

// --- GENERADOR DE TEXTO (58mm - 32 CHARS) ---
define('WIDTH', 32);
define('EOL', "\n");

function clean($str)
{
    $str = strtoupper(trim($str));
    $str = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'], ['A', 'E', 'I', 'O', 'U', 'N'], $str);
    return preg_replace('/[^A-Z0-9 \.,\-\(\)\#\$\%\:\/]/', '', $str);
}

function center($str)
{
    $str = clean($str);
    $len = strlen($str);
    if ($len >= WIDTH)
        return substr($str, 0, WIDTH);
    $pad = floor((WIDTH - $len) / 2);
    return str_repeat(' ', $pad) . $str . str_repeat(' ', WIDTH - $len - $pad);
}

function row($left, $right)
{
    $left = clean($left);
    $right = clean($right);

    $lenLeft = strlen($left);
    $lenRight = strlen($right);

    if ($lenLeft + $lenRight + 1 > WIDTH) {
        $left = substr($left, 0, WIDTH - $lenRight - 1);
    }

    $spaces = WIDTH - strlen($left) - strlen($right);
    return $left . str_repeat(' ', $spaces) . $right;
}

function line()
{
    return str_repeat('-', WIDTH) . EOL;
}

// ---------------------------------------------------------
// CONSTRUCCIÓN DEL TICKET CLIENTE
// ---------------------------------------------------------
$customerTicket = "";
$customerTicket .= center($companyName) . EOL;
$customerTicket .= center("ORDEN #" . str_pad($orderId, 6, '0', STR_PAD_LEFT)) . EOL;
$customerTicket .= center(date('d/m/Y h:i A', strtotime($order['created_at']))) . EOL;
$customerTicket .= EOL;
$customerTicket .= "CAJERO : " . clean(substr($order['customer_name'], 0, 20)) . EOL;
$displayClient = 'MOSTRADOR';
if (!empty($order['client_id'])) {
    $stmtCl = $db->prepare("SELECT name FROM clients WHERE id = ?");
    $stmtCl->execute([$order['client_id']]);
    $clientName = $stmtCl->fetchColumn();
    if ($clientName) $displayClient = $clientName;
} elseif (!empty($order['customer_note'])) {
    $displayClient = $order['customer_note'];
}
$customerTicket .= "CLIENTE: " . clean(substr($displayClient, 0, 24)) . EOL;
if (($order['consumption_type'] ?? '') === 'delivery' && !empty($order['delivery_tier'])) {
    $customerTicket .= "SERVICIO: DELIVERY (" . $order['delivery_tier'] . ")" . EOL;
}
$customerTicket .= line();

$customerTicket .= "CANT DESCRIPCION           TOTAL" . EOL;
$customerTicket .= line();

foreach ($items as $item) {
    $totalItem = $item['price'] * $item['quantity'];
    $mods = $orderManager->getItemModifiers($item['id']);

    if ($item['name'] === 'Servicio Delivery') {
        $customerTicket .= line();
        $customerTicket .= row("  CARGO DELIVERY:", "$" . number_format($totalItem, 2)) . EOL;
        $customerTicket .= line();
        continue;
    }

    $qtyName = str_pad($item['quantity'], 2, ' ', STR_PAD_LEFT) . " " . clean($item['name'] ?? 'PRODUCTO');
    $priceTxt = number_format($totalItem, 2);
    $customerTicket .= row($qtyName, $priceTxt) . EOL;

    if ($item['product_type'] == 'compound') {
        $comps = $productManager->getProductComponents($item['product_id']);
        $subsNames = [];
        foreach ($comps as $c) {
            $subName = "";
            if ($c['component_type'] == 'product') {
                $p = $productManager->getProductById($c['component_id']);
                $subName = $p['name'];
            } elseif ($c['component_type'] == 'manufactured') {
                $stmtM = $db->prepare("SELECT name FROM manufactured_products WHERE id = ?");
                $stmtM->execute([$c['component_id']]);
                $subName = $stmtM->fetchColumn() ?: 'ITEM COCINA';
            }
            if ($subName) {
                $cQty = intval($c['quantity']);
                $subsNames[] = clean($subName) . ($cQty > 1 ? " (X$cQty)" : "");
            }
        }
        if (!empty($subsNames)) {
            $customerTicket .= "  > INC: " . EOL;
            $countS = count($subsNames);
            foreach ($subsNames as $i => $sn) {
                $comma = ($i < $countS - 1) ? "," : "";
                $customerTicket .= $sn . $comma . EOL;
            }
        }
    }

    foreach ($mods as $m) {
        if ($m['modifier_type'] == 'add' && $m['price_adjustment_usd'] > 0) {
            $extraName = "   " . clean($m['ingredient_name']);
            $extraPrice = number_format($m['price_adjustment_usd'] * $item['quantity'], 2);
            $customerTicket .= row($extraName, $extraPrice) . EOL;
        }
    }
}

$customerTicket .= line();
$customerTicket .= row("TOTAL ORDEN:", "$" . number_format($order['total_price'], 2)) . EOL;

// Lógica de Abonos y Saldo
if ($sessionId > 0) {
    // Si venimos del checkout, mostramos el balance anterior
    if ($alreadyPaidNetPrev < 0) {
        $customerTicket .= row("VUELTOS/RET. ANT:", "$" . number_format(abs($alreadyPaidNetPrev), 2)) . EOL;
    } else {
        $customerTicket .= row("ABONOS ANTERIORES:", "$" . number_format($alreadyPaidNetPrev, 2)) . EOL;
    }
    
    $saldoHoy = $order['total_price'] - $alreadyPaidNetPrev;
    $customerTicket .= row("SALDO A COBRAR:", "$" . number_format(max(0, $saldoHoy), 2)) . EOL;
} else {
    // Modo ver historial: mostrar el NETO real histórico (Ingresos - Egresos)
    $sqlHistory = "SELECT 
                        SUM(CASE WHEN type = 'income' THEN amount_usd_ref ELSE 0 END) - 
                        SUM(CASE WHEN type = 'expense' THEN amount_usd_ref ELSE 0 END) as total_net
                    FROM transactions 
                    WHERE reference_type = 'order' AND reference_id = ?";
    $stmtHistory = $db->prepare($sqlHistory);
    $stmtHistory->execute([$orderId]);
    $totalPaidNet = floatval($stmtHistory->fetchColumn() ?: 0);
    
    $customerTicket .= row("PAGADO TOTAL:", "$" . number_format($totalPaidNet, 2)) . EOL;
}

$customerTicket .= line();

foreach ($payments as $pay) {
    $sym = ($pay['currency'] == 'USD') ? '$' : 'Bs ';
    $customerTicket .= row(substr($pay['method'], 0, 18) . ":", $sym . number_format($pay['amount'], 2)) . EOL;
}

if (!empty($changeTxs)) {
    foreach ($changeTxs as $ctx) {
        $sym = ($ctx['currency'] == 'USD') ? '$' : 'Bs ';
        $isBalance = (stripos($ctx['description'] ?? '', 'Saldo') !== false);
        $methodLabel = !empty($ctx['method']) ? " " . $ctx['method'] : "";
        $label = $isBalance ? "ABONO CUENTA:" : "VUELTO" . $methodLabel . ":";
        $customerTicket .= row($label, $sym . number_format($ctx['amount'], 2)) . EOL;
    }
} else {
    $calculatedChange = $totalIncomeGross - $order['total_price'];
    if ($calculatedChange > 0.005 && $newPaidUsd > 0) {
        $customerTicket .= row("SU CAMBIO:", "$" . number_format($calculatedChange, 2)) . EOL;
    }
}

if (!empty($order['customer_note'])) {
    $customerTicket .= line();
    $customerTicket .= "NOTA: " . clean($order['customer_note']) . EOL;
}

$customerTicket .= EOL;
$customerTicket .= center("*** GRACIAS POR SU COMPRA ***") . EOL;
$customerTicket .= EOL . EOL;
$customerTicket .= line();


// ---------------------------------------------------------
// CONSTRUCCIÓN DEL TICKET COCINA (COMANDA)
// ---------------------------------------------------------
$kitchenTicket = "";
$kitchenTicket .= EOL;
$kitchenTicket .= center("- - - CORTE COCINA - - -") . EOL;
$kitchenTicket .= EOL;
$kitchenTicket .= center("ORDEN #" . $orderId) . EOL;
$kitchenTicket .= center(clean(substr($displayClient, 0, 30))) . EOL;

if (($order['consumption_type'] ?? '') === 'delivery' && !empty($order['delivery_tier'])) {
    $kitchenTicket .= center("DELIVERY (" . $order['delivery_tier'] . ")") . EOL;
}

$kitchenTicket .= line();

$useShortCodes = ($GLOBALS['config']->get('kds_use_short_codes', '0') == '1');
foreach ($items as $item) {
    if (strpos(strtoupper($item['name']), 'SERVICIO DELIVERY') !== false) {
        continue;
    }
    $mods = $orderManager->getItemModifiers($item['id']);
    $groupedMods = [];
    foreach ($mods as $m) {
        $groupedMods[$m['sub_item_index']][] = $m;
    }

    foreach ($groupedMods as &$gMods) {
        usort($gMods, function ($a, $b) {
            $order = ['side' => 1, 'add' => 2, 'remove' => 3];
            $ta = strtolower($a['modifier_type'] ?? '');
            $tb = strtolower($b['modifier_type'] ?? '');
            $va = ($ta != 'add' && $ta != 'remove' && $ta != 'side') ? (($ta == 'info') ? 99 : 1) : ($order[$ta] ?? 99);
            $vb = ($tb != 'add' && $tb != 'remove' && $tb != 'side') ? (($tb == 'info') ? 99 : 1) : ($order[$tb] ?? 99);
            return $va <=> $vb;
        });
    }
    unset($gMods);

    $subNames = [];
    if ($item['product_type'] == 'compound') {
        $comps = $productManager->getProductComponents($item['product_id']);
        foreach ($comps as $c) {
            $sName = "";
            if ($c['component_type'] == 'product') {
                $p = $productManager->getProductById($c['component_id']);
                $sName = ($useShortCodes && !empty($p['short_code'])) ? $p['short_code'] : $p['name'];
            } elseif ($c['component_type'] == 'manufactured') {
                $stmtM = $db->prepare("SELECT name, short_code FROM manufactured_products WHERE id = ?");
                $stmtM->execute([$c['component_id']]);
                $mR = $stmtM->fetch(PDO::FETCH_ASSOC);
                $sName = ($useShortCodes && !empty($mR['short_code'])) ? $mR['short_code'] : ($mR['name'] ?? 'ITEM COCINA');
            }
            if ($sName) {
                for ($k = 0; $k < $c['quantity']; $k++) {
                    $subNames[] = clean($sName);
                }
            }
        }
    }

    $loopCount = $item['quantity'];
    if ($item['product_type'] == 'compound' && !empty($subNames)) {
        $loopCount = count($subNames);
    }

    $nameToPrint = $item['name'] ?? 'PRODUCTO';
    $kitchenTicket .= ">> " . $item['quantity'] . " X " . clean(($useShortCodes && !empty($item['short_code'])) ? $item['short_code'] : $nameToPrint) . EOL;

    for ($i = 0; $i < $loopCount; $i++) {
        $currentMods = $groupedMods[$i] ?? [];
        $isTakeaway = false;
        foreach ($currentMods as $m) {
            if ($m['modifier_type'] == 'info' && ($m['is_takeaway'] == 1 || ($m['ingredient_name'] ?? '') == '[LLEVAR]'))
                $isTakeaway = true;
        }

        $orderType = $order['consumption_type'] ?? 'dine_in';
        $cTypeItem = $item['consumption_type'] ?? 'dine_in';
        $tag = '[LOCAL]';
        if ($orderType === 'delivery' || $cTypeItem === 'delivery') {
            $tag = '[DELIVERY]';
        } elseif ($isTakeaway || $cTypeItem === 'takeaway' || $orderType === 'takeaway') {
            $tag = '[LLEVAR]';
        }

        $componentLabel = isset($subNames[$i]) ? " ** (" . clean($subNames[$i]) . ")" : '';
        $kitchenTicket .= "   $tag #" . ($i + 1) . $componentLabel . EOL;

        foreach ($currentMods as $m) {
            if ($m['modifier_type'] == 'info' && !empty($m['note'])) {
                $kitchenTicket .= "    ! " . clean($m['note']) . EOL;
            }
        }

        foreach ($currentMods as $m) {
            if ($m['modifier_type'] == 'info' && preg_match('/^\[[A-Z]\]$/', $m['ingredient_name'] ?? ''))
                continue;

            $prefix = match ($m['modifier_type']) {
                'remove' => '     -- ',
                'side' => '     ** ' ,
                'add' => '     ++ ',
                default => '     >> '
            };
            if ($m['modifier_type'] !== 'info') {
                $mName = ($useShortCodes && !empty($m['short_code'])) ? $m['short_code'] : $m['ingredient_name'];
                $kitchenTicket .= $prefix . clean($mName) . EOL;
            }
        }
        if ($i == 0) {
            foreach ($mods as $gm) {
                if ($gm['sub_item_index'] == -1 && $gm['modifier_type'] == 'info' && !empty($gm['note'])) {
                    $kitchenTicket .= "   NOTA: " . clean($gm['note']) . EOL;
                }
            }
        }
    }
    $kitchenTicket .= str_repeat("-", WIDTH) . EOL;
}
$kitchenTicket .= ".";
$kitchenTicket .= EOL . EOL;
$kitchenTicket .= line();

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<style>
    .ticket-container { display: flex; flex-direction: column; align-items: center; min-height: 80vh; padding-top: 2rem; }
    .ticket-wrapper { background: white; color: black; width: 80mm; padding: 20px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15); border-radius: 2px; margin-bottom: 2rem; }
    pre.ticket-content { font-family: 'Courier New', Courier, monospace; font-size: 14px; font-weight: bold; line-height: 1.2; white-space: pre; margin: 0 auto; display: block; width: fit-content; text-align: left; }
    .ticket-actions { display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center; margin-bottom: 3rem; }
    @media print { .no-print, header, nav, footer { display: none !important; } .ticket-wrapper { width: 100% !important; box-shadow: none !important; margin: 0 !important; } }
</style>

<div class="container py-4">
    <div class="row">
        <div class="col-md-6 ticket-container">
            <h4 class="text-white mb-3"><i class="fa fa-user me-2"></i>RECIBO CLIENTE</h4>
            <div class="ticket-wrapper" id="customerTicketWrapper">
                <pre class="ticket-content"><?= $customerTicket ?></pre>
            </div>
            <div class="ticket-actions no-print">
                <button onclick="printSelect('customer')" class="btn btn-primary btn-lg w-100"><i class="fa fa-print"></i> Imprimir (Windows)</button>
            </div>
        </div>
        <div class="col-md-6 ticket-container">
            <h4 class="text-white mb-3"><i class="fa fa-fire me-2"></i>COMANDA COCINA</h4>
            <div class="ticket-wrapper" id="kitchenTicketWrapper">
                <pre class="ticket-content"><?= $kitchenTicket ?></pre>
            </div>
            <div class="ticket-actions no-print">
                <button onclick="printSelect('kitchen')" class="btn btn-primary btn-lg w-100"><i class="fa fa-print"></i> Imprimir (Windows)</button>
            </div>
        </div>
    </div>
    <div class="text-center mt-4 no-print">
        <a href="tienda.php" class="btn btn-lg btn-secondary px-5"><i class="fa fa-arrow-left me-2"></i> Volver a Tienda</a>
    </div>
</div>

<script>
    function printSelect(type) {
        $('.ticket-container').removeClass('print-only');
        if (type === 'customer') $('#customerTicketWrapper').closest('.ticket-container').addClass('print-only');
        else $('#kitchenTicketWrapper').closest('.ticket-container').addClass('print-only');
        window.print();
    }
</script>

<?php require_once '../templates/footer.php'; ?>