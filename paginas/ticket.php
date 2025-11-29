<?php
require_once '../templates/autoload.php';
session_start();

if (!isset($_SESSION['user_id'])) die("Acceso denegado");

$orderId = $_GET['id'] ?? 0;
$order = $orderManager->getOrderById($orderId);

if (!$order) die("Orden no encontrada");

// Obtener items y configuración global
$items = $orderManager->getOrderItems($orderId);
$companyName = $GLOBALS['config']->get('site_name');

// Calcular cambio si existe
// Buscamos transacciones asociadas para ver cuánto pagó
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
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            width: 80mm; /* Ancho estándar térmica */
            margin: 0 auto;
            background: #fff;
            color: #000;
        }
        .header, .footer { text-align: center; margin-bottom: 10px; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 5px 0; }
        .item-row { margin-bottom: 8px; padding-bottom: 2px; }
        .item-header { display: flex; justify-content: space-between; font-weight: bold; }
        .item-detail { font-size: 11px; padding-left: 10px; }
        .badge-takeaway { background: #000; color: #fff; padding: 1px 3px; border-radius: 3px; font-size: 10px; }
        .badge-dinein { border: 1px solid #000; padding: 0 3px; border-radius: 3px; font-size: 10px; }
        .totals { text-align: right; margin-top: 10px; font-size: 14px; }

        @media print {
            .no-print { display: none; }
            body { margin: 0; padding: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="padding: 10px; text-align: center; background: #eee; margin-bottom: 10px;">
        <button onclick="window.print()" style="font-size: 16px; padding: 5px 15px;">🖨️ Imprimir</button>
        <a href="tienda.php" style="margin-left: 10px;">Volver a Tienda</a>
    </div>

    <div class="header">
        <h3 style="margin:0"><?= strtoupper($companyName) ?></h3>
        <p>ORDEN #<?= str_pad($orderId, 6, '0', STR_PAD_LEFT) ?></p>
        <p><?= date('d/m/Y h:i A', strtotime($order['created_at'])) ?></p>
        <p>Cliente: <?= strtoupper($order['customer_name'] ?? 'General') ?></p>
    </div>

    <div class="divider"></div>

    <div class="items">
        <?php foreach ($items as $item): ?>
            <?php
                // Obtener modificadores
                $mods = $orderManager->getItemModifiers($item['id']);

                // Determinar etiqueta de consumo
                // NOTA: Asegúrate de haber agregado la columna consumption_type en order_items
                $consType = $item['consumption_type'] ?? 'takeaway';
                $consLabel = ($consType == 'takeaway') ? '<span class="badge-takeaway">LLEVAR</span>' : '<span class="badge-dinein">MESA</span>';
            ?>
            <div class="item-row">
                <div class="item-header">
                    <span><?= $item['quantity'] ?> x <?= substr($item['name'], 0, 18) ?></span>
                    <span>$<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                </div>

                <div style="margin-bottom: 2px;"><?= $consLabel ?></div>

                <?php if (!empty($mods)): ?>
                    <div class="item-detail">
                        <?php foreach ($mods as $m): ?>
                            <?php if($m['modifier_type'] == 'remove'): ?>
                                <div>[ - ] SIN <?= $m['ingredient_name'] ?></div>
                            <?php elseif($m['modifier_type'] == 'add'): ?>
                                <div>[ + ] EXTRA <?= $m['ingredient_name'] ?></div>
                            <?php elseif($m['modifier_type'] == 'info'): ?>
                                <div>(i) <?= $m['note'] ?></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="divider"></div>

    <div class="totals">
        <p>TOTAL: <span class="bold">$<?= number_format($order['total_price'], 2) ?></span></p>
        <?php if($paid > 0): ?>
            <p style="font-size: 12px;">Pagado: $<?= number_format($paid, 2) ?></p>
            <p style="font-size: 12px;">Cambio: $<?= number_format(max(0, $change), 2) ?></p>
        <?php endif; ?>
    </div>

    <div class="footer">
        <p>¡Gracias por su compra!</p>
        <p class="bold">*** COCINA ***</p> </div>

</body>
</html>
