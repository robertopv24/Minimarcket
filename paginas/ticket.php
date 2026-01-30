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

// 1. Pagos Recibidos
$sqlPay = "SELECT pm.name as method, t.amount, t.currency
           FROM transactions t
           JOIN payment_methods pm ON t.payment_method_id = pm.id
           WHERE t.reference_type = 'order'
           AND t.reference_id = ?
           AND t.type = 'income'";
$stmtPay = $db->prepare($sqlPay);
$stmtPay->execute([$orderId]);
$payments = $stmtPay->fetchAll(PDO::FETCH_ASSOC);

// 2. Vuelto Real
$sqlChange = "SELECT amount, currency
              FROM transactions
              WHERE reference_type = 'order'
              AND reference_id = ?
              AND type = 'expense'
              LIMIT 1";
$stmtChange = $db->prepare($sqlChange);
$stmtChange->execute([$orderId]);
$changeTx = $stmtChange->fetch(PDO::FETCH_ASSOC);

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
// CONSTRUCCIÓN DEL TICKET
// ---------------------------------------------------------
// ---------------------------------------------------------
// CONSTRUCCIÓN DEL TICKET CLIENTE
// ---------------------------------------------------------
$customerTicket = "";
$customerTicket .= center($companyName) . EOL;
$customerTicket .= center("ORDEN #" . str_pad($orderId, 6, '0', STR_PAD_LEFT)) . EOL;
$customerTicket .= center(date('d/m/Y h:i A', strtotime($order['created_at']))) . EOL;
$customerTicket .= EOL;
$customerTicket .= "CAJERO : " . clean(substr($order['customer_name'], 0, 20)) . EOL;
$customerTicket .= "CLIENTE: " . clean(substr($order['shipping_address'] ?? 'MOSTRADOR', 0, 20)) . EOL;
$customerTicket .= line();

$customerTicket .= "CANT DESCRIPCION           TOTAL" . EOL;
$customerTicket .= line();

