<?php
// list_purchase_receipts.php
require_once '../templates/autoload.php';

session_start();
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$purchaseReceipts = $purchaseReceiptManager->getAllPurchaseReceipts();

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5">
    <h2>Lista de Recepciones de Mercancía</h2>
    <a href="add_purchase_receipt.php" class="btn btn-success mb-3">Registrar Nueva Recepción</a>
    <?php if ($purchaseReceipts): ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Orden de Compra</th>
                    <th>Fecha de Recepción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($purchaseReceipts as $receipt): ?>
                    <tr>
                        <td><?= $receipt['id'] ?></td>
                        <td><?= $receipt['purchase_order_id'] ?></td>
                        <td><?= $receipt['receipt_date'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No hay recepciones de mercancía registradas.</p>
    <?php endif; ?>
</div>

<?php require_once '../templates/footer.php'; ?>
