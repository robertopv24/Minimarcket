<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../templates/autoload.php';

// session_start();
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

$purchaseOrderId = $_GET['id'] ?? 0;
$purchaseOrder = $purchaseOrderManager->getPurchaseOrderById($purchaseOrderId);

if (!$purchaseOrder) {
    echo "Orden de compra no encontrada.";
    exit;
}

$suppliers = $supplierManager->getAllSuppliers();
$products = $productManager->getAllProducts();
$orderItems = $purchaseOrderManager->getPurchaseOrderItems($purchaseOrderId);

// Buscar información financiera
$stmt = $db->prepare("SELECT t.*, pm.name as method_name
                      FROM transactions t
                      JOIN payment_methods pm ON t.payment_method_id = pm.id
                      WHERE t.reference_type = 'purchase' AND t.reference_id = ?");
$stmt->execute([$purchaseOrderId]);
$transaccion = $stmt->fetch(PDO::FETCH_ASSOC);

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📝 Detalle Orden #<?= $purchaseOrder['id'] ?></h2>
        <a href="compras.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Volver</a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">Contenido de la Orden</div>
                <div class="card-body">
                    <form method="post" action="process_purchase_order.php">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= $purchaseOrder['id'] ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Proveedor</label>
                                <select name="supplier_id" class="form-select" <?= $purchaseOrder['status'] == 'received' ? 'disabled' : '' ?>>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?= $supplier['id'] ?>"
                                            <?= $purchaseOrder['supplier_id'] == $supplier['id'] ? 'selected' : '' ?>>
                                            <?= $supplier['name'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fecha Orden</label>
                                <input type="date" name="order_date" class="form-control"
                                    value="<?= $purchaseOrder['order_date'] ?>" <?= $purchaseOrder['status'] == 'received' ? 'disabled' : '' ?>>
                            </div>
                        </div>

                        <h5 class="mt-4">Productos</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th>Cant.</th>
                                        <th>Costo Unit.</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderItems as $item):
                                        // Obtener nombre del producto
                                        $prod = ['name' => 'Producto Eliminado'];
                                        if (!empty($item['product_id'])) {
                                            $prod = $productManager->getProductById((int) $item['product_id']) ?: $prod;
                                        }
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($prod['name']) ?></td>
                                            <td><?= $item['quantity'] ?></td>
                                            <td>$<?= number_format($item['unit_price'], 2) ?></td>
                                            <td class="fw-bold">
                                                $<?= number_format($item['quantity'] * $item['unit_price'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">TOTAL:</td>
                                        <td class="fw-bold text-success">
                                            $<?= number_format($purchaseOrder['total_amount'], 2) ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <?php if ($purchaseOrder['status'] !== 'received'): ?>
                            <div class="alert alert-warning mt-3">
                                <i class="fa fa-info-circle"></i> Para modificar productos, elimina esta orden y crea una
                                nueva (por integridad contable).
                            </div>
                            <button type="submit" class="btn btn-primary">Guardar Cambios (Fechas/Proveedor)</button>
                        <?php else: ?>
                            <div class="alert alert-success mt-3">
                                <i class="fa fa-check-circle"></i> Esta orden ya fue recibida. No se puede editar.
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow border-warning mb-4">
                <div class="card-header bg-warning text-dark fw-bold">
                    <i class="fa fa-file-invoice-dollar"></i> Información de Tesorería
                </div>
                <div class="card-body">
                    <?php if ($transaccion): ?>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Método:</span>
                                <strong><?= $transaccion['method_name'] ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Monto Pagado:</span>
                                <span class="text-danger fw-bold">- <?= number_format($transaccion['amount'], 2) ?>
                                    <?= $transaccion['currency'] ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Tasa usada:</span>
                                <span><?= $transaccion['exchange_rate'] ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Fecha Pago:</span>
                                <span><?= date('d/m/Y', strtotime($transaccion['created_at'])) ?></span>
                            </li>
                        </ul>

                        <?php if ($transaccion['method_name'] === 'Efectivo USD' || $transaccion['method_name'] === 'Efectivo VES'): ?>
                            <div class="alert alert-info mt-3 mb-0 small">
                                <i class="fa fa-vault"></i> Este dinero se descontó automáticamente de la <strong>Caja
                                    Chica</strong>.
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="text-center text-muted py-3">
                            <i class="fa fa-exclamation-circle"></i> No se encontró registro de pago.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow text-center">
                <div class="card-body">
                    <h5 class="card-title">Estado Actual</h5>
                    <?php if ($purchaseOrder['status'] == 'received'): ?>
                        <span class="badge bg-success fs-5 w-100">RECIBIDO</span>
                        <p class="mt-2 small text-muted">El stock ya fue sumado al inventario.</p>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark fs-5 w-100">PENDIENTE</span>
                        <p class="mt-2 small text-muted">Falta confirmar la recepción de mercancía.</p>
                        <a href="add_purchase_receipt.php" class="btn btn-success w-100">Recibir Mercancía Ahora</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>