<?php
// admin/add_purchase_receipt.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../templates/autoload.php';

session_start();
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

// CAPTURAR ID DE LA ORDEN DESDE LA URL (Si viene del botón de la lista)
$preselectedOrderId = $_GET['order_id'] ?? 0;

// Obtener solo órdenes PENDIENTES
$allOrders = $purchaseOrderManager->getAllPurchaseOrders();
$pendingOrders = [];

foreach ($allOrders as $order) {
    if ($order['status'] !== 'received') {
        $pendingOrders[] = $order;
    }
}

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0"><i class="fa fa-truck-loading"></i> Registrar Recepción</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($pendingOrders)): ?>
                        <div class="alert alert-success text-center">
                            <i class="fa fa-check-circle fa-2x mb-3"></i><br>
                            ¡Todo al día! No hay mercancía pendiente por recibir.
                        </div>
                        <div class="d-grid">
                            <a href="compras.php" class="btn btn-secondary">Volver a Compras</a>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Confirma la llegada de la mercancía al almacén.</p>

                        <form method="post" action="process_purchase_receipt.php">
                            <input type="hidden" name="action" value="add">

                            <div class="mb-4">
                                <label for="purchase_order_id" class="form-label fw-bold">Orden de Compra</label>
                                <select name="purchase_order_id" id="purchase_order_id" class="form-select form-select-lg" required>
                                    <option value="">Seleccione orden...</option>
                                    <?php foreach ($pendingOrders as $order):
                                        $supplier = $supplierManager->getSupplierById($order['supplier_id']);
                                        $supplierName = $supplier['name'] ?? 'Desconocido';

                                        // LOGICA INTELIGENTE: Si el ID coincide con la URL, lo marcamos SELECTED
                                        $isSelected = ($order['id'] == $preselectedOrderId) ? 'selected' : '';
                                    ?>
                                        <option value="<?= $order['id'] ?>" <?= $isSelected ?>>
                                            #<?= $order['id'] ?> - <?= $supplierName ?> (Total: $<?= number_format($order['total_amount'], 2) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="receipt_date" class="form-label fw-bold">Fecha de Recepción Real</label>
                                <input type="date" name="receipt_date" id="receipt_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div class="alert alert-info small border-info">
                                <div class="d-flex">
                                    <i class="fa fa-calculator fa-2x me-3"></i>
                                    <div>
                                        <strong>Acciones Automáticas:</strong><br>
                                        1. Se sumará el stock al inventario.<br>
                                        2. Se recalculará el precio de venta (USD y VES) según el margen de ganancia.
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">Confirmar Entrada de Stock</button>
                                <a href="compras.php" class="btn btn-secondary">Cancelar</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>
