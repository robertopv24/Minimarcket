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

                  // [NUEVO FASE 2] Capturamos la tasa actual del sistema
                  // Usamos la instancia global $config que viene del autoload
                  $currentRate = $GLOBALS['config']->get('exchange_rate');

                  // Si por alguna razón falla, usamos 1 como fallback para evitar error SQL
                  if (!$currentRate) $currentRate = 1.00;

                  // Pasamos $currentRate como 5to parámetro
                  if ($purchaseOrderManager->createPurchaseOrder($supplierId, $orderDate, $expectedDeliveryDate, $items, $currentRate)) {
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
