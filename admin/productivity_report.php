<?php
// admin/productivity_report.php
require_once '../templates/autoload.php';
session_start();

// Security check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../paginas/login.php");
    exit;
}
$userManager->requireAdminAccess($_SESSION);

$title = "Reporte de Productividad y Eficiencia";
require_once '../templates/header.php';
require_once '../templates/menu.php';

// 1. Obtener todas las estaciones definidas en el sistema
$stmtStations = $db->query("SELECT DISTINCT kitchen_station FROM categories WHERE kitchen_station IS NOT NULL AND kitchen_station != 'none' AND kitchen_station != ''");
$allSystemStations = $stmtStations->fetchAll(PDO::FETCH_COLUMN);

// 2. Obtener datos del log agrupados por orden
$sql = "SELECT 
            o.id as order_id,
            u.name as cliente,
            o.created_at as created_at,
            o.delivered_at as delivered_at,
            l.station,
            l.event_type,
            l.created_at as event_time
        FROM orders o
        JOIN users u ON o.user_id = u.id
        JOIN order_time_log l ON o.id = l.order_id
        WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY o.id DESC, l.created_at ASC";

$stmt = $db->query($sql);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$orders = [];
foreach ($logs as $log) {
    $oid = $log['order_id'];
    if (!isset($orders[$oid])) {
        $orders[$oid] = [
            'id' => $oid,
            'cliente' => $log['cliente'],
            'created' => $log['created_at'],
            'delivered' => $log['delivered_at'],
            'stations' => []
        ];
    }
    $orders[$oid]['stations'][$log['station']][$log['event_type']] = $log['event_time'];
}
?>

<div class="main-content container-fluid py-4">
    <div class="row mb-4 text-center">
        <div class="col-12">
            <h2 class="text-white"><i class="fa-solid fa-clock-rotate-left me-2 text-info"></i> Eficiencia Operativa
            </h2>
            <p class="text-muted">Desglose detallado de tiempos desde recepción hasta entrega final.</p>
        </div>
    </div>

    <div class="row">
        <?php foreach ($orders as $o): ?>
            <div class="col-xl-6 mb-4">
                <div class="card bg-dark border-secondary text-white shadow-lg h-100"
                    style="border-radius: 12px; background: rgba(30, 41, 59, 0.75) !important; backdrop-filter: blur(15px);">
                    <div
                        class="card-header border-secondary d-flex justify-content-between align-items-center bg-black bg-opacity-25">
                        <div>
                            <span class="fw-bold fs-5">Orden #<?= $o['id'] ?></span>
                            <span class="ms-2 text-info"><?= strtoupper($o['cliente']) ?></span>
                        </div>
                        <div class="text-end">
                            <small class="d-block text-muted">Apertura</small>
                            <span class="badge bg-primary"><?= date('H:i:s', strtotime($o['created'])) ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row text-center mb-4">
                            <div class="col-4">
                                <small class="text-muted d-block">Creación</small>
                                <span class="fw-bold"><?= date('H:i', strtotime($o['created'])) ?></span>
                            </div>
                            <div class="col-4 border-start border-end border-secondary">
                                <small class="text-muted d-block">T. Ciclo Total</small>
                                <?php
                                if ($o['delivered']) {
                                    $diff = strtotime($o['delivered']) - strtotime($o['created']);
                                    echo "<strong class='text-success'>" . floor($diff / 60) . "m " . ($diff % 60) . "s</strong>";
                                } else {
                                    echo "<span class='text-warning animated-pulse'>En Proceso</span>";
                                }
                                ?>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Estado Final</small>
                                <span class="badge <?= $o['delivered'] ? 'bg-success' : 'bg-warning text-dark' ?>">
                                    <?= $o['delivered'] ? 'ENTREGADO' : 'PENDIENTE' ?>
                                </span>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-dark table-sm table-hover mb-0" style="font-size: 0.85rem;">
                                <thead class="bg-black bg-opacity-50">
                                    <tr>
                                        <th>Estación</th>
                                        <th>Inicio Lab.</th>
                                        <th>T. Prep</th>
                                        <th>Fin Lab.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $maxReadyTs = 0;
                                    $creationTs = strtotime($o['created']);

                                    foreach ($allSystemStations as $stName):
                                        $events = $o['stations'][$stName] ?? null;
                                        $ready = $events['ready'] ?? null;
                                        $readyTs = $ready ? strtotime($ready) : null;

                                        if ($readyTs)
                                            $maxReadyTs = max($maxReadyTs, $readyTs);

                                        $prepTimeText = "N/A";
                                        $finLabText = "N/A";

                                        if ($readyTs) {
                                            $dur = $readyTs - $creationTs;
                                            $prepTimeText = floor($dur / 60) . "m " . ($dur % 60) . "s";
                                            $finLabText = date('H:i:s', $readyTs);
                                        } elseif ($events && ($events['preparing'] ?? null)) {
                                            $finLabText = "<span class='text-warning small italic'>En Proc...</span>";
                                        }
                                        ?>
                                        <tr class="<?= !$readyTs ? 'opacity-50' : '' ?>">
                                            <td class="text-info fw-bold"><?= strtoupper($stName) ?></td>
                                            <td><?= date('H:i:s', $creationTs) ?></td>
                                            <td class="<?= $readyTs ? 'text-warning' : 'text-muted' ?>"><?= $prepTimeText ?>
                                            </td>
                                            <td><?= $finLabText ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 row g-2">
                            <div class="col-12 col-md-6">
                                <div class="p-3 bg-black bg-opacity-25 rounded border border-secondary h-100">
                                    <small class="text-muted d-block mb-1">Tiempo de Preparación (Creación -> KDS):</small>
                                    <?php if ($maxReadyTs > 0):
                                        $totalPrep = $maxReadyTs - $creationTs;
                                        ?>
                                        <strong class="text-info fs-5"><?= floor($totalPrep / 60) ?>m
                                            <?= ($totalPrep % 60) ?>s</strong>
                                    <?php else: ?>
                                        <span class="text-warning small italic">Pendiente de Cocina</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="p-3 bg-black bg-opacity-25 rounded border border-secondary h-100">
                                    <small class="text-muted d-block mb-1">Tiempo de Entrega (Despacho ->
                                        Entregado):</small>
                                    <?php if ($o['delivered'] && $maxReadyTs > 0):
                                        $deliveryDur = strtotime($o['delivered']) - $maxReadyTs;
                                        ?>
                                        <strong class="text-success fs-5"><?= floor($deliveryDur / 60) ?>m
                                            <?= ($deliveryDur % 60) ?>s</strong>
                                        <div class="mt-1 text-end">
                                            <small class="text-muted italic" style="font-size: 0.75rem;">Entregado at:
                                                <?= date('H:i:s', strtotime($o['delivered'])) ?></small>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small italic">Pendiente de Entrega</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
    .card {
        transition: transform 0.2s;
    }

    .card:hover {
        transform: translateY(-5px);
    }

    .table-hover tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.05);
    }
</style>

<?php require_once '../templates/footer.php'; ?>