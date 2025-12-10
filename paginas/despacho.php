<?php
// paginas/despacho.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../templates/autoload.php';
session_start();

// PERMISOS
require_once '../templates/kitchen_check.php';

// PROCESAR CAMBIO DE ESTADO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = $_POST['order_id'];
    $newStatus = $_POST['status'];
    // Update simple
    $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $orderId]);
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
    /* Estilos idénticos al Ticket para consistencia mental */
    .badge-takeaway {
        background-color: #212529;
        color: #fff;
        font-size: 0.75em;
        border-radius: 4px;
        padding: 2px 6px;
    }

    .badge-dinein {
        background-color: #fff;
        color: #212529;
        border: 1px solid #212529;
        font-size: 0.75em;
        border-radius: 4px;
        padding: 2px 6px;
        font-weight: bold;
    }

    .item-block {
        border-bottom: 1px dashed #ccc;
        padding-bottom: 8px;
        margin-bottom: 8px;
    }

    .sub-item-row {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 4px;
    }

    .mod-text {
        font-size: 0.85em;
        display: block;
        margin-left: 20px;
        color: #d63384;
        font-weight: 500;
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
        <div>
            <a href="kds_tv.php" target="_blank" class="btn btn-dark shadow">
                <i class="fa fa-tv me-2"></i> Pantalla Cocina (TV)
            </a>
        </div>
    </div>

    <div class="row">
        <?php foreach ($ordenes as $o):
            // Lógica de colores según estado
            $cardClass = 'shadow-sm';
            $headerClass = 'bg-white text-dark';
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
                $statusLabel = '<span class="badge bg-dark">🔥 Cocinando</span>';
                $btnAction = '
                    <form method="POST">
                        <input type="hidden" name="order_id" value="' . $o['id'] . '">
                        <input type="hidden" name="status" value="ready">
                        <button class="btn btn-success w-100 fw-bold text-white"><i class="fa fa-bell"></i> ¡LISTO PARA SERVIR!</button>
                    </form>';
            } elseif ($o['status'] == 'ready') {
                $cardClass = 'card-ready shadow-lg'; // Resaltar
                $headerClass = 'header-ready';
                $statusLabel = '<span class="badge bg-white text-success fw-bold">✅ PARA ENTREGAR</span>';
                $btnAction = '
                    <form method="POST">
                        <input type="hidden" name="order_id" value="' . $o['id'] . '">
                        <input type="hidden" name="status" value="delivered">
                        <button class="btn btn-dark w-100 py-2"><i class="fa fa-hand-holding-heart"></i> ENTREGAR AL CLIENTE</button>
                    </form>';
            }

            // Obtener Ítems Granulares
            $items = $orderManager->getOrderItems($o['id']);
            ?>

            <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                <div class="card h-100 <?= $cardClass ?>">
                    <div class="card-header <?= $headerClass ?> d-flex justify-content-between align-items-center">
                        <h5 class="m-0 fw-bold">#<?= $o['id'] ?></h5>
                        <small class="fw-bold"><?= date('h:i A', strtotime($o['created_at'])) ?></small>
                    </div>

                    <div class="card-body">
                        <h6 class="card-title fw-bold border-bottom pb-2 mb-3">
                            <i class="fa fa-user-circle me-1"></i> <?= htmlspecialchars($o['cliente']) ?>
                            <br>
                            <small class="text-muted fw-normal" style="font-size:0.8em">
                                <?= htmlspecialchars($o['shipping_address'] ?? '') ?>
                            </small>
                        </h6>

                        <div style="max-height: 300px; overflow-y: auto;" class="mb-3">
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
                                        if ($c['component_type'] == 'product') {
                                            $p = $productManager->getProductById($c['component_id']);
                                            for ($k = 0; $k < $c['quantity']; $k++)
                                                $subNames[] = $p['name'];
                                        }
                                    }
                                }

                                // Bucle
                                $loop = $item['quantity'];
                                if ($item['product_type'] == 'compound' && !empty($subNames))
                                    $loop = count($subNames);
                                ?>
                                <div class="item-block">
                                    <div class="fw-bold text-primary">
                                        <?= $item['quantity'] ?> x <?= htmlspecialchars($item['name']) ?>
                                    </div>

                                    <?php for ($i = 0; $i < $loop; $i++):
                                        $currentMods = $groupedMods[$i] ?? [];
                                        $isTakeaway = false;
                                        foreach ($currentMods as $m) {
                                            if ($m['modifier_type'] == 'info' && $m['is_takeaway'] == 1)
                                                $isTakeaway = true;
                                        }

                                        $badge = $isTakeaway
                                            ? '<span class="badge-takeaway"><i class="fa fa-shopping-bag"></i> LLEVAR</span>'
                                            : '<span class="badge-dinein"><i class="fa fa-utensils"></i> MESA</span>';

                                        $specName = isset($subNames[$i]) ? '<small class="text-muted ms-1">(' . $subNames[$i] . ')</small>' : '';
                                        ?>
                                        <div class="sub-item-row">
                                            <?= $badge ?>
                                            <span style="font-size:0.9em; font-weight:bold;">#<?= $i + 1 ?></span>
                                            <?= $specName ?>
                                        </div>

                                        <?php foreach ($currentMods as $m): ?>
                                            <?php if ($m['modifier_type'] == 'remove'): ?>
                                                <span class="mod-text text-danger">❌ SIN <?= $m['ingredient_name'] ?></span>
                                            <?php elseif ($m['modifier_type'] == 'add'): ?>
                                                <span class="mod-text text-success">➕ EXTRA <?= $m['ingredient_name'] ?></span>
                                            <?php endif; ?>
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

                        <div class="text-center mb-3">
                            <?= $statusLabel ?>
                        </div>

                        <?= $btnAction ?>

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