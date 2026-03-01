<?php
// Production settings applied via autoload

require_once '../templates/autoload.php';

session_start();
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

require_once '../templates/header.php';
require_once '../templates/menu.php';

// 1. Obtener datos de la Venta
$ventaId = $_GET['id'] ?? 0;
$venta = $orderManager->getOrderById($ventaId);

if (!$venta) {
    echo '<div class="container mt-5"><div class="alert alert-danger">Venta no encontrada.</div><a href="ventas.php" class="btn btn-primary">Volver</a></div>';
    require_once '../templates/footer.php';
    exit;
}

// 2. Obtener productos de la venta
$productos = $orderManager->getOrderItems($ventaId);

// 3. Obtener Transacciones Financieras (Tesorería)
// Buscamos todo el dinero que entró o salió (vuelto) por esta orden
$stmt = $db->prepare("SELECT t.*, pm.name as method_name
                      FROM transactions t
                      JOIN payment_methods pm ON t.payment_method_id = pm.id
                      WHERE t.reference_type = 'order' AND t.reference_id = ?
                      ORDER BY t.created_at ASC");
$stmt->execute([$ventaId]);
$transacciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cálculos para el resumen
$totalPagado = 0;
$totalVuelto = 0;
foreach ($transacciones as $tr) {
    if ($tr['type'] == 'income')
        $totalPagado += $tr['amount_usd_ref'];
    if ($tr['type'] == 'expense')
        $totalVuelto += $tr['amount_usd_ref'];
}
?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>🧾 Detalle de Venta #<?= htmlspecialchars($venta['id']) ?></h2>
        <div>
            <a href="editar_venta.php?id=<?= $venta['id'] ?>" class="btn btn-warning"><i class="fa fa-edit"></i> Editar
                Estado</a>
            <a href="ventas.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Volver</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">Información del Pedido</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Cliente:</strong>
                                <?= htmlspecialchars($venta['customer_name'] ?? 'Cliente General') ?></p>
                            <p><strong>Fecha:</strong> <?= date('d/m/Y h:i A', strtotime($venta['created_at'])) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Estado:</strong> <span
                                    class="badge bg-info text-dark"><?= strtoupper($venta['status']) ?></span></p>
                            <p><strong>Dirección/Nota:</strong> <?= htmlspecialchars($venta['shipping_address']) ?></p>
                            <?php if (!empty($venta['customer_note'])): ?>
                                <p><strong>Nota de la Orden:</strong> <span
                                        class="text-primary fw-bold"><?= htmlspecialchars($venta['customer_note']) ?></span>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-primary text-white">Productos Vendidos</div>
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="text-center">Cant.</th>
                                <th class="text-end">Precio Unit.</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $prod):
                                // Fetch logic type for this specific item (or check if order_items has it, usually we check product table)
                                $stmtL = $db->prepare("SELECT contour_logic_type FROM products WHERE id = ?");
                                $stmtL->execute([$prod['product_id']]);
                                $logic = $stmtL->fetchColumn();
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($prod['name']) ?></div>
                                        <?php if ($logic === 'proportional'): ?>
                                            <span class="badge bg-info text-dark mb-1" style="font-size: 0.6em">PROP.</span>
                                        <?php endif; ?>

                                        <?php
                                        // Obtener modificadores agrupados por sub-item
                                        $mods = $orderManager->getItemModifiers($prod['id']);
                                        $groupedMods = [];
                                        foreach ($mods as $m) {
                                            $groupedMods[$m['sub_item_index']][] = $m;
                                        }

                                        // Recorrer cada unidad del producto
                                        for ($i = 0; $i < $prod['quantity']; $i++):
                                            $currentMods = $groupedMods[$i] ?? [];
                                            $itemNote = "";
                                            $modsList = [];
                                            $isTakeaway = false;

                                            foreach ($currentMods as $m) {
                                                if ($m['modifier_type'] == 'info') {
                                                    if ($m['is_takeaway'])
                                                        $isTakeaway = true;
                                                    if (!empty($m['note']))
                                                        $itemNote = $m['note'];
                                                } else {
                                                    $prefix = match ($m['modifier_type']) {
                                                        'add' => '<i class="fa fa-plus-circle text-success me-1"></i>',
                                                        'remove' => '<i class="fa fa-minus-circle text-danger me-1"></i>',
                                                        'side' => '<i class="fa fa-dot-circle text-info me-1"></i>',
                                                        default => '• '
                                                    };
                                                    $modsList[] = '<div class="ms-3 small">' . $prefix . htmlspecialchars($m['ingredient_name']) . '</div>';
                                                }
                                            }
                                            ?>
                                            <div class="border-start border-3 ms-2 mb-2 ps-2 py-1 bg-light rounded-end"
                                                style="border-color: #dee2e6 !important;">
                                                <div class="small fw-bold text-muted d-flex justify-content-between">
                                                    <span>Ítem #<?= ($i + 1) ?>
                                                        <?= $isTakeaway ? '<span class="badge bg-danger p-1 ms-1" style="font-size:0.6rem">LLEVAR</span>' : '<span class="badge bg-primary p-1 ms-1" style="font-size:0.6rem">MESA</span>' ?></span>
                                                </div>

                                                <?php foreach ($modsList as $ml)
                                                    echo $ml; ?>

                                                <?php if (!empty($itemNote)): ?>
                                                    <div class="ms-3 small text-info fw-bold italic"><i
                                                            class="fa fa-sticky-note me-1"></i> <?= htmlspecialchars($itemNote) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endfor; ?>
                                    </td>
                                    <td class="text-center"><?= $prod['quantity'] ?></td>
                                    <td class="text-end">$<?= number_format($prod['price'], 2) ?></td>
                                    <td class="text-end fw-bold">
                                        $<?= number_format($prod['price'] * $prod['quantity'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3" class="text-end fw-bold">TOTAL VENTA:</td>
                                <td class="text-end fw-bold fs-5 text-warning">
                                    $<?= number_format($venta['total_price'], 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow border-warning">
                <div class="card-header bg-warning text-dark fw-bold">
                    <i class="fa fa-cash-register"></i> Historial de Pagos
                </div>
                <div class="card-body p-0">
                    <?php if (empty($transacciones)): ?>
                        <div class="p-3 text-center text-muted">No hay registros contables.</div>
                    <?php else: ?>
                        <table class="table table-sm table-hover mb-0" style="font-size: 0.9rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Método</th>
                                    <th class="text-end">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transacciones as $t): ?>
                                    <tr class="<?= $t['type'] == 'expense' ? 'table-danger' : '' ?>">
                                        <td>
                                            <?= $t['method_name'] ?>
                                            <?php if ($t['type'] == 'expense'): ?>
                                                <span class="badge bg-danger" style="font-size: 0.6rem;">VUELTO</span>
                                            <?php endif; ?>

                                            <?php if (!empty($t['payment_reference'])): ?>
                                                <br><small class="text-primary fw-bold">Ref:
                                                    <?= htmlspecialchars($t['payment_reference']) ?></small>
                                            <?php endif; ?>

                                            <?php if (!empty($t['sender_name'])): ?>
                                                <br><small class="text-dark">Remitente:
                                                    <?= htmlspecialchars($t['sender_name']) ?></small>
                                            <?php endif; ?>

                                            <br>
                                            <small class="text-muted"><?= $t['currency'] ?> (Tasa:
                                                <?= $t['exchange_rate'] ?>)</small>
                                        </td>
                                        <td class="text-end">
                                            <span
                                                class="fw-bold <?= $t['type'] == 'income' ? 'text-success' : 'text-danger' ?>">
                                                <?= $t['type'] == 'income' ? '+' : '-' ?>
                                                <?= number_format($t['amount'], 2) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-group-divider">
                                <tr>
                                    <td class="text-end">Neto Recibido ($):</td>
                                    <td class="text-end fw-bold">$<?= number_format($totalPagado - $totalVuelto, 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>