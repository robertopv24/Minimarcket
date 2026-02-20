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

$refreshSeconds = $config->get('kds_refresh_interval', 30);
$colorLlevar = $config->get('kds_color_llevar', '#ef4444');
$colorLocal = $config->get('kds_color_local', '#3b82f6');
$warningMedium = $config->get('kds_warning_time_medium', 15);
$warningLate = $config->get('kds_warning_time_late', 25);
$soundEnabled = $config->get('kds_sound_enabled', '1');
$useShortCodes = ($config->get('kds_use_short_codes', '0') == '1');

// Nuevos colores
$colorCardBg = $config->get('kds_color_card_bg', '#ffffff');
$colorMixedBg = $config->get('kds_color_mixed_bg', '#fff3cd');
$colorModAdd = $config->get('kds_color_mod_add', '#198754');
$colorModRemove = $config->get('kds_color_mod_remove', '#dc3545');
$colorModSide = $config->get('kds_color_mod_side', '#0dcaf0');
$colorProductName = $config->get('kds_product_name_color', '#ffffff');
$soundUrl = $config->get('kds_sound_url_kitchen', '../assets/sounds/ping.mp3');
?>
<?php
// 3. OBTENER ÓRDENES (Paid o Preparing)
$sql = "SELECT DISTINCT o.id, o.created_at, o.status, u.name as cliente, o.shipping_address, o.kds_kitchen_ready,
o.kds_pizza_ready
FROM orders o
JOIN users u ON o.user_id = u.id
WHERE o.status IN ('paid', 'preparing', 'ready') AND o.kds_kitchen_ready = 0
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
            'name' => ($useShortCodes && !empty($item['short_code'])) ? $item['short_code'] : $item['name'],
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

            // Ordenar modificadores: 1. Companion (0), 1. Side (**), 2. Add (++), 3. Remove (--) 
            usort($currentMods, function ($a, $b) {
                $order = ['side' => 1, 'add' => 2, 'remove' => 3];
                $va = $order[$a['modifier_type']] ?? 99;
                $vb = $order[$b['modifier_type']] ?? 99;
                return $va <=> $vb;
            });

            // Si es un componente individual (Combo) o el producto base
            $compName = "";
            $compStation = $pInfo['category_station'] ?? $pInfo['kitchen_station'];

            if ($isCompound && isset($subItems[$i])) {
                $compName = ($useShortCodes && !empty($subItems[$i]['short_code'])) ? $subItems[$i]['short_code'] :
                    $subItems[$i]['name'];
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
            $companionItems = []; // NUEVO: Para guardar acompañantes detectados

            foreach ($currentMods as $m) {
                $mName = ($useShortCodes && !empty($m['short_code'])) ? $m['short_code'] : $m['ingredient_name'];
                $type = strtolower($m['modifier_type'] ?? '');

                if ($type == 'remove') {
                    $modsList[] = '<span style="color: var(--mod-remove);">-- ' . strtoupper($mName ?? '') . '</span>';
                } elseif ($type == 'add') {
                    $modsList[] = '<span style="color: var(--mod-add);">++ ' . strtoupper($mName ?? '') . '</span>';
                } elseif ($type != 'info') {
                    // Fallback para 'side'
                    $modsList[] = '<span style="color: var(--mod-side);">** ' . strtoupper($mName ?? '') . '</span>';
                }
            }

            // Primero el item base
            if (!empty($compName) || $i == 0 || !empty($modsList)) {
                $itemsParaEstaEstacion[] = [
                    'order_item_id' => $item['id'],
                    'sub_item_index' => $i,
                    'num' => $i + 1,
                    'qty' => 1,
                    'name' => $compName ?: "",
                    'is_combo' => $isCompound,
                    'is_contour' => (!empty($compName)),
                    'is_main' => false,
                    'mods' => $modsList,
                    'is_takeaway' => $isTakeaway
                ];
            }
        }
    }

    // DETECTAR SI ES MIXTO (Si tiene items en ambas estaciones: kitchen y pizza)
    $hasKitchen = false;
    $hasPizza = false;
    $allOrderItems = $orderManager->getOrderItems($orden['id']);
    foreach ($allOrderItems as $ai) {
        $aiInfo = $productManager->getProductById($ai['product_id']);
        if (!$aiInfo)
            continue;

        $stations = [];
        if ($ai['product_type'] == 'compound') {
            $comps = $productManager->getProductComponents($ai['product_id']);
            foreach ($comps as $c) {
                if ($c['component_type'] == 'product') {
                    $p = $productManager->getProductById($c['component_id']);
                    $stations[] = $p['category_station'] ?? $p['kitchen_station'] ?? '';
                } elseif ($c['component_type'] == 'manufactured') {
                    $stmtM = $db->prepare("SELECT kitchen_station FROM manufactured_products WHERE id = ?");
                    $stmtM->execute([$c['component_id']]);
                    $mR = $stmtM->fetch(PDO::FETCH_ASSOC);
                    $stations[] = $mR['kitchen_station'] ?? '';
                }
            }
        } else {
            $stations[] = $aiInfo['category_station'] ?? $aiInfo['kitchen_station'] ?? '';
        }

        foreach ($stations as $st) {
            if ($st == 'kitchen')
                $hasKitchen = true;
            if ($st == 'pizza')
                $hasPizza = true;
        }
    }
    $isMixed = ($hasKitchen && $hasPizza);

    if (!empty($itemsParaEstaEstacion)) {
        $ordenesFiltradas[] = [
            'info' => $orden,
            'items' => $itemsParaEstaEstacion,
            'is_mixed' => $isMixed
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>KDS - MONITOR COCINA</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0f172a;
            --card-white:
                <?= $colorCardBg ?>
            ;
            --mixed-bg:
                <?= $colorMixedBg ?>
            ;
            --accent-red: #ef4444;
            --accent-blue: #3b82f6;
            --text-dark: #1e293b;
            --mod-add:
                <?= $colorModAdd ?>
            ;
            --mod-remove:
                <?= $colorModRemove ?>
            ;
            --mod-side:
                <?= $colorModSide ?>
            ;
            --product-text:
                <?= $colorProductName ?>
            ;
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

        .status-preparing {
            background-color: #f59e0b;
        }

        .ticket-mixed {
            background-color: var(--mixed-bg) !important;
        }

        /* Nuevos estados por tiempo */
        .status-medium {
            background-color: #3b82f6 !important;
            /* Azul */
        }

        .status-late {
            background-color: #ef4444 !important;
            /* Rojo */
        }

        .ticket-mixed {
            background-color: var(--mixed-bg) !important;
        }

        .ticket-mixed .ticket-body {
            background-color: var(--mixed-bg) !important;
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
            color: var(--product-text) !important;
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
    <div class="station-header d-flex justify-content-between align-items-center">
        <span><i class="fa-solid fa-burger me-2"></i> MONITOR COCINA</span>
        <button class="btn btn-sm btn-light text-danger fw-bold" onclick="playPing(true)" style="font-size: 0.7rem;">
            <i class="fa fa-volume-high"></i>
        </button>
    </div>

    <?php
    $maxId = 0;
    if (!empty($ordenesFiltradas)) {
        $ids = array_column(array_column($ordenesFiltradas, 'info'), 'id');
        $maxId = max($ids);
    }
    ?>

    <div class="grid-kds" id="kds-grid" data-last-order-id="<?= $maxId ?>">
        <?php foreach ($ordenesFiltradas as $t)
            renderTicket($t); ?>
    </div>

    <!-- Init Sound Overlay -->
    <div id="sound-init-overlay"
        style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); color: white; display: flex; align-items: center; justify-content: center; z-index: 9999; cursor: pointer;">
        <div class="text-center">
            <i class="fa fa-volume-high fa-4x mb-3 text-warning"></i>
            <h3>Activar Sonido</h3>
            <p>Haz clic en cualquier lugar para iniciar</p>
        </div>
    </div>

    <script>
        const STATION = '<?= $targetStation ?>';
        const lastOrderId = <?= count($ordenesFiltradas) > 0 ? max(array_column(array_column($ordenesFiltradas, 'info'), 'id')) : 0 ?>;

        // Sound logic robusta (File -> Oscillator Fallback)
        const soundUrl = '<?= $soundUrl ?>';
        let audioContext = null;

        function checkAudioState() {
            if (!audioContext) audioContext = new (window.AudioContext || window.webkitAudioContext)();

            audioContext.resume().then(() => {
                if (audioContext.state === 'running') {
                    document.getElementById('sound-init-overlay').style.display = 'none';
                } else {
                    document.getElementById('sound-init-overlay').style.display = 'flex';
                }
            }).catch(() => {
                document.getElementById('sound-init-overlay').style.display = 'flex';
            });
        }

        // Run check
        checkAudioState();

        document.getElementById('sound-init-overlay').addEventListener('click', () => {
            if (!audioContext) audioContext = new (window.AudioContext || window.webkitAudioContext)();
            audioContext.resume().then(() => {
                document.getElementById('sound-init-overlay').style.display = 'none';
                playPing(true);
            });
        });

        function playPing(isTest = false) {
            // 1. Try File
            const audio = new Audio(soundUrl);
            const playPromise = audio.play();

            if (playPromise !== undefined) {
                playPromise.catch(error => {
                    console.warn("File playback failed, switching to Oscillator:", error);
                    playOscillatorBackup();
                });
            }
        }

        function playOscillatorBackup() {
            if (!audioContext) audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(880, audioContext.currentTime); // A5 (Agudo para Ping)
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            gainNode.gain.setValueAtTime(0, audioContext.currentTime);
            gainNode.gain.linearRampToValueAtTime(0.5, audioContext.currentTime + 0.05);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

            oscillator.start();
            oscillator.stop(audioContext.currentTime + 0.5);
        }

        // Initial Max ID
        let currentMaxId = parseInt(document.getElementById('kds-grid').dataset.lastOrderId || 0);

        // AJAX Refresh Logic
        function refreshKDS() {
            fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newGrid = doc.getElementById('kds-grid');

                    if (newGrid) {
                        const currentGrid = document.getElementById('kds-grid');
                        currentGrid.innerHTML = newGrid.innerHTML;

                        // Update Data Attribute
                        const newMaxId = parseInt(newGrid.dataset.lastOrderId || 0);
                        currentGrid.dataset.lastOrderId = newMaxId;

                        // Check for sound
                        if (newMaxId > currentMaxId) {
                            playPing();
                        }
                        currentMaxId = newMaxId;

                        // Re-attach listeners because innerHTML replaced elements
                        attachClickListeners();
                    }
                })
                .catch(err => console.error("Refresh failed", err));
        }

        setInterval(refreshKDS, <?= $refreshSeconds * 1000 ?>);

        // Click handler extraction to reusable function
        function attachClickListeners() {
            document.querySelectorAll('.ticket-head').forEach(head => {
                head.addEventListener('click', function () {
                    const orderId = this.dataset.orderId;

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
                            refreshKDS(); // Immediate refresh
                        }
                    });
                });
            });
        }

        // Initial attach
        attachClickListeners();
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

    global $warningMedium, $warningLate;
    $borderClass = ($mins >= $warningLate) ? 'late-warning' : (($mins >= $warningMedium) ? 'medium-warning' : '');

    // Prioridad de color por tiempo
    if ($mins >= $warningLate) {
        $bgStatus = 'status-late';
    } elseif ($mins >= $warningMedium) {
        $bgStatus = 'status-medium';
    }

    $mixedClass = ($data['is_mixed'] ?? false) ? 'ticket-mixed' : '';
    ?>
    <div class="ticket <?= $borderClass ?> <?= $mixedClass ?>">
        <div class="ticket-head <?= $bgStatus ?> d-flex justify-content-between align-items-center"
            data-order-id="<?= $orden['id'] ?>" data-station="kitchen" title="Clic para marcar como LISTO">
            <div>
                <span class="fw-bold">#
                    <?= $orden['id'] ?>
                </span>
                <small class="ms-1 opacity-75 d-none d-sm-inline">
                    <?php
                    $displayClient = (!empty($orden['shipping_address']) && $orden['shipping_address'] !== 'Tienda Física') 
                        ? $orden['shipping_address'] 
                        : $orden['cliente'];
                    echo substr(strtoupper($displayClient), 0, 20);
                    ?>
                </small>
                <?php if ($orden['kds_kitchen_ready']): ?><i class="fa fa-fire ms-2 text-success" title="Cocina Lista"></i>
                <?php endif; ?>
                <?php if ($orden['kds_pizza_ready']): ?><i class="fa fa-pizza-slice ms-1 text-danger"
                        title="Pizza Lista"></i>
                <?php endif; ?>
            </div>
            <span>
                <?= $mins ?>m
            </span>
        </div>
        <div class="ticket-body">
            <?php foreach ($items as $it): ?>
                <div class="minimal-row">
                    <?php if (isset($it['is_main']) && $it['is_main']): ?>
                        <span class="main-item-line">
                            <?= $it['qty'] ?> x
                            <?= strtoupper($it['name']) ?>
                        </span>
                    <?php endif; ?>

                    <?php if (!(isset($it['is_main']) && $it['is_main']) && ($it['num'] > 0 || !empty($it['name']))): ?>
                        <div class="sub-item-line">
                            <span class="item-index">#
                                <?= $it['num'] ?>
                            </span>
                            <?php
                            global $colorLlevar, $colorLocal;
                            if ($it['is_takeaway']): ?>
                                <span class="tag-mini fw-bold px-1 text-white"
                                    style="font-size: 0.6rem; background-color: <?= $colorLlevar ?> !important; border-color: <?= $colorLlevar ?> !important;">LLEVAR</span>
                            <?php else: ?>
                                <span class="tag-mini fw-bold px-1 text-white"
                                    style="font-size: 0.6rem; background-color: <?= $colorLocal ?> !important; border-color: <?= $colorLocal ?> !important;">LOCAL</span>
                            <?php endif; ?>
                            <?= (isset($it['is_contour']) && $it['is_contour'] && !empty($it['name'])) ? "(" . strtoupper($it['name']) . ")" : (!empty($it['name']) ? strtoupper($it['name']) : "") ?>
                        </div>

                        <?php if (!empty($it['mods'])): ?>
                            <?php foreach ($it['mods'] as $m): ?>
                                <div class="mod-line">
                                    <?= $m ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (isset($it['note']) && $it['note']): ?>
                        <div class="note-box"><i class="fa-solid fa-comment-dots me-1"></i>
                            <?= strtoupper($it['note']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php } ?>