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

$refreshSeconds = $config->get('kds_refresh_interval', 30);
$colorLlevar = $config->get('kds_color_llevar', '#ef4444');
$colorLocal = $config->get('kds_color_local', '#3b82f6');
$colorDelivery = $config->get('kds_color_delivery', '#10b981');
$warningMedium = $config->get('kds_warning_time_medium', 15);
$warningLate = $config->get('kds_warning_time_late', 25);
$colorWarningMedium = $config->get('kds_color_warning_medium', '#3b82f6');
$colorWarningLate = $config->get('kds_color_warning_late', '#ef4444');
$colorPreparing = $config->get('kds_color_preparing', '#f59e0b');
$soundEnabled = $config->get('kds_sound_enabled', '1');
$useShortCodes = ($config->get('kds_use_short_codes', '0') == '1');
$useSimpleFlow = ($config->get('kds_simple_flow', '0') == '1');

// Nuevos colores
$colorCardBg = $config->get('kds_color_card_bg', '#ffffff');
$colorMixedBg = $config->get('kds_color_mixed_bg', '#fff3cd');
$colorModAdd = $config->get('kds_color_mod_add', '#198754');
$colorModRemove = $config->get('kds_color_mod_remove', '#dc3545');
$colorModSide = $config->get('kds_color_mod_side', '#0dcaf0');
$colorProductName = $config->get('kds_product_name_color', '#ffffff');
$soundUrl = '../assets/sounds/ping_a.mp3';
?>
<?php
require_once '../funciones/KDSController.php';

$listaPizza = getKDSData('pizza');
$listaCocina = getKDSData('kitchen');

// Función helper para renderizar tickets (se asume que existe o se define en template)
if (!function_exists('renderTicket')) {
    require_once 'kds_template.php';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>KDS - MONITOR TV</title>
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
            --accent-orange:
                <?= $colorPreparing ?>
            ;
            --accent-blue:
                <?= $colorLocal ?>
            ;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --preparing-bg:
                <?= $colorPreparing ?>
            ;
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
            --warning-medium:
                <?= $colorWarningMedium ?>
            ;
            --warning-late:
                <?= $colorWarningLate ?>
            ;
            --status-preparing-active:
                <?= $colorPreparing ?>
            ;
        }

        body {
            background-color: var(--bg-dark);
            color: white;
            font-family: 'Outfit', sans-serif;
            margin: 0;
            height: 100vh;
            overflow: hidden;
            font-size: 13px;
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
            padding: 0.35rem;
            margin-bottom: 0.35rem;
            border-radius: 4px;
            font-size: 1rem;
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

        .grid-kds {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.4rem;
            padding: 0.4rem;
        }

        .ticket {
            background: var(--card-white);
            color: var(--text-dark);
            border-radius: 6px;
            margin-bottom: 0.4rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 2px solid transparent;
            transition: all 0.15s ease;
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
        }


        .ticket-head.status-preparing {
            background-color: var(--preparing-bg) !important;
        }

        .ticket-head.status-medium {
            background-color: var(--warning-medium) !important;
        }

        .ticket-head.status-late {
            background-color: var(--warning-late) !important;
        }

        /* Full ticket highlight only for Preparing Active */
        .ticket.status-preparing-active {
            border-color: var(--status-preparing-active);
            box-shadow: 0 0 12px var(--status-preparing-active);
        }

        .ticket.status-preparing-active .ticket-head {
            background-color: var(--status-preparing-active) !important;
            animation: pulse-opac 2s infinite;
        }

        /* Alertas de tiempo: siempre dominan sobre cualquier otro estado */
        .ticket .ticket-head.status-medium {
            background-color: var(--warning-medium) !important;
            animation: none !important;
        }

        .ticket .ticket-head.status-late {
            background-color: var(--warning-late) !important;
            animation: none !important;
        }

        @keyframes pulse-opac {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }

            100% {
                opacity: 1;
            }
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
            color: var(--text-dark) !important;
            margin-bottom: 0.2rem;
            display: block;
            border-bottom: 1.5px solid #e2e8f0;
        }

        .sub-item-line {
            display: flex;
            align-items: center;
            gap: 3px;
            font-size: 0.73rem;
            font-weight: 700;
            color: #1e293b;
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
            font-size: 0.62rem;
            font-weight: 700;
            margin-left: 12px;
            line-height: 1.05;
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
            border-color: var(--warning-late) !important;
            animation: pulse-shadow-red 2s infinite;
        }

        .medium-warning {
            border-color: var(--warning-medium) !important;
        }

        @keyframes pulse-shadow-red {
            0% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }
    </style>
</head>

