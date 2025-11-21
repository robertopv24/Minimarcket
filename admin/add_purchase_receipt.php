<?php
// add_purchase_receipt.php
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
    <h2>Registrar Recepción de Mercancía</h2>
    <form method="post" action="process_purchase_receipt.php">
        <input type="hidden" name="action" value="add">
        <div class="mb-3">
            <label for="purchase_order_id" class="form-label">Orden de Compra:</label>
            <select name="purchase_order_id" id="purchase_order_id" class="form-select" required>
                <?php foreach ($purchaseOrders as $order): ?>
                    <option value="<?= $order['id'] ?>"><?= $order['id'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="receipt_date" class="form-label">Fecha de Recepción:</label>
            <input type="date" name="receipt_date" id="receipt_date" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success">Registrar Recepción</button>
    </form>
</div>

<?php require_once '../templates/footer.php'; ?>
