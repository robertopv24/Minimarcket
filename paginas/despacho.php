<?php
// paginas/despacho.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../templates/autoload.php';
session_start();

// PERMISOS
// Security Check (Refactored to UserManager)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$userManager->requireKitchenAccess($_SESSION);

// PROCESAR CAMBIO DE ESTADO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = $_POST['order_id'];
    $newStatus = $_POST['status'];
    $resetTarget = $_POST['reset_target'] ?? null;

    if ($resetTarget === 'kitchen') {
        // Al devolver Cocina:
        // Si Pizza está lista list (1), mantenemos status 'ready' (Global).
        // Si Pizza NO está lista (0), bajamos a 'preparing'.
        // PERO primero leemos el estado actual de la orden para saber Pizza
        $stCheck = $db->prepare("SELECT kds_pizza_ready FROM orders WHERE id = ?");
        $stCheck->execute([$orderId]);
        $rStr = $stCheck->fetch(PDO::FETCH_ASSOC);

        $newGlobal = ($rStr['kds_pizza_ready']) ? 'ready' : 'preparing';

        $db->prepare("UPDATE orders SET status = ?, kds_kitchen_ready = 0, updated_at = NOW() WHERE id = ?")
            ->execute([$newGlobal, $orderId]);

    } elseif ($resetTarget === 'pizza') {
        // Al devolver Pizza:
        // Si Cocina está lista (1), mantenemos 'ready'.
        // Si no, 'preparing'.
        $stCheck = $db->prepare("SELECT kds_kitchen_ready FROM orders WHERE id = ?");
        $stCheck->execute([$orderId]);
        $rStr = $stCheck->fetch(PDO::FETCH_ASSOC);

        $newGlobal = ($rStr['kds_kitchen_ready']) ? 'ready' : 'preparing';

        $db->prepare("UPDATE orders SET status = ?, kds_pizza_ready = 0, updated_at = NOW() WHERE id = ?")
            ->execute([$newGlobal, $orderId]);
    } else {
        if ($newStatus === 'ready') {
            $db->prepare("UPDATE orders SET status = 'ready', kds_kitchen_ready = 1, kds_pizza_ready = 1, updated_at = NOW() WHERE id = ?")->execute([$orderId]);
        } else {
            $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $orderId]);
        }
        // REGISTRO DE HITO DE TIEMPO (SISTEMA)
        $orderManager->logStatusMilestone($orderId, 'system', $newStatus);
    }

    header("Location: despacho.php");
    exit;
}

// OBTENER PEDIDOS ACTIVOS
// (Paid -> Preparing -> Ready)
$sql = "SELECT o.*, u.name as cliente
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.status IN ('ready')
        ORDER BY o.id ASC";
$stmt = $db->query($sql);
$ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../templates/header.php';
require_once '../templates/menu.php';

$refreshSeconds = $config->get('kds_refresh_interval', 30);
$colorLlevar = $config->get('kds_color_llevar', '#ef4444');
$colorLocal = $config->get('kds_color_local', '#3b82f6');
$colorDelivery = $config->get('kds_color_delivery', '#10b981');
$useShortCodes = ($config->get('kds_use_short_codes', '0') == '1');

// Nuevos colores modificadores
$colorModAdd = $config->get('kds_color_mod_add', '#198754');
$colorModRemove = $config->get('kds_color_mod_remove', '#dc3545');
$colorModSide = $config->get('kds_color_mod_side', '#0dcaf0');
$soundUrl = $config->get('kds_sound_url_dispatch', '../assets/sounds/success.mp3');
?>
<!-- Meta-refresh removed to use AJAX updates -->