<body>
    <!-- Init Sound Overlay (Moved Outside) -->
    <div id="sound-init-overlay"
        style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); color: white; display: flex; align-items: center; justify-content: center; z-index: 9999; cursor: pointer;">
        <div class="text-center">
            <i class="fa fa-bell fa-4x mb-3 text-primary"></i>
            <h3>Activar Aviso TV</h3>
            <p>Haz clic para iniciar sistema de audio</p>
        </div>
    </div>

    <?php
    // Merge visible lists to track WHAT IS ACTUALLY SHOWN
    // Filter IDs from listaPizza and listaCocina
    $visibleIds = [];
    foreach ($listaPizza as $t)
        $visibleIds[] = $t['info']['id'];
    foreach ($listaCocina as $t)
        $visibleIds[] = $t['info']['id'];
    $visibleIds = array_unique($visibleIds); // Remove duplicates if same order is in both cols
    $visibleIdsStr = implode(',', $visibleIds);
    ?>
    <div class="row h-100 g-0" id="main-monitor-row" data-visible-ids="<?= $visibleIdsStr ?>">
        <div class="col-6 station-col">
            <div class="station-header header-pizza d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-pizza-slice me-2"></i> MONITOR PIZZAS</span>
                <button class="btn btn-sm btn-light text-danger border-0" onclick="playPing(true)"
                    style="font-size:0.7rem; padding: 2px 6px;">
                    <i class="fa fa-volume-high"></i>
                </button>
            </div>
            <div class="grid-kds">
                <?php foreach ($listaPizza as $t)
                    renderTicket($t); ?>
            </div>
        </div>
        <div class="col-6 station-col">
            <div class="station-header header-cocina d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-burger me-2"></i> MONITOR COCINA</span>
                <button class="btn btn-sm btn-light text-warning border-0" onclick="playPing(true)"
                    style="font-size:0.7rem; padding: 2px 6px;">
                    <i class="fa fa-volume-high"></i>
                </button>
            </div>
            <div class="grid-kds">
                <?php foreach ($listaCocina as $t)
                    renderTicket($t); ?>
            </div>
        </div>
    </div>

    <script>
        const STATION = 'tv';
        let currentIdsStr = document.getElementById('main-monitor-row').dataset.visibleIds || '';
        let currentIds = currentIdsStr ? currentIdsStr.split(',') : [];

        // Sound logic
        const soundUrl = '<?= $config->get('kds_sound_url_dispatch', '../assets/sounds/ping_a.mp3') ?>';
        let audioContext = null;

        function checkAudioState() {
            if (!audioContext) audioContext = new (window.AudioContext || window.webkitAudioContext)();
            audioContext.resume().then(() => {
                if (audioContext.state === 'running') {
                    document.getElementById('sound-init-overlay').style.display = 'none';
                }
            }).catch(() => { });
        }
        checkAudioState();

        document.getElementById('sound-init-overlay').addEventListener('click', () => {
            if (!audioContext) audioContext = new (window.AudioContext || window.webkitAudioContext)();
            audioContext.resume().then(() => {
                document.getElementById('sound-init-overlay').style.display = 'none';
                playPing(true);
            });
        });

        function playPing(isTest = false) {
            const audio = new Audio(soundUrl);
            audio.play().catch(() => playOscillatorBackup());
        }

        function playOscillatorBackup() {
            if (!audioContext) audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(880, audioContext.currentTime);
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            gainNode.gain.setValueAtTime(0, audioContext.currentTime);
            gainNode.gain.linearRampToValueAtTime(0.5, audioContext.currentTime + 0.05);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
            oscillator.start();
            oscillator.stop(audioContext.currentTime + 0.5);
        }

        function refreshKDS() {
            const url = window.location.href.split('?')[0];
            const params = new URLSearchParams(window.location.search);
            params.set('ts', new Date().getTime());

            fetch(`${url}?${params.toString()}`)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newRow = doc.getElementById('main-monitor-row');
                    if (newRow) {
                        const currentRow = document.getElementById('main-monitor-row');
                        currentRow.innerHTML = newRow.innerHTML;
                        const newIdsStr = newRow.dataset.visibleIds || '';
                        const newIds = newIdsStr ? newIdsStr.split(',') : [];
                        if (newIds.some(id => !currentIds.includes(id))) playPing();
                        currentIds = newIds;
                        currentRow.dataset.visibleIds = newIdsStr;
                    }
                }).catch(err => console.error("Refresh failed", err));
        }

        setInterval(refreshKDS, <?= $refreshSeconds * 1000 ?>);

        function updateStatus(orderId, targetStatus, station) {
            fetch('../ajax/update_kds_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId, station: station, status: targetStatus })
            }).then(res => res.json()).then(data => {
                if (data.success) refreshKDS();
            }).catch(err => console.error("Update failed", err));
        }

        function updateLiveTimers() {
            const now = Math.floor(Date.now() / 1000);
            const warningMedium = <?= $warningMedium ?>;
            const warningLate = <?= $warningLate ?>;

            document.querySelectorAll('.ticket').forEach(ticket => {
                const timerSpan = ticket.querySelector('.kds-timer');
                if (!timerSpan) return;
                const startTime = parseInt(timerSpan.dataset.startTime);
                const diff = now - startTime;
                const mins = Math.floor(diff / 60);
                const secs = diff % 60;
                timerSpan.textContent = `${mins}m ${secs.toString().padStart(2, '0')}s`;

                const head = ticket.querySelector('.ticket-head');
                if (head) {
                    ticket.classList.remove('medium-warning', 'late-warning');
                    head.classList.remove('status-preparing', 'status-medium', 'status-late');
                    if (mins >= warningLate) {
                        ticket.classList.remove('status-preparing-active');
                        ticket.classList.add('late-warning');
                        head.classList.add('status-late');
                    } else if (mins >= warningMedium) {
                        ticket.classList.remove('status-preparing-active');
                        ticket.classList.add('medium-warning');
                        head.classList.add('status-medium');
                    } else if (!ticket.classList.contains('status-preparing-active')) {
                        head.classList.add('status-preparing');
                    }
                }
            });
        }
        setInterval(updateLiveTimers, 1000);
    </script>
