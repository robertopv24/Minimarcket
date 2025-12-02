<?php
require_once '../templates/autoload.php';
session_start();

if (!isset($_SESSION['user_id'])) die("Acceso denegado");

$orderId = $_GET['id'] ?? 0;
$order = $orderManager->getOrderById($orderId);

if (!$order) die("Orden no encontrada");

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

function clean($str) {
    $str = strtoupper(trim($str));
    $str = str_replace(['Á','É','Í','Ó','Ú','Ñ'], ['A','E','I','O','U','N'], $str);
    return preg_replace('/[^A-Z0-9 \.,\-\(\)\#\$\%\:\/]/', '', $str);
}

function center($str) {
    $str = clean($str);
    $len = strlen($str);
    if ($len >= WIDTH) return substr($str, 0, WIDTH);
    $pad = floor((WIDTH - $len) / 2);
    return str_repeat(' ', $pad) . $str . str_repeat(' ', WIDTH - $len - $pad);
}

function row($left, $right) {
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

function line() {
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
        foreach($comps as $c) {
            if($c['component_type'] == 'product') {
                $p = $productManager->getProductById($c['component_id']);
                $subs[] = clean($p['name']);
            }
        }
        if(!empty($subs)) {
            $incStr = " > INC: " . implode(",", $subs);
            if (strlen($incStr) > WIDTH) {
                $ticket .= substr($incStr, 0, WIDTH) . EOL;
                $rest = substr($incStr, WIDTH);
                if($rest) $ticket .= "   " . substr($rest, 0, WIDTH-3) . EOL;
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
    foreach($mods as $m) { $groupedMods[$m['sub_item_index']][] = $m; }

    $subNames = [];
    if ($item['product_type'] == 'compound') {
        $comps = $productManager->getProductComponents($item['product_id']);
        foreach($comps as $c) {
            if($c['component_type'] == 'product') {
                $p = $productManager->getProductById($c['component_id']);
                for($k=0; $k < $c['quantity']; $k++) $subNames[] = clean($p['name']);
            }
        }
    }

    $loop = $item['quantity'];
    if ($item['product_type'] == 'compound' && !empty($subNames)) $loop = count($subNames);

    $ticket .= ">> " . $item['quantity'] . " X " . clean($item['name']) . EOL;

    for($i = 0; $i < $loop; $i++) {
        $currentMods = $groupedMods[$i] ?? [];
        $isTakeaway = false;
        foreach($currentMods as $m) {
            if($m['modifier_type'] == 'info' && $m['is_takeaway'] == 1) $isTakeaway = true;
        }

        $tag = $isTakeaway ? '[LLEVAR]' : '[MESA]';
        $specName = isset($subNames[$i]) ? $subNames[$i] : '';

        $ticket .= "   $tag #" . ($i+1) . " $specName" . EOL;

        foreach($currentMods as $m) {
            if($m['modifier_type'] == 'remove') $ticket .= "     -- SIN " . clean($m['ingredient_name']) . EOL;
            if($m['modifier_type'] == 'add')    $ticket .= "     ++ EXTRA " . clean($m['ingredient_name']) . EOL;
        }

        if ($i == 0) {
            foreach($mods as $gm) {
                if($gm['sub_item_index'] == -1 && $gm['modifier_type'] == 'info' && !empty($gm['note'])) {
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
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket #<?= $orderId ?></title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; }
            @page { margin: 0; size: auto; }
        }
        body {
            background-color: #e9ecef;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            font-family: sans-serif;
            padding-top: 20px;
        }
        .ticket-wrapper {
            background: white;
            width: 80mm; /* Visualización en pantalla */
            padding: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            margin-bottom: 60px;
            border-radius: 5px;
        }
        pre {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            font-weight: bold;
            line-height: 1.2;
            white-space: pre-wrap;
            margin: 0;
            color: #000;
            width: 100%;
            text-align: center; /* Centrado general */
        }
        .actions {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 9999;
        }
        .btn {
            border: none;
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
            color: white;
            font-size: 14px;
            min-width: 220px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.2s;
        }
        .btn:hover { transform: translateY(-2px); shadow: 0 6px 8px rgba(0,0,0,0.3); }
        .btn:active { transform: translateY(0); }
        .btn-browser { background-color: #0d6efd; }
        .btn-server  { background-color: #fd7e14; }
        .btn-back    { background-color: #6c757d; }
    </style>
</head>
<body>

    <div class="ticket-wrapper">
        <pre><?= $ticket ?></pre>
    </div>

    <div class="actions no-print">
        <button onclick="window.print()" class="btn btn-browser">🪟 Imprimir (Windows)</button>
        <button id="btnLinux" class="btn btn-server">🐧 Imprimir (Server USB)</button>
        <a href="tienda.php" style="text-decoration:none;"><button class="btn btn-back">⬅ Volver a Tienda</button></a>
    </div>

    <script>
        $(document).ready(function() {
            $('#btnLinux').click(function() {
                const btn = $(this);
                const originalText = btn.text();

                btn.prop('disabled', true).text('Enviando...');

                $.post('../ajax/imprimir_ticket.php', { order_id: <?= $orderId ?> }, function(res) {
                    if(res.status === 'ok') {
                        btn.css('background-color', '#198754').text('✅ ¡Enviado!');
                        setTimeout(() => {
                            btn.css('background-color', '#fd7e14').text(originalText).prop('disabled', false);
                        }, 2000);
                    } else {
                        alert("❌ Error: " + res.message);
                        btn.prop('disabled', false).text(originalText);
                    }
                }, 'json')
                .fail(function() {
                    alert("Error de conexión con el servidor.");
                    btn.prop('disabled', false).text(originalText);
                });
            });
        });
    </script>

</body>
</html>
