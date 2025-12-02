<?php
require_once '../templates/autoload.php';
session_start();

if (!isset($_SESSION['user_id'])) die("Acceso denegado");

$orderId = $_GET['id'] ?? 0;
$order = $orderManager->getOrderById($orderId);

if (!$order) die("Orden no encontrada");

$items = $orderManager->getOrderItems($orderId);
$companyName = $GLOBALS['config']->get('site_name');

// Datos Financieros
$stmtTx = $db->prepare("SELECT SUM(amount_usd_ref) as total_paid FROM transactions WHERE reference_type='order' AND reference_id=? AND type='income'");
$stmtTx->execute([$orderId]);
$paid = $stmtTx->fetchColumn() ?: 0;
$change = $paid - $order['total_price'];

// --- GENERADOR DE VISUALIZACIÓN (TEXTO PLANO PARA NAVEGADOR) ---
// Configuración visual para 58mm (aprox 32 caracteres)
define('WIDTH', 32);
define('EOL', "\n");

function clean($str) {
    $str = strtoupper($str);
    $str = str_replace(['Á','É','Í','Ó','Ú','Ñ'], ['A','E','I','O','U','N'], $str);
    return $str;
}

function center($str) {
    $str = clean($str);
    $len = strlen($str);
    if ($len >= WIDTH) return substr($str, 0, WIDTH);
    $pad = floor((WIDTH - $len) / 2);
    return str_repeat(' ', $pad) . $str;
}

function row($col1, $col2) {
    $col1 = clean($col1);
    $col2 = clean($col2);
    $len1 = strlen($col1);
    $len2 = strlen($col2);
    $space = WIDTH - $len1 - $len2;
    if ($space < 1) $space = 1;
    return $col1 . str_repeat(' ', $space) . $col2;
}

function line() {
    return str_repeat('-', WIDTH) . EOL;
}

// ---------------------------------------------------------
// CONSTRUCCIÓN DEL TICKET VISUAL (STRING)
// ---------------------------------------------------------
$txt = "";

// 1. CABECERA
$txt .= center($companyName) . EOL;
$txt .= center("ORDEN: #" . str_pad($orderId, 6, '0', STR_PAD_LEFT)) . EOL;
$txt .= center(date('d/m/Y h:i A', strtotime($order['created_at']))) . EOL;
$txt .= EOL;
$txt .= "CAJERO: " . clean(substr($order['customer_name'], 0, 24)) . EOL;
$txt .= "CLIENTE: " . clean(substr($order['shipping_address'] ?? 'MOSTRADOR', 0, 23)) . EOL;
$txt .= line();
$txt .= "CANT DESCRIPCION           TOTAL" . EOL;
$txt .= line();

// 2. ÍTEMS (FINANCIERO)
foreach ($items as $item) {
    $totalItem = $item['price'] * $item['quantity'];
    $mods = $orderManager->getItemModifiers($item['id']);

    // Línea Principal
    $qtyName = $item['quantity'] . " " . clean(substr($item['name'], 0, 20));
    $priceTxt = number_format($totalItem, 2);
    $txt .= row($qtyName, $priceTxt) . EOL;

    // Desglose Combo (Visual)
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
            $txt .= "   INC: " . substr(implode(",", $subs), 0, 24) . EOL;
        }
    }

    // Extras Cobrados
    foreach ($mods as $m) {
        if ($m['modifier_type'] == 'add' && $m['price_adjustment_usd'] > 0) {
            $extraName = " + " . clean($m['ingredient_name']);
            $extraTotal = number_format($m['price_adjustment_usd'] * $item['quantity'], 2);
            $txt .= row($extraName, $extraTotal) . EOL;
        }
    }
}

// 3. TOTALES
$txt .= line();
$txt .= row("TOTAL:", "$" . number_format($order['total_price'], 2)) . EOL;

if ($paid > 0) {
    $txt .= row("PAGADO:", "$" . number_format($paid, 2)) . EOL;
    $txt .= row("CAMBIO:", "$" . number_format(max(0, $change), 2)) . EOL;
}

$txt .= EOL;
$txt .= center("*** GRACIAS POR SU COMPRA ***") . EOL;
$txt .= EOL . EOL;

// 4. COMANDA COCINA (OPERATIVO)
$txt .= center("- - - CORTE COCINA - - -") . EOL;
$txt .= EOL;
$txt .= center("ORDEN #" . $orderId) . EOL;
$txt .= center(clean(substr($order['shipping_address'] ?? '', 0, 32))) . EOL;
$txt .= EOL;