<style>
    body {
        background-color: #0f172a;
        color: #f8fafc;
        font-family: 'Outfit', sans-serif;
        font-size: 13px;
    }

    .container-fluid {
        padding: 0.5rem;
    }

    h2 {
        font-size: 1.25rem !important;
        margin-bottom: 0.25rem !important;
    }

    p.lead {
        font-size: 0.85rem !important;
        margin-bottom: 0.5rem !important;
    }

    /* Estilos idénticos al Ticket para consistencia mental */
    .badge-takeaway {
        background-color:
            <?= $colorLlevar ?>
        ;
        color: #fff;
        font-size: 0.65em;
        border-radius: 3px;
        padding: 1px 4px;
    }

    .badge-delivery {
        background-color:
            <?= $colorDelivery ?>
        ;
        color: #fff;
        font-size: 0.65em;
        border-radius: 3px;
        padding: 1px 4px;
    }

    .badge-dinein {
        background-color:
            <?= $colorLocal ?>
        ;
        color: #fff;
        font-size: 0.65em;
        border-radius: 3px;
        padding: 1px 4px;
        font-weight: bold;
    }

    /* Rediseño Minimalista (Sin Tarjetas Internas) */
    .minimal-item-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .minimal-row {
        border-bottom: 1px solid #f1f5f9;
        padding: 2px 0;
        line-height: 1.2;
    }

    .minimal-row:last-child {
        border-bottom: none;
    }

    .item-main-name {
        font-weight: 800;
        color: #ffffff;
        font-size: 0.9rem;
    }

    .sub-item-text {
        font-size: 0.75rem;
        color: #e2e8f0;
        margin-left: 8px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .mod-inline {
        color: #ffffff;
        font-size: 0.7rem;
        font-weight: 500;
        margin-left: 14px;
    }

    .btn-xs {
        padding: 1px 5px;
        font-size: 0.7rem;
    }

    .card-ready {
        border: 2px solid #198754;
        box-shadow: 0 0 15px rgba(25, 135, 84, 0.3);
    }

    .header-ready {
        background-color: #198754 !important;
        color: white;
    }

    /* Estilos para botones de cabecera compactos */
    .btn-header-action {
        background: none;
        border: none;
        padding: 0 4px;
        font-size: 0.85rem;
        transition: transform 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn-header-action:hover {
        transform: scale(1.2);
    }

    .btn-header-action:disabled {
        opacity: 0.3;
        cursor: not-allowed;
        transform: none;
    }

    .header-actions-group {
        display: flex;
        gap: 6px;
        align-items: center;
        background: rgba(255, 255, 255, 0.1);
        padding: 1px 6px;
        border-radius: 20px;
        margin: 0 8px;
    }

    /* Rejilla de alta densidad personalizada */
    .dispatch-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        justify-content: flex-start;
    }

    .dispatch-card-wrapper {
        flex: 0 0 auto;
        width: 210px;
        /* Ancho optimizado para 8-9 columnas en Full HD */
    }

    @media (max-width: 768px) {
        .dispatch-card-wrapper {
            width: calc(50% - 6px);
        }
    }

    @media (max-width: 480px) {
        .dispatch-card-wrapper {
            width: 100%;
        }
    }
</style>

<div class="container-fluid mt-4 mb-5 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>🚀 Centro de Despacho</h2>
            <p class="text-muted">Vista operativa de pedidos en curso</p>
        </div>
        <div class="btn-group shadow">
            <a href="kds_cocina_tv.php" target="_blank" class="btn btn-dark">
                <i class="fa fa-tv me-2"></i> KDS Cocina
            </a>
            <a href="kds_pizza_tv.php" target="_blank" class="btn btn-danger">
                <i class="fa fa-pizza-slice me-2"></i> KDS Pizza
            </a>
        </div>
    </div>

    <div class="dispatch-grid" id="dispatch-grid">
        <?php foreach ($ordenes as $o):
            renderDispatchCard($o);
        endforeach; ?>

        <?php if (empty($ordenes)): ?>
            <div class="col-12 text-center py-5">
                <div class="text-muted mb-3"><i class="fa fa-check-circle fa-4x"></i></div>
                <h3>Todo al día</h3>
                <p class="text-muted">No hay pedidos pendientes de despacho.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Configuración desde PHP
    const REFRESH_INTERVAL = <?= $refreshSeconds * 1000 ?>;
    const soundUrl = '<?= $soundUrl ?>';
    let currentCount = <?= count($ordenes) ?>;

    // AJAX Refresh Logic
    function refreshDispatch() {
        fetch(window.location.href)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newGrid = doc.getElementById('dispatch-grid');

                if (newGrid) {
                    const currentGrid = document.getElementById('dispatch-grid');

                    // Contar tarjetas nuevas para el sonido
                    const newCards = newGrid.querySelectorAll('.dispatch-card-wrapper').length;
                    if (newCards > currentCount) {
                        playPing();
                    }
                    currentCount = newCards;

                    currentGrid.innerHTML = newGrid.innerHTML;
                }
            })
            .catch(err => console.error("Refresh failed", err));
    }

    // Intervalo de refresco
    setInterval(refreshDispatch, REFRESH_INTERVAL);

    // Initial Overlay
    const overlaySeen = sessionStorage.getItem('dispatch_sound_active');

    const overlay = document.createElement('div');
    overlay.id = 'sound-init-overlay';
    overlay.style.cssText = "position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); color: white; display: flex; align-items: center; justify-content: center; z-index: 9999; cursor: pointer;";
    overlay.innerHTML = '<div class="text-center"><i class="fa fa-truck-fast fa-4x mb-3 text-success"></i><h3>Activar Sonido Despacho</h3><p>Haz clic para iniciar</p></div>';

    if (overlaySeen === 'true') {
        overlay.style.display = 'none';
        // Re-init AudioContext silently if possible or wait for next user action
    }
    document.body.appendChild(overlay);

    let audioContext = null;

    overlay.addEventListener('click', () => {
        sessionStorage.setItem('dispatch_sound_active', 'true');
        if (!audioContext) audioContext = new (window.AudioContext || window.webkitAudioContext)();
        audioContext.resume().then(() => {
            overlay.style.display = 'none';
            playPing(true);
        });
    });

    function playPing(isTest = false) {
        const audio = new Audio(soundUrl);
        const playPromise = audio.play();
        if (playPromise !== undefined) {
            playPromise.catch(error => {
                playOscillatorBackup();
            });
        }
    }

    function playOscillatorBackup() {
        if (!audioContext) audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.type = 'triangle';
        oscillator.frequency.setValueAtTime(523.25, audioContext.currentTime); // C5
        oscillator.frequency.linearRampToValueAtTime(659.25, audioContext.currentTime + 0.1); // E5

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        gainNode.gain.setValueAtTime(0, audioContext.currentTime);
        gainNode.gain.linearRampToValueAtTime(0.5, audioContext.currentTime + 0.1);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.6);

        oscillator.start();
        oscillator.stop(audioContext.currentTime + 0.6);
    }
