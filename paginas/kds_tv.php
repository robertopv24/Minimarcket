<?php
// paginas/kds_tv.php
require_once '../templates/autoload.php';
session_start();

// PERMISOS: Permitimos Admin y Usuario (Cajero/Cocinero)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 1. OBTENER ÓRDENES ACTIVAS
// Solo 'paid' (recién cobradas) y 'preparing' (ya en proceso)
$sql = "SELECT o.id, o.created_at, o.status, u.name as cliente, o.tracking_number
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.status IN ('paid', 'preparing')
        ORDER BY o.created_at ASC"; // FIFO (Primero en entrar, primero en salir)

$stmt = $db->query($sql);
$ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. SEPARAR POR ESTACIONES (Pizza vs Cocina)
$listaPizza = [];
$listaCocina = [];

foreach ($ordenes as $orden) {
    $items = $orderManager->getOrderItems($orden['id']);

    $itemsPizza = [];
    $itemsCocina = [];

    foreach ($items as $item) {
        $prod = $productManager->getProductById($item['product_id']);

        // Buscar modificadores (Sin cebolla, etc)
        $stmtMod = $db->prepare("SELECT m.*, rm.name as mat_name FROM order_item_modifiers m JOIN raw_materials rm ON m.raw_material_id = rm.id WHERE order_item_id = ?");
        $stmtMod->execute([$item['id']]);
        $item['mods'] = $stmtMod->fetchAll(PDO::FETCH_ASSOC);

        // Clasificar
        // Asegúrate de haber ejecutado el ALTER TABLE para 'kitchen_station'
        $station = $prod['kitchen_station'] ?? 'kitchen';

        if ($station == 'pizza') {
            $itemsPizza[] = $item;
        } elseif ($station == 'kitchen') { // Hamburguesas, Fritos
            $itemsCocina[] = $item;
        }
        // 'bar' (Refrescos) se ignora aquí
    }

    if (!empty($itemsPizza)) $listaPizza[] = ['orden' => $orden, 'items' => $itemsPizza];
    if (!empty($itemsCocina)) $listaCocina[] = ['orden' => $orden, 'items' => $itemsCocina];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="15"> <title>KDS Cocina</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #222; color: white; overflow: hidden; height: 100vh; }
        .station-col { height: 100vh; overflow-y: auto; padding: 15px; }
        .station-header { font-weight: 800; text-align: center; text-transform: uppercase; padding: 10px; margin-bottom: 15px; border-radius: 5px; }
        .ticket { background: #fff; color: #000; border-radius: 8px; margin-bottom: 15px; page-break-inside: avoid; }
        .ticket-head { padding: 8px 12px; border-bottom: 2px dashed #ccc; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; }
        .ticket-body { padding: 10px; }
        .item { font-weight: bold; font-size: 1.1rem; border-bottom: 1px solid #eee; padding: 4px 0; }
        .mods { color: #d32f2f; font-size: 0.9rem; margin-left: 20px; font-weight: normal; }

        /* Colores de Estado */
        .status-paid { background-color: #0d6efd; color: white; } /* Azul: Nuevo */
        .status-preparing { background-color: #ffc107; color: black; } /* Amarillo: Cocinando */

        /* Tiempos de espera */
        .late { animation: pulse 2s infinite; border: 4px solid red; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(255, 0, 0, 0.7); } 70% { box-shadow: 0 0 0 10px rgba(255, 0, 0, 0); } 100% { box-shadow: 0 0 0 0 rgba(255, 0, 0, 0); } }
    </style>
</head>
<body>
    <div class="row h-100 g-0">
        <div class="col-6 station-col border-end border-secondary">
            <div class="station-header bg-danger text-white">
                <i class="fa fa-pizza-slice"></i> Estación Pizzas
            </div>

            <?php foreach ($listaPizza as $t):
                $mins = round((time() - strtotime($t['orden']['created_at'])) / 60);
                $isLate = $mins > 25 ? 'late' : '';
                $statusClass = 'status-' . $t['orden']['status'];
            ?>
            <div class="ticket shadow <?= $isLate ?>">
                <div class="ticket-head <?= $statusClass ?>">
                    <span>#<?= $t['orden']['id'] ?> - <?= substr($t['orden']['cliente'],0,12) ?></span>
                    <span>⏱️ <?= $mins ?>m</span>
                </div>
                <div class="ticket-body">
                    <?php foreach ($t['items'] as $i): ?>
                        <div class="item">
                            <?= $i['quantity'] ?>x <?= $i['name'] ?>
                            <?php if ($i['mods']): ?>
                                <div class="mods">
                                    <?php foreach ($i['mods'] as $m): ?>
                                        <div><?= ($m['modifier_type']=='remove'?'❌ SIN ':'➕ EXTRA ') . $m['mat_name'] ?></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="col-6 station-col">
            <div class="station-header bg-warning text-dark">
                <i class="fa fa-hamburger"></i> Estación Caliente
            </div>

            <?php foreach ($listaCocina as $t):
                $mins = round((time() - strtotime($t['orden']['created_at'])) / 60);
                $isLate = $mins > 15 ? 'late' : ''; // Hamburguesas tardan menos
                $statusClass = 'status-' . $t['orden']['status'];
            ?>
            <div class="ticket shadow <?= $isLate ?>">
                <div class="ticket-head <?= $statusClass ?>">
                    <span>#<?= $t['orden']['id'] ?> - <?= substr($t['orden']['cliente'],0,12) ?></span>
                    <span>⏱️ <?= $mins ?>m</span>
                </div>
                <div class="ticket-body">
                    <?php foreach ($t['items'] as $i): ?>
                        <div class="item">
                            <?= $i['quantity'] ?>x <?= $i['name'] ?>
                            <?php if ($i['mods']): ?>
                                <div class="mods">
                                    <?php foreach ($i['mods'] as $m): ?>
                                        <div><?= ($m['modifier_type']=='remove'?'❌ SIN ':'➕ EXTRA ') . $m['mat_name'] ?></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
