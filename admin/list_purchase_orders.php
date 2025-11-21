<?php
// list_purchase_orders.php
require_once '../templates/autoload.php';

session_start();
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$purchaseOrders = $purchaseOrderManager->getAllPurchaseOrders();

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5">
    <h2>Lista de Órdenes de Compra</h2>
    <a href="add_purchase_order.php" class="btn btn-success mb-3">Crear Nueva Orden de Compra</a>
    <?php if ($purchaseOrders): ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Proveedor</th>
                    <th>Fecha de Orden</th>
                    <th>Fecha de Entrega Esperada</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($purchaseOrders as $order): ?>
                    <tr>
                        <td><?= $order['id'] ?></td>
                        <td><?= $supplierManager->getSupplierById($order['supplier_id'])['name'] ?></td>
                        <td><?= $order['order_date'] ?></td>
                        <td><?= $order['expected_delivery_date'] ?></td>
                        <td><?= $order['total_amount'] ?></td>
                        <td><?= $order['status'] ?></td>
                        <td>
                            <a href="edit_purchase_order.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-primary">Editar</a>
                            <a href="delete_purchase_order.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-danger">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No hay órdenes de compra registradas.</p>
    <?php endif; ?>
</div>

<?php require_once '../templates/footer.php'; ?>
