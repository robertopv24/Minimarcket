<?php
// process_purchase_order.php
require_once '../templates/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $supplierId = $_POST['supplier_id'] ?? 0;
            $orderDate = $_POST['order_date'] ?? '';
            $expectedDeliveryDate = $_POST['expected_delivery_date'] ?? '';
            $items = $_POST['items'] ?? [];

            if ($purchaseOrderManager->createPurchaseOrder($supplierId, $orderDate, $expectedDeliveryDate, $items)) {
                header('Location: list_purchase_orders.php');
                exit;
            } else {
                echo "Error al crear la orden de compra.";
            }
            break;
        case 'edit':
            $id = $_POST['id'] ?? 0;
            $supplierId = $_POST['supplier_id'] ?? 0;
            $orderDate = $_POST['order_date'] ?? '';
            $expectedDeliveryDate = $_POST['expected_delivery_date'] ?? '';
            $items = $_POST['items'] ?? [];

            if ($purchaseOrderManager->updatePurchaseOrder($id, $supplierId, $orderDate, $expectedDeliveryDate, $items)) {
                header('Location: list_purchase_orders.php');
                exit;
            } else {
                echo "Error al actualizar la orden de compra.";
            }
            break;
        case 'delete':
            $id = $_POST['id'] ?? 0;
            if ($purchaseOrderManager->deletePurchaseOrder($id)) {
                header('Location: list_purchase_orders.php');
                exit;
            } else {
                echo "Error al eliminar la orden de compra.";
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
