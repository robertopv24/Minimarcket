<?php
// paginas/kds_pizza_tv.php
require_once '../templates/autoload.php';
session_start();

date_default_timezone_set('America/Caracas');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../paginas/login.php");
    exit;
}
$userManager->requireKitchenAccess($_SESSION);

$targetStation = 'pizza';

// 1. OBTENER ÓRDENES (Paid o Preparing)
$sql = "SELECT DISTINCT o.id, o.created_at, o.status, u.name as cliente, o.shipping_address
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.status IN ('paid', 'preparing')
        ORDER BY o.created_at ASC";

$stmt = $db->query($sql);
$ordenesRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ordenesFiltradas = [];

foreach ($ordenesRaw as $orden) {
    $itemsRaw = $orderManager->getOrderItems($orden['id']);
    $itemsParaEstaEstacion = [];

    foreach ($itemsRaw as $item) {
        $pInfo = $productManager->getProductById($item['product_id']);
        if (!$pInfo || empty($pInfo['kitchen_station']))
            continue;

        $mods = $orderManager->getItemModifiers($item['id']);
        $groupedMods = [];
        foreach ($mods as $m)
            $groupedMods[$m['sub_item_index']][] = $m;

        $isCompound = ($item['product_type'] == 'compound');

        $subItems = [];
        if ($isCompound) {
            $comps = $productManager->getProductComponents($item['product_id']);
            foreach ($comps as $c) {
                $compData = null;
                if ($c['component_type'] == 'product') {
                    $compData = $productManager->getProductById($c['component_id']);
                } elseif ($c['component_type'] == 'manufactured') {
                    $stmtM = $db->prepare("SELECT kitchen_station, name FROM manufactured_products WHERE id = ?");
                    $stmtM->execute([$c['component_id']]);
                    $compData = $stmtM->fetch(PDO::FETCH_ASSOC);
                }

                for ($k = 0; $k < $c['quantity']; $k++) {
                    $subItems[] = [
                        'name' => $compData['name'] ?? 'ITEM',
                        'station' => $compData['kitchen_station'] ?? ''
                    ];
                }
            }
        }

        // AÑADIMOS HEADER DEL PRODUCTO (Título principal del ticket)
        $hasItemsForThisStation = false;
        if (!$isCompound) {
            if ($pInfo['kitchen_station'] == $targetStation)
                $hasItemsForThisStation = true;
        } else {
            foreach ($subItems as $si) {
                if ($si['station'] == $targetStation) {
                    $hasItemsForThisStation = true;
                    break;
                }
            }
        }

        if (!$hasItemsForThisStation)
            continue;

        // Si llegamos aquí, el producto (o combo) tiene algo para nosotros.
        // 1. Agregamos el "Header" (Título principal)
        $itemsParaEstaEstacion[] = [
            'num' => 0,
            'qty' => $item['quantity'],
            'name' => $item['name'],
            'is_combo' => $isCompound,
            'is_main' => true,
            'mods' => [],
            'is_takeaway' => false
        ];

        // 3. DETERMINAR BUCLE DE ÍTEMS / COMPONENTES
        $maxIdx = $isCompound ? count($subItems) : $item['quantity'];
        foreach (array_keys($groupedMods) as $idx)
            if ($idx >= $maxIdx)
                $maxIdx = $idx + 1;

        for ($i = 0; $i <= $maxIdx; $i++) {
            $currentMods = $groupedMods[$i] ?? [];

            // Si es un componente individual
            $compName = "";
            $compStation = $pInfo['kitchen_station'];
            if ($isCompound && isset($subItems[$i])) {
                $compName = $subItems[$i]['name'];
                $compStation = $subItems[$i]['station'];
            }

            // FILTRADO ESTRICTO: Solo mostramos lo que es de esta estación (evitamos reventa)
            if (empty($compStation) || $compStation != $targetStation)
                continue;

            if (empty($currentMods) && $isCompound && !isset($subItems[$i]))
                continue;
            if (empty($currentMods) && !$isCompound && $i > 0)
                continue;

            $isTakeaway = false;
            foreach ($currentMods as $m)
                if ($m['modifier_type'] == 'info' && $m['is_takeaway'] == 1)
                    $isTakeaway = true;

            $modsList = [];
            foreach ($currentMods as $m) {
                if ($m['modifier_type'] == 'remove')
                    $modsList[] = "-- SIN " . $m['ingredient_name'];
                elseif ($m['modifier_type'] == 'side')
                    $modsList[] = "🔘 " . strtoupper($m['ingredient_name']);
                elseif ($m['modifier_type'] == 'add') {
                    $isPaid = (floatval($m['price_adjustment_usd'] ?? 0) > 0);
                    $prefix = $isPaid ? "++ EXTRA " : "🔘 ";
                    $modsList[] = $prefix . $m['ingredient_name'];
                }
            }

            $itemsParaEstaEstacion[] = [
                'num' => $i + 1,
                'qty' => 1,
                'name' => $compName ?: ($i == 0 ? $item['name'] : ""),
                'is_combo' => $isCompound,
                'is_main' => false,
                'mods' => $modsList,
                'is_takeaway' => $isTakeaway
            ];
        }
    }

    if (!empty($itemsParaEstaEstacion))
        $ordenesFiltradas[] = ['info' => $orden, 'items' => $itemsParaEstaEstacion];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>KDS - MONITOR PIZZAS</title>
    <meta http-equiv="refresh" content="15">
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0f172a;
            --card-white: #ffffff;
            --accent-red: #ef4444;
            --accent-blue: #3b82f6;
            --text-dark: #1e293b;
        }

        body {
            background-color: var(--bg-dark);
            color: white;
            font-family: 'Outfit', sans-serif;
            margin: 0;
            height: 100vh;
        }

        .station-header {
            background: linear-gradient(135deg, #ef4444, #991b1b);
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            font-size: 2rem;
            letter-spacing: 2px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }

        .grid-kds {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem;
        }

        .ticket {
            background: var(--card-white);
            color: var(--text-dark);
            border-radius: 16px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            border: 4px solid transparent;
        }

        .ticket-head {
            padding: 0.75rem 1rem;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            font-size: 1.3rem;
        }

        .status-paid {
            background-color: var(--accent-blue);
        }

        .status-preparing {
            background-color: #f59e0b;
        }

        .ticket-body {
            padding: 1.25rem;
        }

        .main-item {
            font-size: 1.5rem;
            font-weight: 800;
            border-bottom: 2px solid #f1f5f9;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
        }

        .sub-item-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid #e2e8f0;
        }

        .item-num {
            font-size: 1.3rem;
            font-weight: 800;
            color: #1e293b;
            margin: 0.25rem 0;
        }

        .item-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: #111;
        }

        .tag {
            padding: 2px 10px;
            border-radius: 6px;
            font-weight: 800;
            font-size: 0.85rem;
            display: inline-block;
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

        .mod-bad {
            color: #dc2626;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .mod-good {
            color: #16a34a;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .late-warning {
            animation: pulse-red 2s infinite;
            border-color: #ef4444;
        }

        .medium-warning {
            border-color: #f59e0b;
        }

        @keyframes pulse-red {
            0% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
            }

            70% {
                box-shadow: 0 0 0 15px rgba(239, 68, 68, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }
    </style>
</head>

<body>
    <div class="station-header"><i class="fa-solid fa-pizza-slice me-2"></i> MONITOR PIZZAS</div>
    <div class="grid-kds">
        <?php foreach ($ordenesFiltradas as $t)
            renderTicket($t); ?>
    </div>
</body>

</html>

<?php
function renderTicket($orderData)
{
    $orden = $orderData['info'];
    $items = $orderData['items'];
    $mins = round((time() - strtotime($orden['created_at'])) / 60);
    if ($mins < 0)
        $mins = 0;

    $bgStatus = ($orden['status'] == 'paid') ? 'status-paid' : 'status-preparing';
    $borderClass = ($mins > 25) ? 'late-warning' : (($mins > 15) ? 'medium-warning' : '');
    ?>
    <div class="ticket <?= $borderClass ?>">
        <div class="ticket-head <?= $bgStatus ?>">
            <span>#<?= $orden['id'] ?> <small class="ms-2 opacity-75"><?= strtoupper($orden['cliente']) ?></small></span>
            <span><i class="fa-regular fa-clock me-1"></i> <?= $mins ?>m</span>
        </div>
        <div class="ticket-body">
            <?php foreach ($items as $it): ?>
                <?php if ($it['is_main']): ?>
                    <div class="main-item"><?= $it['qty'] ?> x <?= strtoupper($it['name']) ?></div>
                <?php endif; ?>

                <?php if ($it['num'] > 0): ?>
                    <div class="sub-item-card text-center">
                        <span class="tag <?= $it['is_takeaway'] ? 'tag-takeaway' : 'tag-dinein' ?>">
                            <?= $it['is_takeaway'] ? 'LLEVAR' : 'MESA' ?>
                        </span>
                        <div class="item-num">#<?= $it['num'] ?></div>
                        <div class="item-name">(<?= strtoupper($it['name']) ?>)</div>

                        <?php if (!empty($it['mods'])): ?>
                            <div class="mt-2 text-start px-2">
                                <?php foreach ($it['mods'] as $m): ?>
                                    <div class="<?= (strpos($m, 'SIN') !== false) ? 'mod-bad' : 'mod-good' ?>"><?= strtoupper($m) ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
<?php } ?>