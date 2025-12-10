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

// Primera fila tiene todos los datos de la orden
$venta = [
    'id' => $rows[0]['id'],
    'user_id' => $rows[0]['user_id'],
    'customer_name' => $rows[0]['customer_name'],
    'shipping_address' => $rows[0]['shipping_address'],
    'total_price' => $rows[0]['total_usd'], // Renamed from total_usd to total_price for consistency with original code
    'total_ves' => $rows[0]['total_ves'],
    'status' => $rows[0]['status'],
    'created_at' => $rows[0]['created_at'],
    'cashier_name' => $rows[0]['cashier_name']
];

// Construir array de items desde las filas
$items = [];
foreach ($rows as $row) {
    if ($row['item_id']) { // Solo si hay items
        $items[] = [
            'id' => $row['item_id'],
            'product_id' => $row['product_id'],
            'name' => $row['product_name'],
            'quantity' => $row['item_quantity'],
            'price' => $row['item_price'] // Renamed from price_usd to price for consistency with original code
        ];
    }
}

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
                            <?php foreach ($productos as $prod): ?>
                                <tr>
                                    <td><?= htmlspecialchars($prod['name']) ?></td>
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