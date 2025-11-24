<?php
// admin/process_purchase_order.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../templates/autoload.php';

session_start();
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            try {
                // Recibir datos del formulario
                $supplierId = $_POST['supplier_id'] ?? 0;
                $orderDate = $_POST['order_date'] ?? date('Y-m-d');
                $expectedDate = $_POST['expected_delivery_date'] ?? date('Y-m-d');
                $paymentMethodId = $_POST['payment_method_id'] ?? 0;
                $items = $_POST['items'] ?? [];
                $userId = $_SESSION['user_id'];

                // Validaciones básicas
                if (empty($items)) throw new Exception("La orden debe tener al menos un producto.");
                if ($paymentMethodId == 0) throw new Exception("Debes seleccionar un método de pago.");

                // Obtener tasa actual para registros
                $currentRate = $GLOBALS['config']->get('exchange_rate');
                if (!$currentRate) $currentRate = 1.00;

                // 1. CREAR ORDEN DE COMPRA (Inventario)
                // Esto guarda en purchase_orders y purchase_order_items
                $purchaseId = $purchaseOrderManager->createPurchaseOrder($supplierId, $orderDate, $expectedDate, $items, $currentRate);

                if ($purchaseId) {
                    // 2. REGISTRAR PAGO / GASTO (Tesorería)

                    // Obtenemos el total calculado por el Manager para ser precisos
                    $purchaseData = $purchaseOrderManager->getPurchaseOrderById($purchaseId);
                    $totalAmountUsd = $purchaseData['total_amount'];

                    // Averiguar moneda del método de pago seleccionado
                    // (Necesario para saber si descontamos USD o VES de la bóveda)
                    $stmt = $db->prepare("SELECT currency FROM payment_methods WHERE id = ?");
                    $stmt->execute([$paymentMethodId]);
                    $currency = $stmt->fetchColumn();

                    // Calcular monto en la moneda de pago
                    // Si la moneda es VES, convertimos el total USD a VES usando la tasa actual
                    $amountToRegister = ($currency == 'VES') ? ($totalAmountUsd * $currentRate) : $totalAmountUsd;

                    // Llamamos al TransactionManager para que registre el egreso
                    // Si es efectivo, descontará de la Caja Chica automáticamente
                    $result = $transactionManager->registerPurchasePayment(
                        $purchaseId,
                        $amountToRegister,
                        $currency,
                        $paymentMethodId,
                        $userId
                    );

                    if ($result === false) {
                        // Si falla el pago (ej: saldo insuficiente), deberíamos cancelar la orden para mantener consistencia
                        $purchaseOrderManager->deletePurchaseOrder($purchaseId);
                        throw new Exception("Error financiero: No se pudo registrar el pago (¿Saldo insuficiente en Caja Chica?). La orden fue cancelada.");
                    }

                    // Éxito
                    header('Location: list_purchase_orders.php?msg=success');
                    exit;
                } else {
                    throw new Exception("Error al guardar la orden en base de datos.");
                }

            } catch (Exception $e) {
                // Mostrar error amigable
                echo '<div style="padding: 20px; color: red; font-family: sans-serif;">
                        <h2>Error al Procesar Compra</h2>
                        <p>' . $e->getMessage() . '</p>
                        <a href="add_purchase_order.php">Volver al formulario</a>
                      </div>';
            }
            break;

        case 'delete':
            // ... Lógica de eliminar existente ...
            $id = $_POST['id'] ?? 0;
            if ($purchaseOrderManager->deletePurchaseOrder($id)) {
                header('Location: list_purchase_orders.php');
            } else {
                echo "Error al eliminar.";
            }
            break;

        default:
            echo "Acción no válida.";
            break;
    }
} else {
    header('Location: list_purchase_orders.php');
}
?>