</body>

</html>

<?php
function renderTicket($data)
{
    $orden = $data['info'];
    $items = $data['items'];
    $diffSeconds = max(0, time() - strtotime($orden['created_at']));
    $mins = floor($diffSeconds / 60);
    $secs = $diffSeconds % 60;

    global $warningMedium, $warningLate;
    $stationType = $data['station_type'] ?? 'kitchen';
    $isPrep = $orden['is_prep_here'];
    $targetStatus = $orden['simple_flow'] ? 'ready' : ($isPrep ? 'ready' : 'preparing');

    // Las alertas de tiempo tienen prioridad máxima sobre el estado de preparación
    if ($mins >= $warningLate)
        $bgStatus = 'status-late';
    elseif ($mins >= $warningMedium)
        $bgStatus = 'status-medium';
    elseif ($isPrep)
        $bgStatus = 'status-preparing-active';
    else
        $bgStatus = 'status-preparing';

    $borderClass = ($mins >= $warningLate) ? 'late-warning' : (($mins >= $warningMedium) ? 'medium-warning' : '');
    ?>
    <div class="ticket <?= $borderClass ?> <?= ($data['is_mixed'] ?? false) ? 'ticket-mixed' : '' ?> <?= $isPrep ? 'status-preparing-active' : '' ?>"
        id="ticket-<?= $orden['id'] ?>-<?= $stationType ?>">
        <div class="ticket-head <?= $bgStatus ?>"
            onclick="updateStatus(<?= $orden['id'] ?>, '<?= $targetStatus ?>', '<?= $stationType ?>')">
            <div class="d-flex align-items-center">
                <span class="fw-bold">#<?= $orden['id'] ?></span>
                <small class="ms-1 opacity-75 d-inline-block text-truncate"
                    style="max-width: 80px;"><?= strtoupper($orden['cliente_display']) ?></small>
                <?php if ($orden['kds_kitchen_ready']): ?><i class="fa fa-fire ms-2 text-success"
                        title="Cocina Lista"></i><?php endif; ?>
                <?php if ($orden['kds_pizza_ready']): ?><i class="fa fa-pizza-slice ms-1 text-danger"
                        title="Pizza Lista"></i><?php endif; ?>
            </div>
            <span class="kds-timer" data-start-time="<?= strtotime($orden['created_at']) ?>"><?= $mins ?>m
                <?= str_pad($secs, 2, '0', STR_PAD_LEFT) ?>s</span>
        </div>
        <div class="ticket-body">
            <?php foreach ($items as $it): ?>
                <div class="minimal-row">
                    <?php if (isset($it['is_main']) && $it['is_main']): ?>
                        <span class="main-item-line"># <?= strtoupper($it['name']) ?></span>
                    <?php endif; ?>
                    <?php if (!(isset($it['is_main']) && $it['is_main']) && (!empty($it['name']))): ?>
                        <div class="sub-item-line">
                            <span class="item-index">#</span>
                            <?php
                            global $colorLlevar, $colorLocal, $colorDelivery;
                            $cType = $it['consumption_type'] ?? 'dine_in';
                            $bgColor = ($cType === 'delivery') ? $colorDelivery : (($cType === 'takeaway' || ($it['is_takeaway'] ?? false)) ? $colorLlevar : $colorLocal);
                            $label = ($cType === 'delivery') ? 'DELIVERY' . ($orden['delivery_tier'] ? " ({$orden['delivery_tier']})" : "") : (($cType === 'takeaway' || ($it['is_takeaway'] ?? false)) ? 'LLEVAR' : 'LOCAL');
                            ?>
                            <span class="tag-mini fw-bold px-1 text-white"
                                style="background-color: <?= $bgColor ?> !important;"><?= $label ?></span>
                            <?= strtoupper($it['name']) ?>
                        </div>
                        <?php foreach (($it['mods'] ?? []) as $m): ?>
                            <div class="mod-line"><?= $m ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if (isset($it['note']) && $it['note']): ?>
                        <div class="note-box"><i class="fa-solid fa-comment-dots me-1"></i><?= strtoupper($it['note']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php } ?>