foreach ($items as $item) {
    $mods = $orderManager->getItemModifiers($item['id']);

    // Agrupar modificadores por índice
    $groupedMods = [];
    foreach($mods as $m) { $groupedMods[$m['sub_item_index']][] = $m; }

    // Recuperar Nombres de sub-ítems
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

    // Cabecera Grande
    $txt .= ">> " . $item['quantity'] . " X " . clean($item['name']) . EOL;

    // Bucle Sub-Items
    for($i = 0; $i < $loop; $i++) {
        $currentMods = $groupedMods[$i] ?? [];
        $isTakeaway = false;
        foreach($currentMods as $m) {
            if($m['modifier_type'] == 'info' && $m['is_takeaway'] == 1) $isTakeaway = true;
        }

        $tag = $isTakeaway ? '[LLEVAR]' : '[MESA]';
        $specName = isset($subNames[$i]) ? $subNames[$i] : '';

        // Línea Detalle: [MESA] #1 PIZZA
        $txt .= "   $tag #".($i+1)." $specName" . EOL;

        // Modificadores
        foreach($currentMods as $m) {
            if($m['modifier_type'] == 'remove') {
                $txt .= "     -- SIN " . clean($m['ingredient_name']) . EOL;
            } elseif($m['modifier_type'] == 'add') {
                $txt .= "     ++ EXTRA " . clean($m['ingredient_name']) . EOL;
            }
        }

        // Nota General (Solo en el 1ero)
        if ($i == 0) {
            foreach($mods as $gm) {
                if($gm['sub_item_index'] == -1 && $gm['modifier_type'] == 'info' && !empty($gm['note'])) {
                    $txt .= "   NOTA: " . clean($gm['note']) . EOL;
                }
            }
        }
        $txt .= EOL;
    }
    $txt .= str_repeat("=", WIDTH) . EOL;
}
$txt .= "."; // Punto final para asegurar margen
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket #<?= $orderId ?></title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* ESTILOS DE IMPRESIÓN (NAVEGADOR) */
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; }
            @page { margin: 0; size: auto; }
        }

        body {
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            padding-top: 20px;
        }

        /* SIMULACIÓN DE PAPEL TÉRMICO VISUAL */
        .ticket-container {
            background: white;
            /* Ancho visual en pantalla (un poco más ancho que 58mm para leer mejor) */
            width: 58mm;
            padding: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-bottom: 50px;
        }

        /* EL TICKET REAL (TEXTO PLANO) */
        pre {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            font-weight: bold;
            line-height: 1.2;
            white-space: pre-wrap;
            margin: 0;
            color: #000;
            /* Esto fuerza el ancho exacto al imprimir desde navegador */
            width: 100%;
        }

        /* BOTONES DE ACCIÓN */
        .actions {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 1000;
        }

        .btn {
            border: none;
            padding: 12px 20px;
            border-radius: 50px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
            color: white;
            font-size: 14px;
            transition: transform 0.1s;
            text-align: center;
            min-width: 200px;
        }
        .btn:active { transform: scale(0.95); }
        .btn:disabled { opacity: 0.7; cursor: not-allowed; }

        .btn-browser { background-color: #007bff; }
        .btn-server  { background-color: #e67e22; } /* Naranja Fedora */
        .btn-back    { background-color: #6c757d; }

    </style>
</head>
<body>

    <div class="ticket-container">
        <pre><?= $txt ?></pre>
    </div>

    <div class="actions no-print">

        <button onclick="window.print()" class="btn btn-browser">
            🪟 Imprimir (Navegador)
        </button>

        <button id="btnLinux" class="btn btn-server">
            🐧 Imprimir (Servidor USB)
        </button>

        <a href="tienda.php" style="text-decoration:none;">
            <button class="btn btn-back">⬅ Volver a Tienda</button>
        </a>
    </div>

    <script>
        $(document).ready(function() {
            $('#btnLinux').click(function() {
                const btn = $(this);
                const originalText = btn.text();

                btn.prop('disabled', true).text('⏳ Enviando a CUPS...');

                $.post('../ajax/imprimir_ticket.php', { order_id: <?= $orderId ?> }, function(res) {
                    if(res.status === 'ok') {
                        // Feedback visual positivo
                        btn.css('background-color', '#28a745').text('✅ ¡Impreso!');
                        setTimeout(() => {
                            btn.css('background-color', '#e67e22').text(originalText).prop('disabled', false);
                        }, 3000);
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