</script>

<?php
function renderDispatchCard($o)
{
    global $db, $orderManager, $productManager, $colorLlevar, $colorLocal, $colorDelivery, $useShortCodes, $colorModAdd, $colorModRemove, $colorModSide;

    // Lógica de colores según estado
    $cardClass = 'shadow-sm';
    $headerClass = 'bg-dark text-white border-secondary';

    // Estructura de Acciones en Cabecera (ENTREGAR + DEVOLUCIONES)
    $headerActions = '<div class="header-actions-group">';

    // 1. Botón Entregar
    $deliverAction = '';
    if ($o['status'] == 'ready') {
        $deliverAction = '
            <form method="POST" class="d-inline">
                <input type="hidden" name="order_id" value="' . $o['id'] . '">
                <input type="hidden" name="status" value="delivered">
                <button type="submit" class="btn-header-action text-white" title="ENTREGAR">
                    <i class="fa fa-hand-holding-heart"></i>
                </button>
            </form>';
    }

    // 2. Indicadores/Botones de Devolución
    $returnKitchen = '
        <form method="POST" class="d-inline">
            <input type="hidden" name="order_id" value="' . $o['id'] . '">
            <input type="hidden" name="status" value="preparing">
            <input type="hidden" name="reset_target" value="kitchen">
            <button type="submit" class="btn-header-action ' . ($o['kds_kitchen_ready'] ? 'text-warning' : 'text-secondary opacity-25') . '" ' . (!$o['kds_kitchen_ready'] ? 'disabled' : '') . ' title="Devolver a Cocina">
                <i class="fa fa-fire"></i>
            </button>
        </form>';

    $returnPizza = '
        <form method="POST" class="d-inline">
            <input type="hidden" name="order_id" value="' . $o['id'] . '">
            <input type="hidden" name="status" value="preparing">
            <input type="hidden" name="reset_target" value="pizza">
            <button type="submit" class="btn-header-action ' . ($o['kds_pizza_ready'] ? 'text-danger' : 'text-secondary opacity-25') . '" ' . (!$o['kds_pizza_ready'] ? 'disabled' : '') . ' title="Devolver a Pizza">
                <i class="fa fa-pizza-slice"></i>
            </button>
        </form>';

    $headerActions .= $returnKitchen . $returnPizza . $deliverAction . '</div>';

    // Obtener Ítems
    $items = $orderManager->getOrderItems($o['id']);
    ?>
    <div class="dispatch-card-wrapper mb-2">
        <div class="card h-100 <?= $cardClass ?>">
            <div class="card-header <?= $headerClass ?> d-flex justify-content-between align-items-center py-0 px-2">
                <div class="d-flex align-items-center">
                    <h6 class="m-0 fw-bold" style="font-size: 0.85rem;">#<?= $o['id'] ?></h6>
                    <?= $headerActions ?>
                </div>
                <small class="fw-bold" style="font-size: 0.65rem;">
                    <?= date('h:i A', strtotime($o['created_at'])) ?>
                </small>
            </div>

            <div class="card-body p-1 bg-dark text-white">
                <h6 class="card-title fw-bold border-bottom border-secondary pb-1 mb-1" style="font-size: 0.85rem;">
                    <i class="fa fa-user-circle me-1"></i>
                    <?php
                    $displayClient = (!empty($o['shipping_address']) && $o['shipping_address'] !== 'Tienda Física')
                        ? $o['shipping_address']
                        : $o['cliente'];
                    echo htmlspecialchars(strtoupper($displayClient));
                    ?>
                </h6>

                <div style="max-height: 250px; overflow-y: auto;">
                    <?php foreach ($items as $item):
                        $mods = $orderManager->getItemModifiers($item['id']);
                        $groupedMods = [];
                        foreach ($mods as $m) {
                            $groupedMods[$m['sub_item_index']][] = $m;
                        }

                        // Ordenar CADA GRUPO de modificadores
                        foreach ($groupedMods as &$gMods) {
                            usort($gMods, function ($a, $b) {
                                $order = ['side' => 1, 'add' => 2, 'remove' => 3];
                                // Normalizar a minúsculas
                                $ta = strtolower($a['modifier_type'] ?? '');
                                $tb = strtolower($b['modifier_type'] ?? '');

                                // Si no es un tipo estándar (add/remove), asumir 'side' (1)
                                // excepto si es 'info' que lo mandamos al fondo (99)
                                if ($ta != 'add' && $ta != 'remove' && $ta != 'side')
                                    $va = ($ta == 'info') ? 99 : 1;
                                else
                                    $va = $order[$ta] ?? 99;

                                if ($tb != 'add' && $tb != 'remove' && $tb != 'side')
                                    $vb = ($tb == 'info') ? 99 : 1;
                                else
                                    $vb = $order[$tb] ?? 99;

                                return $va <=> $vb;
                            });
                        }
                        unset($gMods); // Romper referencia
                
                        // Rayos X para Combos (Nombres)
                        $subNames = [];
                        if ($item['product_type'] == 'compound') {
                            $comps = $productManager->getProductComponents($item['product_id']);
                            foreach ($comps as $c) {
                                $sName = "";
                                if ($c['component_type'] == 'product') {
                                    $p = $productManager->getProductById($c['component_id']);
                                    $sName = ($useShortCodes && !empty($p['short_code'])) ? $p['short_code'] : $p['name'];
                                    $sStation = $p['category_station'] ?? $p['kitchen_station'] ?? '';
                                } elseif ($c['component_type'] == 'manufactured') {
                                    $stmtM = $db->prepare("SELECT name, kitchen_station, short_code FROM manufactured_products WHERE id = ?");
                                    $stmtM->execute([$c['component_id']]);
                                    $mR = $stmtM->fetch(PDO::FETCH_ASSOC);
                                    $sName = ($useShortCodes && !empty($mR['short_code'])) ? $mR['short_code'] : ($mR['name'] ?? 'ITEM');
                                    $sStation = $mR['kitchen_station'] ?? '';
                                }
                                if ($sName) {
                                    for ($k = 0; $k < $c['quantity']; $k++) {
                                        $subNames[] = [
                                            'name' => $sName,
                                            'station' => $sStation
                                        ];
                                    }
                                }
                            }
                        } else {
                            // Para productos simples, obtener la estación principal
                            $pInfo = $productManager->getProductById($item['product_id']);
                            $itemStation = $pInfo['category_station'] ?? $pInfo['kitchen_station'] ?? '';
                        }

                        $loop = count($subNames) ?: $item['quantity'];
                        ?>
                        <div class="minimal-row">
                            <div class="item-main-name">
                                <?= $item['quantity'] ?> x
                                <?= htmlspecialchars(($useShortCodes && !empty($item['short_code'])) ? $item['short_code'] : $item['name']) ?>
                            </div>
                            <?php for ($i = 0; $i < $loop; $i++):
                                $cMods = $groupedMods[$i] ?? [];
                                $isTakeaway = false;
                                foreach ($cMods as $m)
                                    if ($m['modifier_type'] == 'info' && $m['is_takeaway'])
                                        $isTakeaway = true;
                                ?>
                                <div class="sub-item-text">
                                    <?php
                                    $cType = $item['consumption_type'] ?? 'dine_in';
                                    if ($cType === 'delivery'): ?>
                                        <span class="badge badge-delivery text-white"
                                            style="font-size: 0.5rem; padding: 1px 2px;">DELIVERY</span>
                                    <?php elseif ($isTakeaway || $cType === 'takeaway'): ?>
                                        <span class="badge badge-takeaway text-white"
                                            style="font-size: 0.5rem; padding: 1px 2px;">LLEVAR</span>
                                    <?php else: ?>
                                        <span class="badge badge-dinein text-white"
                                            style="font-size: 0.5rem; padding: 1px 2px;">LOCAL</span>
                                    <?php endif; ?>

                                    <span class="badge bg-secondary ms-1"
                                        style="font-size: 0.5rem; padding: 1px 2px;">#<?= $i + 1 ?></span>
                                    <?php
                                    if (isset($subNames[$i])) {
                                        $st = $subNames[$i]['station'];
                                        $stColor = ($st == 'pizza') ? 'text-danger' : (($st == 'bar') ? 'text-info' : 'text-warning');
                                        echo '<i class="fa fa-circle ' . $stColor . '" style="font-size: 0.5rem;"></i> ';
                                        echo '<span class="text-white fw-bold">** (' . strtoupper($subNames[$i]['name']) . ')</span>';
                                    } else {
                                        $st = $itemStation ?? '';
                                        if ($st) {
                                            $stColor = ($st == 'pizza') ? 'text-danger' : (($st == 'bar') ? 'text-info' : 'text-warning');
                                            echo '<i class="fa fa-circle ' . $stColor . ' ms-1" style="font-size: 0.5rem;"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <?php foreach ($cMods as $m):
                                    $mName = ($useShortCodes && !empty($m['short_code'])) ? $m['short_code'] : $m['ingredient_name'];
                                    $type = strtolower($m['modifier_type'] ?? '');

                                    // Saltamos info notes aquí porque se muestran abajo
                                    if ($type == 'info')
                                        continue;

                                    if ($type == 'add') {
                                        $mColor = $colorModAdd;
                                        $mPrefix = '++';
                                    } elseif ($type == 'remove') {
                                        $mColor = $colorModRemove;
                                        $mPrefix = '--';
                                    } else {
                                        $mColor = $colorModSide;
                                        $mPrefix = '**';
                                    }
                                    ?>
                                    <div class="mod-inline" style="color: <?= $mColor ?>;">
                                        <small><?= $mPrefix ?>                 <?= strtoupper($mName ?? '') ?></small>
                                    </div>
                                <?php endforeach; ?>
                                <?php if ($i == 0):
                                    foreach ($mods as $gm) {
                                        if ($gm['sub_item_index'] == -1 && $gm['modifier_type'] == 'info' && !empty($gm['note'])) {
                                            echo '<div class="alert alert-warning p-1 mb-0 mt-1 small"><i class="fa fa-exclamation-triangle"></i> ' . $gm['note'] . '</div>';
                                        }
                                    }
                                endif; ?>
                            <?php endfor; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-1 text-center border-top border-secondary pt-1">
                    <a href="ticket.php?id=<?= $o['id'] ?>" target="_blank" class="text-muted"
                        style="font-size: 0.65rem; text-decoration:none;">
                        <i class="fa fa-print"></i> Re-imprimir Ticket
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<?php require_once '../templates/footer.php'; ?>