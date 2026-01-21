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
    // Validar CSRF
    try {
        Csrf::validateToken();
    } catch (Exception $e) {
        die("Error de seguridad: " . $e->getMessage());
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            try {
                // Recibir datos del formulario
                $supplierId = $_POST['supplier_id'] ?? 0;
                $orderDate = $_POST['order_date'] ?? date('Y-m-d');
                $expectedDate = $_POST['expected_delivery_date'] ?? date('Y-m-d');
                $paymentMethodId = $_POST['payment_method_id'] ?? 0;
                $paymentStatus = $_POST['payment_status'] ?? 'paid'; // paid | pending (credit) | partial
                $items = $_POST['items'] ?? [];
                $userId = $_SESSION['user_id'];

                // Validaciones básicas
                if (empty($items))
                    throw new Exception("La orden debe tener al menos un producto.");

                // Si es pagado, requerimos método de pago
                if ($paymentStatus == 'paid' && $paymentMethodId == 0)
                    throw new Exception("Debes seleccionar un método de pago para compras de contado.");

                // Obtener tasa actual para registros
                $currentRate = $GLOBALS['config']->get('exchange_rate');
                if (!$currentRate)
                    $currentRate = 1.00;

                // 1. CREAR ORDEN DE COMPRA (Inventario)
                // Esto guarda en purchase_orders y purchase_order_items
                $purchaseId = $purchaseOrderManager->createPurchaseOrder($supplierId, $orderDate, $expectedDate, $items, $currentRate);

                if ($purchaseId) {
                    // ACTUALIZAR ESTADO DE PAGO EN LA ORDEN
                    $stmt = $db->prepare("UPDATE purchase_orders SET payment_status = ? WHERE id = ?");
                    $stmt->execute([$paymentStatus, $purchaseId]);

                    // 2. REGISTRAR PAGO / GASTO (Tesorería) - SÓLO SI ES 'PAID'
                    if ($paymentStatus === 'paid') {
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
                    } else {
                        // Es Crédito: No registramos salida de dinero aún.
                        // El sistema maneja esto como deuda por pagar.
                    }

                    // Éxito
                    header('Location: compras.php?msg=success');
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
                header('Location: compras.php');
            } else {
                echo "Error al eliminar.";
            }
            break;

        case 'pay_credit':
            try {
                $purchaseId = $_POST['purchase_id'] ?? 0;
                $paymentMethodId = $_POST['payment_method_id'] ?? 0;
                $userId = $_SESSION['user_id'];

                if ($paymentMethodId == 0)
                    throw new Exception("Debe seleccionar un método de pago.");

                // 1. Obtener datos de la orden
                $purchaseOrder = $purchaseOrderManager->getPurchaseOrderById($purchaseId);
                if (!$purchaseOrder)
                    throw new Exception("Compra no encontrada.");

                if ($purchaseOrder['payment_status'] !== 'pending' && $purchaseOrder['payment_status'] !== 'partial')
                    throw new Exception("Esta orden ya está pagada o no es elegible para pago de deuda.");

                // 2. Calcular montos
                $currentRate = $GLOBALS['config']->get('exchange_rate') ?: 1.00;
                $totalAmountUsd = $purchaseOrder['total_amount'];

                // Averiguar moneda del método de pago
                $stmt = $db->prepare("SELECT currency FROM payment_methods WHERE id = ?");
                $stmt->execute([$paymentMethodId]);
                $currency = $stmt->fetchColumn();

                $amountToRegister = ($currency == 'VES') ? ($totalAmountUsd * $currentRate) : $totalAmountUsd;

                // 3. Registrar Transacción
                $result = $transactionManager->registerPurchasePayment(
                    $purchaseId,
                    $amountToRegister,
                    $currency,
                    $paymentMethodId,
                    $userId
                );

                if ($result) {
                    // 4. Actualizar estado de orden a PAID
                    $stmt = $db->prepare("UPDATE purchase_orders SET payment_status = 'paid' WHERE id = ?");
                    $stmt->execute([$purchaseId]);

                    header("Location: edit_purchase_order.php?id=$purchaseId&msg=paid");
                    exit;
                } else {
                    throw new Exception("Error al registrar el pago en caja.");
                }

            } catch (Exception $e) {
                die("Error al procesar pago de deuda: " . $e->getMessage());
            }
            break;

        default:
            echo "Acción no válida.";
            break;
    }
} else {
    header('Location: compras.php');
}
?>