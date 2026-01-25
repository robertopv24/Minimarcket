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
        $db->prepare("UPDATE orders SET status = 'preparing', kds_kitchen_ready = 0, updated_at = NOW() WHERE id = ?")->execute([$orderId]);
    } elseif ($resetTarget === 'pizza') {
        $db->prepare("UPDATE orders SET status = 'preparing', kds_pizza_ready = 0, updated_at = NOW() WHERE id = ?")->execute([$orderId]);
    } else {
        if ($newStatus === 'ready') {
            $db->prepare("UPDATE orders SET status = 'ready', kds_kitchen_ready = 1, kds_pizza_ready = 1, updated_at = NOW() WHERE id = ?")->execute([$orderId]);
        } else {
            $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $orderId]);
        }
    }

    header("Location: despacho.php");
    exit;
}

// OBTENER PEDIDOS ACTIVOS
// (Paid -> Preparing -> Ready)
$sql = "SELECT o.*, u.name as cliente
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.status IN ('paid', 'preparing', 'ready')
        ORDER BY o.id ASC";
$stmt = $db->query($sql);
$ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

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
        background-color: #212529;
        color: #fff;
        font-size: 0.65em;
        border-radius: 3px;
        padding: 1px 4px;
    }

    .badge-dinein {
        background-color: #fff;
        color: #212529;
        border: 1px solid #212529;
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

    <div class="row">
        <?php foreach ($ordenes as $o):
            // Lógica de colores según estado
            $cardClass = 'shadow-sm';
            $headerClass = 'bg-dark text-white border-secondary';
            $statusLabel = '<span class="badge bg-secondary">En Cola</span>';
            $btnAction = '';

            if ($o['status'] == 'paid') {
                $headerClass = 'bg-secondary text-white';
                $statusLabel = '<span class="badge bg-light text-dark">⏳ Pendiente</span>';
                $btnAction = '
                    <form method="POST">
                        <input type="hidden" name="order_id" value="' . $o['id'] . '">
                        <input type="hidden" name="status" value="preparing">
                        <button class="btn btn-warning w-100 fw-bold"><i class="fa fa-fire"></i> Mandar a Cocina</button>
                    </form>';
            } elseif ($o['status'] == 'preparing') {
                $headerClass = 'bg-warning text-dark';
                $statusLabel = '<span class="badge bg-dark" style="font-size: 0.6rem;">🔥 Cocinando</span>';
                $btnAction = '
                    <form method="POST">
                        <input type="hidden" name="order_id" value="' . $o['id'] . '">
                        <input type="hidden" name="status" value="ready">
                        <button class="btn btn-success btn-xs w-100 fw-bold text-white py-1" style="font-size: 0.75rem;"><i class="fa fa-bell"></i> ¡LISTO!</button>
                    </form>';
            } elseif ($o['status'] == 'ready') {
                $cardClass = 'card-ready shadow-lg';
                $headerClass = 'header-ready';
                $statusLabel = '<span class="badge bg-white text-success fw-bold p-1" style="font-size: 0.6rem;">✅ LISTO</span>';
                $btnAction = '
                    <form method="POST" class="mb-1">
                        <input type="hidden" name="order_id" value="' . $o['id'] . '">
                        <input type="hidden" name="status" value="delivered">
                        <button class="btn btn-dark btn-xs w-100 py-1" style="font-size: 0.75rem; border: 2px solid #0d6efd;"><i class="fa fa-hand-holding-heart"></i> ENTREGAR</button>
                    </form>';
            }

            // Botones de Devolución Granular (Independientes del estado global)
            $returnButtons = '';
            if ($o['status'] != 'delivered' && ($o['kds_kitchen_ready'] || $o['kds_pizza_ready'])) {
                $returnButtons = '
                <form method="POST" class="mb-1">
                    <input type="hidden" name="order_id" value="' . $o['id'] . '">
                    <input type="hidden" name="status" value="preparing">
                    <div class="btn-group w-100 mt-1">';
                if ($o['kds_kitchen_ready']) {
                    $returnButtons .= '<button type="submit" name="reset_target" value="kitchen" class="btn btn-outline-warning btn-xs" title="Devolver a Cocina"><i class="fa fa-undo"></i> 🔥</button>';
                }
                if ($o['kds_pizza_ready']) {
                    $returnButtons .= '<button type="submit" name="reset_target" value="pizza" class="btn btn-outline-danger btn-xs" title="Devolver a Pizza"><i class="fa fa-undo"></i> 🍕</button>';
                }
                $returnButtons .= '</div></form>';
            }

            $kitchenBadge = $o['kds_kitchen_ready'] ? '<span class="badge bg-warning text-dark" title="Cocina Lista"><i class="fa fa-fire"></i></span>' : '<span class="badge bg-secondary opacity-50"><i class="fa fa-fire"></i></span>';
            $pizzaBadge = $o['kds_pizza_ready'] ? '<span class="badge bg-danger" title="Pizza Lista"><i class="fa fa-pizza-slice"></i></span>' : '<span class="badge bg-secondary opacity-50"><i class="fa fa-pizza-slice"></i></span>';
            $stationStatus = '<div class="station-status-icons ms-2">' . $kitchenBadge . ' ' . $pizzaBadge . '</div>';

            // Obtener Ítems Granulares
            $items = $orderManager->getOrderItems($o['id']);
            ?>

            <div class="col-xl-2 col-lg-3 col-md-4 mb-2">
                <div class="card h-100 <?= $cardClass ?>">
                    <div
                        class="card-header <?= $headerClass ?> d-flex justify-content-between align-items-center py-0 px-2">
                        <div class="d-flex align-items-center">
                            <h6 class="m-0 fw-bold" style="font-size: 0.85rem;">#
                                <?= $o['id'] ?>
                            </h6>
                            <?= $stationStatus ?>
                        </div>
                        <small class="fw-bold" style="font-size: 0.65rem;">
                            <?= date('h:i A', strtotime($o['created_at'])) ?>
                        </small>
                    </div>

                    <div class="card-body p-1 bg-dark text-white">
                        <h6 class="card-title fw-bold border-bottom border-secondary pb-1 mb-1" style="font-size: 1.3rem;">
                            <i class="fa fa-user-circle me-1"></i>
                            <?= htmlspecialchars($o['cliente']) ?>
                            <br>
                            <small class="text-muted fw-normal" style="font-size:0.55em">
                                <?= htmlspecialchars($o['shipping_address'] ?? '') ?>
                            </small>
                        </h6>

                        <div style="max-height: 200px; overflow-y: auto;" class="mb-2">
                            <?php foreach ($items as $item):
                                $mods = $orderManager->getItemModifiers($item['id']);
                                $groupedMods = [];
                                foreach ($mods as $m) {
                                    $groupedMods[$m['sub_item_index']][] = $m;
                                }

                                // Rayos X para Combos (Nombres)
                                $subNames = [];
                                if ($item['product_type'] == 'compound') {
                                    $comps = $productManager->getProductComponents($item['product_id']);
                                    foreach ($comps as $c) {
                                        $sName = "";
                                        if ($c['component_type'] == 'product') {
                                            $p = $productManager->getProductById($c['component_id']);
                                            $sName = $p['name'];
                                            $sStation = $p['category_station'] ?? $p['kitchen_station'] ?? '';
                                        } elseif ($c['component_type'] == 'manufactured') {
                                            $stmtM = $db->prepare("SELECT name, kitchen_station FROM manufactured_products WHERE id = ?");
                                            $stmtM->execute([$c['component_id']]);
                                            $mR = $stmtM->fetch(PDO::FETCH_ASSOC);
                                            $sName = $mR['name'] ?? 'ITEM';
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
                                        <?= htmlspecialchars($item['name']) ?>
                                    </div>
                                    <?php for ($i = 0; $i < $loop; $i++):
                                        $cMods = $groupedMods[$i] ?? [];
                                        $isTakeaway = false;
                                        foreach ($cMods as $m)
                                            if ($m['modifier_type'] == 'info' && $m['is_takeaway'])
                                                $isTakeaway = true;
                                        ?>
                                        <div class="sub-item-text">
                                            <?php if ($isTakeaway): ?>
                                                <span class="badge text-white" style="font-size: 0.5rem; padding: 1px 2px; background-color: #ef4444 !important;">LLEVAR</span>
                                            <?php else: ?>
                                                <span class="badge text-white" style="font-size: 0.5rem; padding: 1px 2px; background-color: #3b82f6 !important;">LOCAL</span>
                                            <?php endif; ?>
                                            <span class="badge bg-secondary ms-1" style="font-size: 0.5rem; padding: 1px 2px;">#
                                                <?= $i + 1 ?>
                                            </span>
                                            <?php
                                            if (isset($subNames[$i])) {
                                                $st = $subNames[$i]['station'];
                                                $stColor = ($st == 'pizza') ? 'text-danger' : (($st == 'bar') ? 'text-info' : 'text-warning');
                                                echo '<i class="fa fa-circle ' . $stColor . '" style="font-size: 0.5rem;"></i> ';
                                                echo '<span class="text-white fw-bold">(' . strtoupper($subNames[$i]['name']) . ')</span>';
                                            } else {
                                                // Indicador para producto simple
                                                $st = $itemStation ?? '';
                                                if ($st) {
                                                    $stColor = ($st == 'pizza') ? 'text-danger' : (($st == 'bar') ? 'text-info' : 'text-warning');
                                                    echo '<i class="fa fa-circle ' . $stColor . ' ms-1" style="font-size: 0.5rem;"></i>';
                                                }
                                            }
                                            ?>
                                        </div>
                                        <?php foreach ($cMods as $m):
                                            if ($m['modifier_type'] == 'remove' || $m['modifier_type'] == 'add' || $m['modifier_type'] == 'side'):
                                                $prefix = ($m['modifier_type'] == 'remove') ? '-- ' : (($m['modifier_type'] == 'add') ? '++ ' : '** ');
                                                ?>
                                                <div class="mod-inline">
                                                    <?= $prefix . strtoupper($m['ingredient_name']) ?>
                                                </div>
                                            <?php endif;
                                        endforeach; ?>
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

                        <div class="text-center mb-3">
                            <?= $statusLabel ?>
                        </div>

                        <?= $btnAction ?>
                        <?= $returnButtons ?>

                        <div class="mt-2 text-center">
                            <a href="ticket.php?id=<?= $o['id'] ?>" target="_blank"
                                class="text-muted small text-decoration-none">
                                <i class="fa fa-print"></i> Re-imprimir Ticket
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

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
    // Recarga automática cada 15 segundos para ver nuevos pedidos de caja
    setTimeout(function () {
        location.reload();
    }, 15000);
</script>

<?php require_once '../templates/footer.php'; ?>