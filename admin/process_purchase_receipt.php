<?php
// process_purchase_receipt.php
require_once '../templates/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $purchaseOrderId = $_POST['purchase_order_id'] ?? 0;
            $receiptDate = $_POST['receipt_date'] ?? '';

            if ($purchaseReceiptManager->createPurchaseReceipt($purchaseOrderId, $receiptDate)) {
                header('Location: list_purchase_receipts.php');
                exit;
            } else {
                echo "Error al registrar la recepción de mercancía.";
            }
            break;
        default:
            echo "Acción no válida.";
            break;
    }
} else {
    echo "Acceso no permitido.";
}
?>
