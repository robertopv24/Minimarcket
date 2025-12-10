<?php
// delete_purchase_order.php
require_once '../templates/autoload.php';

session_start();
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$purchaseOrderId = $_GET['id'] ?? 0;
$purchaseOrder = $purchaseOrderManager->getPurchaseOrderById($purchaseOrderId);

if (!$purchaseOrder) {
    echo "Orden de compra no encontrada.";
    exit;
}

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5">
    <h2>Eliminar Orden de Compra</h2>
    <p>¿Estás seguro de que quieres eliminar la orden de compra #<?= $purchaseOrder['id'] ?>?</p>
    <form method="post" action="process_purchase_order.php">
        <input type="hidden" name="csrf_token" value="<?= Csrf::getToken() ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= $purchaseOrder['id'] ?>">
        <button type="submit" class="btn btn-danger">Eliminar Orden de Compra</button>
        <a href="list_purchase_orders.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php require_once '../templates/footer.php'; ?>