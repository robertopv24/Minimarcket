<?php
// paginas/kds_tv.php
require_once '../templates/autoload.php';
session_start();

date_default_timezone_set('America/Caracas');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../paginas/login.php");
    exit;
}
$userManager->requireKitchenAccess($_SESSION);

// 1. OBTENER ÓRDENES (Paid o Preparing)
$sql = "SELECT o.id, o.created_at, o.status, u.name as cliente, o.shipping_address, 
               o.kds_kitchen_ready, o.kds_pizza_ready
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.status IN ('paid', 'preparing')
        ORDER BY o.created_at ASC";

$stmt = $db->query($sql);
$ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. PROCESAMIENTO UNIFICADO
$listaPizza = [];
$listaCocina = [];

foreach ($ordenes as $orden) {
    $items = $orderManager->getOrderItems($orden['id']);
    $itemsParaPizza = [];
    $itemsParaCocina = [];

    foreach ($items as $item) {
        $pInfo = $productManager->getProductById($item['product_id']);

        // 1. FILTRAR PRODUCTOS SIN ESTACIÓN (REVENTA)
        if (!$pInfo || empty($pInfo['kitchen_station']))
            continue;

        $mods = $orderManager->getItemModifiers($item['id']);
        $groupedMods = [];
        foreach ($mods as $m)
            $groupedMods[$m['sub_item_index']][] = $m;

        $isCompound = ($item['product_type'] == 'compound');

        // 2. OBTENER COMPONENTES DE COMBO
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

                // Guardamos TODO para el contexto del combo
                for ($k = 0; $k < $c['quantity']; $k++) {
                    $subItems[] = [
                        'name' => $compData['name'] ?? 'ITEM',
                        'station' => $compData['category_station'] ?? $compData['kitchen_station'] ?? '',
                        'category_name' => $compData['category_name'] ?? ''
                    ];
                }
            }
        }

        $maxIdx = $isCompound ? count($subItems) : $item['quantity'];
        foreach (array_keys($groupedMods) as $idx)
            if ($idx >= $maxIdx)
                $maxIdx = $idx + 1;

        for ($i = 0; $i <= $maxIdx; $i++) {
            // NUCLEAR RESET: Asegurar que nada gotee de la iteración anterior
            $currentMods = [];
            $modsList = [];
            $isTakeaway = false;
            $compName = "";
            $note = "";

            $currentMods = $groupedMods[$i] ?? [];
            if (empty($currentMods) && $isCompound && !isset($subItems[$i]))
                continue;
            if (empty($currentMods) && !$isCompound && $i > 0)
                continue;
            if (!$isCompound && $i == 0 && $item['quantity'] == 0)
                continue;

            $isTakeaway = false;
            foreach ($currentMods as $m)
                if ($m['modifier_type'] == 'info' && $m['is_takeaway'] == 1)
                    $isTakeaway = true;

            $modsList = [];
            foreach ($currentMods as $m) {
                if ($m['modifier_type'] == 'remove')
                    $modsList[] = "-- " . strtoupper($m['ingredient_name']);
                elseif ($m['modifier_type'] == 'side')
                    $modsList[] = "** " . strtoupper($m['ingredient_name']);
                elseif ($m['modifier_type'] == 'add') {
                    $modsList[] = "++ " . strtoupper($m['ingredient_name']);
                }
            }

            $note = ($i == 0 || $i == -1) ? ($groupedMods[-1][0]['note'] ?? "") : "";

            $compName = "";
            $compStation = $pInfo['kitchen_station'];

            if ($isCompound && isset($subItems[$i])) {
                $compName = $subItems[$i]['name'];
                $compStation = $subItems[$i]['station'];
            } else {
                // Para productos simples, priorizamos la estación de la categoría
                $compStation = $pInfo['category_station'] ?? $pInfo['kitchen_station'];
            }

            // Filtrado ESTRICTO de estacíón (evita que reventa o BAR aparezcan en monitores cocina)
            $compStation = strtolower($compStation);
            if (empty($compStation) || !in_array($compStation, ['pizza', 'kitchen']))
                continue;

            $processedSub = [
                'order_item_id' => $item['id'],
                'sub_item_index' => $i,
                'num' => $i + 1,
                'name' => $compName ?: "",
                'station' => $compStation,
                'is_contour' => (!empty($compName)), // Marcamos si es contorno
                'is_takeaway' => $isTakeaway,
                'mods' => $modsList,
                'note' => $note
            ];

            if ($i == 0 && $isCompound && empty($compName) && empty($modsList))
                continue;

            // En kds_tv.php: enviamos a la columna correspondiente SOLO SI NO ESTÁ LISTA
            if ($processedSub['station'] == 'pizza' && !$orden['kds_pizza_ready'])
                $itemsParaPizza[] = $processedSub + ['main_name' => $item['name']];
            elseif ($processedSub['station'] == 'kitchen' && !$orden['kds_kitchen_ready']) {
                $itemsParaCocina[] = $processedSub + ['main_name' => $item['name']];
            }
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
    <title>KDS - MONITOR TV</title>
    <meta http-equiv="refresh" content="30">
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0f172a;
            --card-white: #ffffff;
            --accent-red: #ef4444;
            --accent-orange: #f59e0b;
            --accent-blue: #3b82f6;
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }

        body {
            background-color: var(--bg-dark);
            color: white;
            font-family: 'Outfit', sans-serif;
            margin: 0;
            height: 100vh;
            overflow: hidden;
            font-size: 12px;
        }

        .station-col {
            height: 100vh;
            overflow-y: auto;
            padding: 0.5rem;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }

        .station-header {
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
            padding: 0.25rem;
            margin-bottom: 0.25rem;
            border-radius: 4px;
            font-size: 0.85rem;
            letter-spacing: 1px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }

        .header-pizza {
            background: linear-gradient(135deg, #ef4444, #991b1b);
        }

        .header-cocina {
            background: linear-gradient(135deg, #f59e0b, #92400e);
        }

        .ticket {
            background: var(--card-white);
            color: var(--text-dark);
            border-radius: 6px;
            margin-bottom: 0.4rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid transparent;
            transition: all 0.3s ease;
        }

        .ticket-head {
            padding: 0.25rem 0.5rem;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .ticket-head:hover {
            opacity: 0.8;
        }

        .status-paid {
            background-color: var(--accent-blue);
        }

        .status-preparing {
            background-color: var(--accent-orange);
        }

        .ticket-body {
            padding: 0.25rem;
        }

        .minimal-row {
            border-bottom: 1px solid #f1f5f9;
            padding: 1px 0;
        }

        .minimal-row:last-child {
            border-bottom: none;
        }

        .main-item-line {
            font-size: 0.8rem;
            font-weight: 800;
            color: #ef4444;
            margin-bottom: 0.15rem;
            display: block;
        }

        .sub-item-line {
            display: flex;
            align-items: center;
            gap: 3px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .item-index {
            background: #334155;
            color: white;
            padding: 0 2px;
            border-radius: 2px;
            font-size: 0.55rem;
        }

        .tag-mini {
            font-size: 0.5rem;
            font-weight: 800;
            padding: 0 2px;
            border: 1px solid currentColor;
            border-radius: 2px;
        }

        .mod-line {
            font-size: 0.6rem;
            font-weight: 700;
            margin-left: 8px;
            line-height: 1;
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
            font-size: 0.7rem;
        }

        .mod-good {
            color: #16a34a;
            font-weight: 700;
            font-size: 0.7rem;
        }

        .note-box {
            background: #fffbeb;
            color: #92400e;
            padding: 0.25rem;
            margin-top: 0.25rem;
            border-radius: 4px;
            font-size: 0.65rem;
            font-weight: 600;
            border: 1px solid #fde68a;
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
    <div class="row h-100 g-0">
        <div class="col-6 station-col">
            <div class="station-header header-pizza"><i class="fa-solid fa-pizza-slice me-2"></i> MONITOR PIZZAS</div>
            <?php foreach ($listaPizza as $t)
                renderTicket($t); ?>
        </div>
        <div class="col-6 station-col">
            <div class="station-header header-cocina"><i class="fa-solid fa-burger me-2"></i> MONITOR COCINA</div>
            <?php foreach ($listaCocina as $t)
                renderTicket($t); ?>
        </div>
    </div>

    <script>
        const lastOrderId = <?= count($ordenes) > 0 ? max(array_column($ordenes, 'id')) : 0 ?>;

        // Sound logic
        function playPing() {
            const context = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = context.createOscillator();
            const gainNode = context.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(880, context.currentTime); // A5
            oscillator.connect(gainNode);
            gainNode.connect(context.destination);

            gainNode.gain.setValueAtTime(0, context.currentTime);
            gainNode.gain.linearRampToValueAtTime(0.5, context.currentTime + 0.05);
            gainNode.gain.exponentialRampToValueAtTime(0.01, context.currentTime + 0.5);

            oscillator.start();
            oscillator.stop(context.currentTime + 0.5);
        }

        // Check for new orders
        const storedLastId = localStorage.getItem('kds_last_order_id_TV');
        if (storedLastId && lastOrderId > parseInt(storedLastId)) {
            playPing();
        }
        localStorage.setItem('kds_last_order_id_TV', lastOrderId);

        // Click handler for marking order as ready
        document.querySelectorAll('.ticket-head').forEach(head => {
            head.addEventListener('click', function () {
                const orderId = this.dataset.orderId;
                const station = this.dataset.station; // 'kitchen' o 'pizza'

                fetch('../ajax/update_kds_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        order_id: orderId,
                        station: station,
                        status: 'ready'
                    })
                }).then(res => res.json()).then(data => {
                    if (data.success) {
                        // In TV view, we might have the same ticket twice. Find all of them.
                        document.querySelectorAll(`.ticket-head[data-order-id="${orderId}"][data-station="${station}"]`).forEach(h => {
                            h.closest('.ticket').style.opacity = '0.3';
                            h.closest('.ticket').style.transform = 'scale(0.95)';
                            h.closest('.ticket').style.pointerEvents = 'none';
                        });
                        setTimeout(() => location.reload(), 300);
                    }
                });
            });
    });

        // Auto refresh
        setTimeout(() => location.reload(), 30000);
    </script>
</body>

</html>

<?php
function renderTicket($data)
{
    $orden = $data['info'];
    $items = $data['items'];
    $mins = round((time() - strtotime($orden['created_at'])) / 60);
    if ($mins < 0)
        $mins = 0;

    $bgStatus = ($orden['status'] == 'paid') ? 'status-paid' : 'status-preparing';
    $borderClass = ($mins > 25) ? 'late-warning' : (($mins > 15) ? 'medium-warning' : '');
    ?>
    <div class="ticket <?= $borderClass ?>">
        <div class="ticket-head <?= $bgStatus ?> d-flex justify-content-between align-items-center"
            data-order-id="<?= $orden['id'] ?>"
            data-station="<?= (isset($items[0]) && $items[0]['station'] == 'pizza') ? 'pizza' : 'kitchen' ?>"
            title="Clic para marcar como LISTO">
            <div>
                <span class="fw-bold">#<?= $orden['id'] ?></span>
                <small class="ms-1 opacity-75 d-none d-sm-inline"><?= substr(strtoupper($orden['cliente']), 0, 8) ?></small>
                <?php if ($orden['kds_kitchen_ready']): ?><i class="fa fa-fire ms-2 text-success"
                        title="Cocina Lista"></i><?php endif; ?>
                <?php if ($orden['kds_pizza_ready']): ?><i class="fa fa-pizza-slice ms-1 text-danger"
                        title="Pizza Lista"></i><?php endif; ?>
            </div>
            <span><?= $mins ?>m</span>
        </div>
        <div class="ticket-body">
            <?php
            $lastMain = "";
            foreach ($items as $it):
                if ($it['main_name'] !== $lastMain):
                    $lastMain = $it['main_name']; ?>
                    <span class="main-item-line"><?= strtoupper($it['main_name']) ?></span>
                <?php endif; ?>

                <div class="minimal-row">
                    <?php if (isset($it['is_main']) && $it['is_main']): ?>
                        <!-- No render here, kds_tv has a fixed title above -->
                    <?php endif; ?>

                    <?php if (!(isset($it['is_main']) && $it['is_main']) && ($it['num'] > 0 || !empty($it['name']))): ?>
                        <div class="sub-item-line">
                            <span class="item-index">#<?= $it['num'] ?></span>
                            <span
                                class="tag-mini fw-bold px-1 <?= $it['is_takeaway'] ? 'bg-danger text-white border-danger' : 'bg-primary text-white border-primary' ?>"
                                style="font-size: 0.6rem;">
                                <?= $it['is_takeaway'] ? 'LLEVAR' : 'LOCAL' ?>
                            </span>
                            <?= ($it['is_contour'] && !empty($it['name'])) ? "(" . strtoupper($it['name']) . ")" : (!empty($it['name']) ? strtoupper($it['name']) : "") ?>
                        </div>

                        <?php if (!empty($it['mods'])): ?>
                            <?php foreach ($it['mods'] as $m): ?>
                                <div class="mod-line <?= (strpos($m, '--') === 0) ? 'text-danger' : 'text-success' ?>">
                                    <?= $m ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($it['note']): ?>
                        <div class="note-box"><i class="fa-solid fa-comment-dots me-1"></i>
                            <?= strtoupper($it['note']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php } ?>