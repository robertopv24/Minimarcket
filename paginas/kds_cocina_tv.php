<?php
// paginas/kds_cocina_tv.php
require_once '../templates/autoload.php';
session_start();

date_default_timezone_set('America/Caracas');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../paginas/login.php");
    exit;
}
$userManager->requireKitchenAccess($_SESSION);

$targetStation = 'kitchen';

// 1. OBTENER ÓRDENES (Paid o Preparing)
$sql = "SELECT DISTINCT o.id, o.created_at, o.status, u.name as cliente, o.shipping_address, o.kds_kitchen_ready, o.kds_pizza_ready
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.status IN ('paid', 'preparing') AND o.kds_kitchen_ready = 0
        ORDER BY o.created_at ASC";

$stmt = $db->query($sql);
$ordenesRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ordenesFiltradas = [];

foreach ($ordenesRaw as $orden) {
    $itemsRaw = $orderManager->getOrderItems($orden['id']);
    $itemsParaEstaEstacion = [];

    foreach ($itemsRaw as $item) {
        $pInfo = $productManager->getProductById($item['product_id']);

        // 1. FILTRAR PRODUCTOS SIN ESTACIÓN (REVENTA)
        if (!$pInfo || empty($pInfo['kitchen_station']))
            continue;

        $mods = $orderManager->getItemModifiers($item['id']);
        $groupedMods = [];
        foreach ($mods as $m)
            $groupedMods[$m['sub_item_index']][] = $m;

        $isCompound = ($item['product_type'] == 'compound');

        // 2. OBTENER COMPONENTES DE COMBO (SÓLO SI PERTENECEN A ESTA ESTACIÓN)
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
                        'station' => $compData['category_station'] ?? $compData['kitchen_station'] ?? ''
                    ];
                }
            }
        }

        // AÑADIMOS HEADER DEL PRODUCTO (Título principal del ticket)
        $hasItemsForThisStation = false;
        if (!$isCompound) {
            $currentStation = $pInfo['category_station'] ?? $pInfo['kitchen_station'];
            if ($currentStation == $targetStation)
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
            'is_contour' => false, // Evitar Advertencia
            'mods' => [],
            'is_takeaway' => false
        ];

        // 3. DETERMINAR BUCLE DE ÍTEMS / COMPONENTES
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

            // Si es un componente individual (Combo) o el producto base
            $compName = "";
            $compStation = $pInfo['category_station'] ?? $pInfo['kitchen_station'];

            if ($isCompound && isset($subItems[$i])) {
                $compName = $subItems[$i]['name'];
                $compStation = $subItems[$i]['station'];
            }

            // FILTRADO ESTRICTO: Solo mostramos lo que es de esta estación (evitamos reventa y bar)
            $compStation = strtolower($compStation);
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
                    $modsList[] = "-- " . strtoupper($m['ingredient_name']);
                elseif ($m['modifier_type'] == 'side')
                    $modsList[] = "** " . strtoupper($m['ingredient_name']);
                elseif ($m['modifier_type'] == 'add') {
                    $modsList[] = "++ " . strtoupper($m['ingredient_name']);
                }
            }

            $itemsParaEstaEstacion[] = [
                'order_item_id' => $item['id'],
                'sub_item_index' => $i,
                'num' => $i + 1,
                'qty' => 1,
                'name' => $compName ?: "",
                'is_combo' => $isCompound,
                'is_contour' => (!empty($compName)), // Marcamos si es contorno
                'is_main' => false,
                'mods' => $modsList,
                'is_takeaway' => $isTakeaway
            ];
        }
    }

    if (!empty($itemsParaEstaEstacion)) {
        $ordenesFiltradas[] = ['info' => $orden, 'items' => $itemsParaEstaEstacion];
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>KDS - MONITOR COCINA</title>
    <meta http-equiv="refresh" content="30">
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
            font-size: 13px;
        }

        .station-header {
            background: linear-gradient(135deg, #f59e0b, #92400e);
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
            padding: 0.35rem;
            margin-bottom: 0.35rem;
            font-size: 1rem;
            letter-spacing: 1px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }

        .grid-kds {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 0.4rem;
            padding: 0.4rem;
        }

        .ticket {
            background: var(--card-white);
            color: var(--text-dark);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid transparent;
        }

        .ticket-head {
            padding: 0.25rem 0.5rem;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 700;
            font-size: 0.9rem;
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
            background-color: #f59e0b;
        }

        .ticket-body {
            padding: 0.35rem;
        }

        .minimal-row {
            border-bottom: 1px solid #f1f5f9;
            padding: 1px 0;
        }

        .minimal-row:last-child {
            border-bottom: none;
        }

        .main-item-line {
            font-size: 0.85rem;
            font-weight: 800;
            color: #ef4444;
            margin-bottom: 0.2rem;
            display: block;
        }

        .sub-item-line {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .item-index {
            background: #334155;
            color: white;
            padding: 0 3px;
            border-radius: 2px;
            font-size: 0.6rem;
        }

        .tag-mini {
            font-size: 0.55rem;
            font-weight: 800;
            padding: 0 2px;
            border: 1px solid currentColor;
            border-radius: 2px;
        }

        .mod-line {
            font-size: 0.65rem;
            font-weight: 700;
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
    <div class="station-header"><i class="fa-solid fa-burger me-2"></i> MONITOR COCINA</div>
    <div class="grid-kds">
        <?php foreach ($ordenesFiltradas as $t)
            renderTicket($t); ?>
    </div>

    <script>
        const STATION = '<?= $targetStation ?>';
        const lastOrderId = <?= count($ordenesFiltradas) > 0 ? max(array_column(array_column($ordenesFiltradas, 'info'), 'id')) : 0 ?>;

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
        const storedLastId = localStorage.getItem('kds_last_order_id_' + STATION);
        if (storedLastId && lastOrderId > parseInt(storedLastId)) {
            playPing();
        }
        localStorage.setItem('kds_last_order_id_' + STATION, lastOrderId);

        // Click handler for marking order as ready
        document.querySelectorAll('.ticket-head').forEach(head => {
            head.addEventListener('click', function () {
                const orderId = this.dataset.orderId;
                // Eliminada confirmación para mayor rapidez

                fetch('../ajax/update_kds_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        order_id: orderId,
                        station: 'kitchen',
                        status: 'ready'
                    })
                }).then(res => res.json()).then(data => {
                    if (data.success) {
                        this.closest('.ticket').style.opacity = '0.3';
                        this.closest('.ticket').style.transform = 'scale(0.95)';
                        this.closest('.ticket').style.pointerEvents = 'none';
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
        <div class="ticket-head <?= $bgStatus ?> d-flex justify-content-between align-items-center"
            data-order-id="<?= $orden['id'] ?>" title="Clic para marcar como LISTO">
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
            <?php foreach ($items as $it): ?>
                <div class="minimal-row">
                    <?php if ($it['is_main']): ?>
                        <span class="main-item-line"><?= $it['qty'] ?> x <?= strtoupper($it['name']) ?></span>
                    <?php endif; ?>

                    <?php if (!$it['is_main'] && ($it['num'] > 0 || !empty($it['name']))): ?>
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
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php } ?>