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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket #<?= $orderId ?></title>
    <style>
        @page { margin: 0; padding: 0; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 10px;
            width: 56mm;
            margin: 0 auto;
            padding: 2px;
            background: #fff;
            color: #000;
            text-align: center;
        }

        /* Utilidades */
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 5px 0; }

        /* Sección Cliente */
        .item-row { margin-bottom: 4px; border-bottom: 1px dotted #ccc; padding-bottom: 2px; text-align: left; }
        .item-flex { display: flex; align-items: flex-start; }
        .qty-col { width: 10%; font-weight: bold; }
        .name-col { width: 60%; }
        .price-col { width: 30%; text-align: right; }

        .sub-info { font-size: 9px; color: #444; padding-left: 5px; display: block; font-style: italic; }
        .extra-row { font-size: 9px; padding-left: 5px; display: flex; justify-content: space-between; }

        /* Sección Cocina */
        .cut-line {
            border-top: 2px dashed #000;
            margin-top: 25px;    /* Espacio superior antes de la línea */
            padding-top: 10px;   /* ESPACIO CLAVE: Separa la línea del texto */
            margin-bottom: 10px; /* Espacio inferior antes de los items */
            font-size: 10px;
            font-weight: bold;
        }

        .kitchen-item { margin-bottom: 10px; border-bottom: 2px solid #000; padding-bottom: 5px; }
        .kitchen-subitem { margin-top: 4px; padding: 2px 0; border-bottom: 1px dotted #999; }

        /* Etiquetas */
        .tag-takeaway { background: #000; color: #fff; padding: 1px 3px; font-weight: bold; border-radius: 2px; display:inline-block; }
        .tag-dinein { border: 1px solid #000; padding: 0 2px; font-weight: bold; border-radius: 2px; display:inline-block; }

        .sub-name { font-weight: 800; font-size: 11px; text-decoration: underline; margin-left: 3px; }
        .mod-remove { text-decoration: line-through; }
        .mod-add { font-weight: bold; }

        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="padding:5px; background:#eee; margin-bottom:5px; font-size:12px;">
        <a href="tienda.php" style="text-decoration:none;">⬅ Volver</a>
        <button onclick="window.print()" style="margin-left:5px;">🖨️</button>
    </div>

    <div class="header">
        <h3 style="margin:2px 0;"><?= strtoupper($companyName) ?></h3>
        <p>ORDEN: <strong>#<?= str_pad($orderId, 6, '0', STR_PAD_LEFT) ?></strong></p>
        <p><?= date('d/m/y h:i A', strtotime($order['created_at'])) ?></p>

        <div style="text-align: left; margin-top: 5px; font-size: 9px; border-bottom: 1px solid #000; padding-bottom: 2px;">
            <div>Cajero: <?= strtoupper(substr($order['customer_name'],0,15)) ?></div>
            <div>Cliente: <?= strtoupper(substr($order['shipping_address'] ?? 'Mostrador',0,20)) ?></div>
        </div>
    </div>

    <div style="margin-top: 5px;">
        <?php foreach ($items as $item):
            $totalItem = $item['price'] * $item['quantity'];
            $mods = $orderManager->getItemModifiers($item['id']);

            // Rayos X para Combos
            $subItemNames = [];
            if ($item['product_type'] == 'compound') {
                $comps = $productManager->getProductComponents($item['product_id']);
                foreach($comps as $c) {
                    if($c['component_type'] == 'product') {
                        $p = $productManager->getProductById($c['component_id']);
                        for($k=0; $k < $c['quantity']; $k++) $subItemNames[] = $p['name'];
                    }
                }
            }
        ?>
            <div class="item-row">
                <div class="item-flex">
                    <div class="qty-col"><?= $item['quantity'] ?></div>
                    <div class="name-col"><?= strtoupper($item['name']) ?></div>
                    <div class="price-col">$<?= number_format($totalItem, 2) ?></div>
                </div>

                <?php if (!empty($subItemNames)): ?>
                    <span class="sub-info">Inc: <?= implode(", ", $subItemNames) ?></span>
                <?php endif; ?>

                <?php foreach ($mods as $m): ?>
                    <?php if ($m['modifier_type'] == 'add' && $m['price_adjustment_usd'] > 0): ?>
                        <div class="extra-row">
                            <span>+ <?= $m['ingredient_name'] ?></span>
                            <span>$<?= number_format($m['price_adjustment_usd'] * $item['quantity'], 2) ?></span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="divider"></div>

    <div class="totals text-right">
        <div style="font-size: 12px; font-weight: bold;">TOTAL: $<?= number_format($order['total_price'], 2) ?></div>
        <?php if($paid > 0): ?>
            <div style="font-size:9px; margin-top:2px;">
                Pago: $<?= number_format($paid, 2) ?><br>
                Cambio: $<?= number_format(max(0, $change), 2) ?>
            </div>
        <?php endif; ?>
    </div>

    <div style="margin-top:10px;">
        <p>¡Gracias por su compra!</p>
    </div>

    <div class="cut-line">✂ COCINA #<?= $orderId ?> ✂</div>

    <div style="margin-top:5px; text-align: left;">
        <?php foreach ($items as $item):
            $mods = $orderManager->getItemModifiers($item['id']);

            // Agrupar modificadores
            $groupedMods = [];
            foreach($mods as $m) { $groupedMods[$m['sub_item_index']][] = $m; }

            // Recuperar Nombres de sub-ítems para la cocina
            $subNames = [];
            if ($item['product_type'] == 'compound') {
                $comps = $productManager->getProductComponents($item['product_id']);
                foreach($comps as $c) {
                    if($c['component_type'] == 'product') {
                        $p = $productManager->getProductById($c['component_id']);
                        for($k=0; $k < $c['quantity']; $k++) $subNames[] = $p['name'];
                    }
                }
            }

            // Calcular bucle
            $loop = $item['quantity'];
            if ($item['product_type'] == 'compound' && !empty($subNames)) {
                $loop = count($subNames);
            }
        ?>

            <div class="kitchen-item">
                <div style="font-size: 11px; font-weight: 900; text-align: center; background: #eee;">
                    <?= $item['quantity'] ?> x <?= strtoupper($item['name']) ?>
                </div>

                <?php for($i = 0; $i < $loop; $i++):
                    $currentMods = $groupedMods[$i] ?? [];

                    // Estado
                    $isTakeaway = false;
                    foreach($currentMods as $m) {
                        if($m['modifier_type'] == 'info' && $m['is_takeaway'] == 1) $isTakeaway = true;
                    }
                    $tag = $isTakeaway
                        ? '<span class="tag-takeaway">LLEVAR</span>'
                        : '<span class="tag-dinein">MESA</span>';

                    // Nombre Específico (Ej: Pizza Margarita)
                    $specificName = isset($subNames[$i]) ? strtoupper($subNames[$i]) : '';
                ?>
                    <div class="kitchen-subitem">
                        <div style="margin-bottom:1px;">
                            <?= $tag ?> <strong>#<?= $i+1 ?></strong>
                            <?php if($specificName): ?>
                                <span class="sub-name"><?= $specificName ?></span>
                            <?php endif; ?>
                        </div>

                        <?php foreach($currentMods as $m): ?>
                            <?php if($m['modifier_type'] == 'remove'): ?>
                                <div class="mod-remove">NO <?= strtoupper($m['ingredient_name']) ?></div>
                            <?php elseif($m['modifier_type'] == 'add'): ?>
                                <div class="mod-add">+ <?= strtoupper($m['ingredient_name']) ?></div>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <?php if ($i == 0):
                            foreach($mods as $gm) {
                                if($gm['sub_item_index'] == -1 && $gm['modifier_type'] == 'info' && !empty($gm['note'])) {
                                    echo '<div style="background:#000; color:#fff; font-style:italic; padding:1px; margin-top:2px;">⚠️ ' . $gm['note'] . '</div>';
                                }
                            }
                        endif; ?>
                    </div>
                <?php endfor; ?>
            </div>

        <?php endforeach; ?>
    </div>

</body>
</html>
