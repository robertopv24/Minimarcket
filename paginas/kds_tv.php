<?php
// paginas/kds_tv.php
require_once '../templates/autoload.php';
session_start();

// 1. CONFIGURACIÓN DE TIEMPO (CRÍTICO)
// Forzamos la zona horaria para evitar desfases de 4 horas
date_default_timezone_set('America/Caracas');

// PERMISOS
require_once '../templates/kitchen_check.php';

// 2. OBTENER ÓRDENES
$sql = "SELECT o.id, o.created_at, o.status, u.name as cliente, o.shipping_address
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.status IN ('paid', 'preparing')
        ORDER BY o.created_at ASC";

$stmt = $db->query($sql);
$ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. PROCESAMIENTO
$listaPizza = [];
$listaCocina = [];

foreach ($ordenes as $orden) {
    $sqlItems = "SELECT oi.*, p.name, p.product_type, p.kitchen_station
                 FROM order_items oi
                 JOIN products p ON oi.product_id = p.id
                 WHERE oi.order_id = ?";
    $stmtItems = $db->prepare($sqlItems);
    $stmtItems->execute([$orden['id']]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    $itemsParaPizza = [];
    $itemsParaCocina = [];

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
                        $subNames[] = $p['name'];
                }
            }
        }

        $loop = ($item['product_type'] == 'compound' && !empty($subNames)) ? count($subNames) : $item['quantity'];

        $visualSubs = [];
        for ($i = 0; $i < $loop; $i++) {
            $currentMods = $groupedMods[$i] ?? [];
            $isTakeaway = false;
            foreach ($currentMods as $m) {
                if ($m['modifier_type'] == 'info' && $m['is_takeaway'] == 1)
                    $isTakeaway = true;
            }

            $ingredientes = [];
            foreach ($currentMods as $m) {
                if ($m['modifier_type'] == 'remove')
                    $ingredientes[] = "NO " . $m['ingredient_name'];
                if ($m['modifier_type'] == 'add')
                    $ingredientes[] = "EXTRA " . $m['ingredient_name'];
            }

            $note = "";
            if ($i == 0) {
                foreach ($mods as $gm) {
                    if ($gm['sub_item_index'] == -1 && $gm['modifier_type'] == 'info')
                        $note = $gm['note'];
                }
            }

            $visualSubs[] = [
                'num' => $i + 1,
                'name' => isset($subNames[$i]) ? $subNames[$i] : '',
                'is_takeaway' => $isTakeaway,
                'mods' => $ingredientes,
                'note' => $note
            ];
        }

        $itemProcesado = [
            'qty' => $item['quantity'],
            'name' => $item['name'],
            'subs' => $visualSubs
        ];

        if ($item['kitchen_station'] == 'pizza') {
            $itemsParaPizza[] = $itemProcesado;
        } elseif ($item['kitchen_station'] == 'kitchen') {
            $itemsParaCocina[] = $itemProcesado;
        }
    }

    if (!empty($itemsParaPizza))
        $listaPizza[] = ['info' => $orden, 'items' => $itemsParaPizza];
    if (!empty($itemsParaCocina))
        $listaCocina[] = ['info' => $orden, 'items' => $itemsParaCocina];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="15">
    <title>KDS Cocina</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #1a1a1a;
            color: white;
            overflow: hidden;
            height: 100vh;
            font-family: 'Segoe UI', sans-serif;
        }

        .station-col {
            height: 100vh;
            overflow-y: auto;
            padding: 10px;
            border-right: 2px solid #444;
        }

        .station-header {
            font-weight: 900;
            text-align: center;
            text-transform: uppercase;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 1.5rem;
            letter-spacing: 2px;
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
        }

        .header-pizza {
            background: linear-gradient(45deg, #d32f2f, #b71c1c);
        }

        .header-cocina {
            background: linear-gradient(45deg, #f57f17, #e65100);
        }

        .ticket {
            background: #fff;
            color: #000;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .ticket-head {
            padding: 10px 15px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .status-paid {
            background-color: #0d6efd;
        }

        .status-preparing {
            background-color: #ffca28;
            color: #000;
        }

        .ticket-body {
            padding: 10px 15px;
        }

        .main-item {
            font-size: 1.2rem;
            font-weight: 800;
            border-bottom: 2px solid #eee;
            margin-top: 10px;
            padding-bottom: 5px;
        }

        .sub-item {
            margin-top: 5px;
            padding-left: 10px;
            border-left: 3px solid #ccc;
            font-size: 1rem;
            display: flex;
            flex-direction: column;
        }

        .tag {
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.85rem;
            display: inline-block;
            margin-right: 5px;
        }

        .tag-takeaway {
            background: #000;
            color: #fff;
        }

        .tag-dinein {
            background: #fff;
            color: #000;
            border: 2px solid #000;
        }

        .mods-box {
            margin-top: 2px;
        }

        .mod-bad {
            color: #d32f2f;
            font-weight: bold;
        }

        .mod-good {
            color: #2e7d32;
            font-weight: bold;
        }

        .note-box {
            background: #fff3cd;
            color: #856404;
            padding: 5px;
            margin-top: 5px;
            border-radius: 4px;
            font-size: 0.9rem;
            font-style: italic;
            border: 1px solid #ffeeba;
        }

        .late-warning {
            animation: pulse-red 1.5s infinite;
            border: 4px solid #ff0000;
        }

        @keyframes pulse-red {
            0% {
                box-shadow: 0 0 0 0 rgba(255, 0, 0, 0.7);
            }

            70% {
                box-shadow: 0 0 0 15px rgba(255, 0, 0, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(255, 0, 0, 0);
            }
        }

        .medium-warning {
            border: 4px solid #ffc107;
        }
    </style>
</head>

<body>
    <div class="row h-100 g-0">
        <div class="col-6 station-col">
            <div class="station-header header-pizza"><i class="fa-solid fa-pizza-slice me-2"></i> Pizzas</div>
            <?php foreach ($listaPizza as $t)
                renderTicket($t); ?>
        </div>
        <div class="col-6 station-col">
            <div class="station-header header-cocina"><i class="fa-solid fa-burger me-2"></i> Cocina</div>
            <?php foreach ($listaCocina as $t)
                renderTicket($t); ?>
        </div>
    </div>
</body>

</html>

<?php
function renderTicket($data)
{
    $orden = $data['info'];
    $items = $data['items'];

    // CÁLCULO DE TIEMPO CORREGIDO
    $timeOrder = strtotime($orden['created_at']);
    $timeNow = time();
    $diffSeconds = $timeNow - $timeOrder;
    $mins = round($diffSeconds / 60);

    // Evitar tiempos negativos si hay desfase ligero
    if ($mins < 0)
        $mins = 0;

    // Formato Humano (ej: 65m -> 1h 5m)
    $timeLabel = $mins . "m";
    if ($mins >= 60) {
        $h = floor($mins / 60);
        $m = $mins % 60;
        $timeLabel = "{$h}h {$m}m";
    }

    $bgStatus = ($orden['status'] == 'paid') ? 'status-paid' : 'status-preparing';
    $borderClass = '';

    // Umbrales de alerta
    if ($mins > 25)
        $borderClass = 'late-warning';
    elseif ($mins > 15)
        $borderClass = 'medium-warning';

    $cliente = strtoupper(substr($orden['cliente'], 0, 15));
    $notaDir = strtoupper(substr($orden['shipping_address'] ?? '', 0, 20));
    ?>
    <div class="ticket <?= $borderClass ?>">
        <div class="ticket-head <?= $bgStatus ?>">
            <span>#<?= $orden['id'] ?>     <?= $cliente ?></span>
            <span><i class="fa-regular fa-clock"></i> <?= $timeLabel ?></span>
        </div>
        <div class="ticket-body">
            <?php if ($notaDir && $notaDir != 'TIENDA FISICA'): ?>
                <div class="mb-2 text-muted small"><i class="fa-solid fa-location-dot"></i> <?= $notaDir ?></div>
            <?php endif; ?>

            <?php foreach ($items as $item): ?>
                <div class="main-item">
                    <?= $item['qty'] ?> <span class="small">x</span> <?= strtoupper($item['name']) ?>
                </div>
                <?php foreach ($item['subs'] as $sub):
                    $tagClass = $sub['is_takeaway'] ? 'tag-takeaway' : 'tag-dinein';
                    $tagText = $sub['is_takeaway'] ? 'LLEVAR' : 'MESA';
                    ?>
                    <div class="sub-item">
                        <div>
                            <span class="tag <?= $tagClass ?>"><?= $tagText ?></span>
                            <strong>#<?= $sub['num'] ?></strong>
                            <?php if ($sub['name']): ?><span
                                    class="text-decoration-underline"><?= strtoupper($sub['name']) ?></span><?php endif; ?>
                        </div>
                        <?php if (!empty($sub['mods'])): ?>
                            <div class="mods-box">
                                <?php foreach ($sub['mods'] as $mod): ?>
                                    <div class="<?= strpos($mod, 'NO') !== false ? 'mod-bad' : 'mod-good' ?>"><?= $mod ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($sub['note'])): ?>
                            <div class="note-box">⚠️ <?= $sub['note'] ?></div><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>
<?php } ?>