foreach ($items as $item) {
    $totalItem = $item['price'] * $item['quantity'];
    $mods = $orderManager->getItemModifiers($item['id']);

    $qtyName = str_pad($item['quantity'], 2, ' ', STR_PAD_LEFT) . " " . clean($item['name']);
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
$customerTicket .= row("TOTAL:", "$" . number_format($order['total_price'], 2)) . EOL;

foreach ($payments as $pay) {
    $sym = ($pay['currency'] == 'USD') ? '$' : 'Bs ';
    $customerTicket .= row(substr($pay['method'], 0, 18) . ":", $sym . number_format($pay['amount'], 2)) . EOL;
}

if ($changeTx) {
    $sym = ($changeTx['currency'] == 'USD') ? '$' : 'Bs ';
    $customerTicket .= row("SU CAMBIO:", $sym . number_format($changeTx['amount'], 2)) . EOL;
} else {
    $customerTicket .= row("SU CAMBIO:", "$0.00") . EOL;
}

$customerTicket .= EOL;
$customerTicket .= center("*** GRACIAS POR SU COMPRA ***") . EOL;
$customerTicket .= EOL . EOL;

// ---------------------------------------------------------
// CONSTRUCCIÓN DEL TICKET COCINA (COMANDA)
// ---------------------------------------------------------
$kitchenTicket = "";
$kitchenTicket .= center("- - - CORTE COCINA - - -") . EOL;
$kitchenTicket .= EOL;
$kitchenTicket .= center("ORDEN #" . $orderId) . EOL;
$kitchenTicket .= center(clean(substr($order['shipping_address'] ?? '', 0, 30))) . EOL;
$kitchenTicket .= line();

$useShortCodes = ($GLOBALS['config']->get('kds_use_short_codes', '0') == '1');
foreach ($items as $item) {
    $mods = $orderManager->getItemModifiers($item['id']);
    $groupedMods = [];
    foreach ($mods as $m) {
        $groupedMods[$m['sub_item_index']][] = $m;
    }

    // Ordenar modificadores por grupo para impresión correcta
    foreach ($groupedMods as &$gMods) {
        usort($gMods, function ($a, $b) {
            $order = ['side' => 1, 'add' => 2, 'remove' => 3];
            $ta = strtolower($a['modifier_type'] ?? '');
            $tb = strtolower($b['modifier_type'] ?? '');

            if ($ta != 'add' && $ta != 'remove' && $ta != 'side')
                $va = ($ta == 'info') ? 99 : 1;
            else
                $va = $order[$ta] ?? 99;

            if ($tb != 'add' && $tb != 'remove' && $tb != 'side')
                $vb = ($tb == 'info') ? 99 : 1;
            else
                $vb = $order[$tb] ?? 99;

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

    $kitchenTicket .= ">> " . $item['quantity'] . " X " . clean(($useShortCodes && !empty($item['short_code'])) ? $item['short_code'] : $item['name']) . EOL;

    for ($i = 0; $i < $loopCount; $i++) {
        $currentMods = $groupedMods[$i] ?? [];
        $isTakeaway = false;
        foreach ($currentMods as $m) {
            if ($m['modifier_type'] == 'info' && $m['is_takeaway'] == 1)
                $isTakeaway = true;
        }

        $tag = $isTakeaway ? '[TA]' : '[IN]';
        $componentLabel = isset($subNames[$i]) ? " ** (" . clean($subNames[$i]) . ")" : '';

        $kitchenTicket .= "   $tag #" . ($i + 1) . $componentLabel . EOL;

        foreach ($currentMods as $m) {
            if ($m['modifier_type'] == 'remove') {
                $mName = ($useShortCodes && !empty($m['short_code'])) ? $m['short_code'] : $m['ingredient_name'];
                $kitchenTicket .= "     -- " . clean($mName) . EOL;
            } elseif ($m['modifier_type'] == 'side') {
                $mName = ($useShortCodes && !empty($m['short_code'])) ? $m['short_code'] : $m['ingredient_name'];
                $kitchenTicket .= "     ** " . clean($mName) . EOL;
            } elseif ($m['modifier_type'] == 'add') {
                $mName = ($useShortCodes && !empty($m['short_code'])) ? $m['short_code'] : $m['ingredient_name'];
                $kitchenTicket .= "     ++ " . clean($mName) . EOL;
            }
        }

        if ($i == 0) {
            foreach ($mods as $gm) {
                if ($gm['sub_item_index'] == -1 && $gm['modifier_type'] == 'info' && !empty($gm['note'])) {
                    $kitchenTicket .= "   NOTA: " . clean($gm['note']) . EOL;
                }
            }
        }
        $kitchenTicket .= EOL;
    }
    $kitchenTicket .= str_repeat("=", WIDTH) . EOL;
}
$kitchenTicket .= ".";
?>
?>
<?php
// ... (PHP Logic from lines 1-231 remains unchanged, handled by startLine below)

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<style>
    /* Estilos específicos para la visualización del Ticket */
    .ticket-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        min-height: 80vh;
        padding-top: 2rem;
    }

    .ticket-wrapper {
        background: white;
        /* Siempre blanco para simular papel */
        color: black;
        /* Siempre negro para simular tinta */
        width: 80mm;
        /* Ancho estándar de ticket */
        padding: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        /* Sombra elegante */
        border-radius: 2px;
        /* Bordes casi rectos */
        margin-bottom: 2rem;
        transform: rotate(-0.5deg);
        /* Efecto sutil de imperfección */
    }

    pre.ticket-content {
        font-family: 'Courier New', Courier, monospace;
        font-size: 13px;
        font-weight: bold;
        line-height: 1.2;
        white-space: pre;
        margin: 0 auto;
        display: block;
        width: fit-content;
        text-align: left;
    }

    /* Acciones flotantes o estáticas */
    .ticket-actions {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        justify-content: center;
        margin-bottom: 3rem;
    }

    /* Print Styles */
    @media print {

        .no-print,
        header,
        nav,
        footer {
            display: none !important;
        }

        body,
        .container,
        .ticket-container {
            background: white !important;
            margin: 0 !important;
            padding: 0 !important;
            min-height: auto !important;
            width: auto !important;
        }

        .ticket-wrapper {
            width: 100% !important;
            box-shadow: none !important;
            margin: 0 !important;
            padding: 0 !important;
            transform: none !important;
        }

        pre.ticket-content {
            font-size: 12px;
            /* Ajuste para impresora térmica */
            text-align: left;
            /* Alineación natural impresora */
        }
    }
</style>

<div class="container py-4">
    <div class="row">
        <!-- TICKET CLIENTE -->
        <div class="col-md-6 ticket-container">
            <h4 class="text-white mb-3"><i class="fa fa-user me-2"></i>RECIBO CLIENTE</h4>
            <div class="ticket-wrapper" id="customerTicketWrapper">
                <pre class="ticket-content"><?= $customerTicket ?></pre>
            </div>
            <div class="ticket-actions no-print">
                <button onclick="printSelect('customer')" class="btn btn-primary btn-lg w-100 mb-2">
                    <i class="fa fa-print"></i> Imprimir (Windows)
                </button>
                <button onclick="printServer('customer', this)" class="btn btn-warning btn-lg w-100 text-dark">
                    <i class="fa fa-server"></i> Imprimir (Server USB)
                </button>
            </div>
        </div>

        <!-- TICKET COCINA -->
        <div class="col-md-6 ticket-container">
            <h4 class="text-white mb-3"><i class="fa fa-fire me-2"></i>COMANDA COCINA</h4>
            <div class="ticket-wrapper" id="kitchenTicketWrapper">
                <pre class="ticket-content"><?= $kitchenTicket ?></pre>
            </div>
            <div class="ticket-actions no-print">
                <button onclick="printSelect('kitchen')" class="btn btn-primary btn-lg w-100 mb-2">
                    <i class="fa fa-print"></i> Imprimir (Windows)
                </button>
                <button onclick="printServer('kitchen', this)" class="btn btn-warning btn-lg w-100 text-dark">
                    <i class="fa fa-server"></i> Imprimir (Server USB)
                </button>
            </div>
        </div>
    </div>

    <!-- BOTÓN VOLVER -->
    <div class="text-center mt-4 no-print">
        <hr class="border-secondary">
        <a href="tienda.php" class="btn btn-lg btn-secondary hover-scale px-5">
            <i class="fa fa-arrow-left me-2"></i> Volver a Tienda
        </a>
    </div>
</div>

<style>
    /* Estilos específicos para Impresión Selectiva */
    @media print {
        .ticket-container {
            display: none !important;
        }

        .print-only {
            display: block !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .ticket-wrapper.print-only {
            box-shadow: none !important;
            transform: none !important;
        }
    }
</style>

<script>
    function printSelect(type) {
        // Marcamos el que queremos imprimir
        $('.ticket-container').removeClass('print-only');
        if (type === 'customer') {
            $('#customerTicketWrapper').closest('.ticket-container').addClass('print-only');
        } else {
            $('#kitchenTicketWrapper').closest('.ticket-container').addClass('print-only');
        }
        window.print();
    }

    function printServer(type, btn) {
        const jBtn = $(btn);
        const originalText = jBtn.html();

        jBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Enviando...');

        $.post('../ajax/imprimir_ticket.php', {
            order_id: <?= $orderId ?>,
            type: type
        }, function (res) {
            if (res.status === 'ok') {
                jBtn.removeClass('btn-warning').addClass('btn-success').html('<i class="fa fa-check"></i> ¡Impreso!');
                setTimeout(() => {
                    jBtn.removeClass('btn-success').addClass('btn-warning').html(originalText).prop('disabled', false);
                }, 3000);
            } else {
                alert("❌ Error: " + res.message);
                jBtn.prop('disabled', false).html(originalText);
            }
        }, 'json').fail(function () {
            alert("Error de conexión con el servidor.");
            jBtn.prop('disabled', false).html(originalText);
        });
    }
</script>

<?php require_once '../templates/footer.php'; ?>