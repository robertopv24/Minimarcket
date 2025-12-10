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
$ticket = "";

// CABECERA
$ticket .= center($companyName) . EOL;
$ticket .= center("ORDEN #" . str_pad($orderId, 6, '0', STR_PAD_LEFT)) . EOL;
$ticket .= center(date('d/m/Y h:i A', strtotime($order['created_at']))) . EOL;
$ticket .= EOL;
$ticket .= "CAJERO : " . clean(substr($order['customer_name'], 0, 20)) . EOL;
$ticket .= "CLIENTE: " . clean(substr($order['shipping_address'] ?? 'MOSTRADOR', 0, 20)) . EOL;
$ticket .= line();

// ÍTEMS
$ticket .= "CANT DESCRIPCION           TOTAL" . EOL;
$ticket .= line();

foreach ($items as $item) {
    $totalItem = $item['price'] * $item['quantity'];
    $mods = $orderManager->getItemModifiers($item['id']);

    $qtyName = str_pad($item['quantity'], 2, ' ', STR_PAD_LEFT) . " " . clean(substr($item['name'], 0, 18));
    $priceTxt = number_format($totalItem, 2);
    $ticket .= row($qtyName, $priceTxt) . EOL;

    // Desglose Combo
    if ($item['product_type'] == 'compound') {
        $comps = $productManager->getProductComponents($item['product_id']);
        $subs = [];
        foreach ($comps as $c) {
            if ($c['component_type'] == 'product') {
                $p = $productManager->getProductById($c['component_id']);
                $subs[] = clean($p['name']);
            }
        }
        if (!empty($subs)) {
            $incStr = " > INC: " . implode(",", $subs);
            if (strlen($incStr) > WIDTH) {
                $ticket .= substr($incStr, 0, WIDTH) . EOL;
                $rest = substr($incStr, WIDTH);
                if ($rest)
                    $ticket .= "   " . substr($rest, 0, WIDTH - 3) . EOL;
            } else {
                $ticket .= $incStr . EOL;
            }
        }
    }

    // Extras Cobrados
    foreach ($mods as $m) {
        if ($m['modifier_type'] == 'add' && $m['price_adjustment_usd'] > 0) {
            $extraName = "  + " . clean($m['ingredient_name']);
            $extraPrice = number_format($m['price_adjustment_usd'] * $item['quantity'], 2);
            $ticket .= row($extraName, $extraPrice) . EOL;
        }
    }
}

$ticket .= line();

// TOTALES
$ticket .= row("TOTAL:", "$" . number_format($order['total_price'], 2)) . EOL;

// PAGOS DETALLADOS
foreach ($payments as $pay) {
    $sym = ($pay['currency'] == 'USD') ? '$' : 'Bs ';
    $ticket .= row(substr($pay['method'], 0, 18) . ":", $sym . number_format($pay['amount'], 2)) . EOL;
}

// CAMBIO REAL
if ($changeTx) {
    $sym = ($changeTx['currency'] == 'USD') ? '$' : 'Bs ';
    $ticket .= row("SU CAMBIO:", $sym . number_format($changeTx['amount'], 2)) . EOL;
} else {
    $ticket .= row("SU CAMBIO:", "$0.00") . EOL;
}

$ticket .= EOL;
$ticket .= center("*** GRACIAS POR SU COMPRA ***") . EOL;
$ticket .= EOL . EOL;

// COMANDA COCINA
$ticket .= center("- - - CORTE COCINA - - -") . EOL;
$ticket .= EOL;
$ticket .= center("ORDEN #" . $orderId) . EOL;
$ticket .= center(clean(substr($order['shipping_address'] ?? '', 0, 30))) . EOL;
$ticket .= line();

foreach ($items as $item) {
    $mods = $orderManager->getItemModifiers($item['id']);
    $groupedMods = [];
    foreach ($mods as $m) {
        $groupedMods[$m['sub_item_index']][] = $m;
    }

    $subNames = [];
    if ($item['product_type'] == 'compound') {
        $comps = $productManager->getProductComponents($item['product_id']);
        foreach ($comps as $c) {
            if ($c['component_type'] == 'product') {
                $p = $productManager->getProductById($c['component_id']);
                for ($k = 0; $k < $c['quantity']; $k++)
                    $subNames[] = clean($p['name']);
            }
        }
    }

    $loop = $item['quantity'];
    if ($item['product_type'] == 'compound' && !empty($subNames))
        $loop = count($subNames);

    $ticket .= ">> " . $item['quantity'] . " X " . clean($item['name']) . EOL;

    for ($i = 0; $i < $loop; $i++) {
        $currentMods = $groupedMods[$i] ?? [];
        $isTakeaway = false;
        foreach ($currentMods as $m) {
            if ($m['modifier_type'] == 'info' && $m['is_takeaway'] == 1)
                $isTakeaway = true;
        }

        $tag = $isTakeaway ? '[LLEVAR]' : '[MESA]';
        $specName = isset($subNames[$i]) ? $subNames[$i] : '';

        $ticket .= "   $tag #" . ($i + 1) . " $specName" . EOL;

        foreach ($currentMods as $m) {
            if ($m['modifier_type'] == 'remove')
                $ticket .= "     -- SIN " . clean($m['ingredient_name']) . EOL;
            if ($m['modifier_type'] == 'add')
                $ticket .= "     ++ EXTRA " . clean($m['ingredient_name']) . EOL;
        }

        if ($i == 0) {
            foreach ($mods as $gm) {
                if ($gm['sub_item_index'] == -1 && $gm['modifier_type'] == 'info' && !empty($gm['note'])) {
                    $ticket .= "   NOTA: " . clean($gm['note']) . EOL;
                }
            }
        }
        $ticket .= EOL;
    }
    $ticket .= str_repeat("=", WIDTH) . EOL;
}
$ticket .= ".";
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
        white-space: pre-wrap;
        margin: 0;
        text-align: center;
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

<div class="container ticket-container">

    <!-- TICKET DE PAPEL -->
    <div class="ticket-wrapper">
        <pre class="ticket-content"><?= $ticket ?></pre>
    </div>

    <!-- BOTONES DE ACCIÓN -->
    <div class="ticket-actions no-print">
        <button onclick="window.print()" class="btn btn-lg btn-primary hover-scale">
            <i class="fa fa-print"></i> Imprimir (Windows)
        </button>

        <button id="btnLinux" class="btn btn-lg btn-warning hover-scale text-dark">
            <i class="fa fa-server"></i> Imprimir (Server USB)
        </button>

        <a href="tienda.php" class="btn btn-lg btn-secondary hover-scale">
            <i class="fa fa-arrow-left"></i> Volver a Tienda
        </a>
    </div>

</div>

<script>
    $(document).ready(function () {
        $('#btnLinux').click(function () {
            const btn = $(this);
            const originalText = btn.html();

            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Enviando...');

            $.post('../ajax/imprimir_ticket.php', { order_id: <?= $orderId ?> }, function (res) {
                if (res.status === 'ok') {
                    btn.removeClass('btn-warning').addClass('btn-success').html('<i class="fa fa-check"></i> ¡Impreso!');
                    setTimeout(() => {
                        btn.removeClass('btn-success').addClass('btn-warning').html(originalText).prop('disabled', false);
                    }, 3000);
                } else {
                    alert("❌ Error: " + res.message);
                    btn.prop('disabled', false).html(originalText);
                }
            }, 'json')
                .fail(function () {
                    alert("Error de conexión con el servidor.");
                    btn.prop('disabled', false).html(originalText);
                });
        });
    });
</script>

<?php require_once '../templates/footer.php'; ?>