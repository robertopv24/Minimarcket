<?php
// admin/process_purchase_order.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../templates/autoload.php';

use Minimarcket\Core\Container;
use Minimarcket\Modules\User\Services\UserService;
use Minimarcket\Modules\SupplyChain\Services\PurchaseOrderService;
use Minimarcket\Modules\Finance\Services\TransactionService;
use Minimarcket\Core\Config\ConfigService;
use Minimarcket\Core\Security\CsrfToken;

$container = Container::getInstance();
$userService = $container->get(UserService::class);
$purchaseOrderService = $container->get(PurchaseOrderService::class);
$transactionService = $container->get(TransactionService::class);
$configService = $container->get(ConfigService::class);
$csrfToken = $container->get(CsrfToken::class);

// session_start();
if (!isset($_SESSION['user_id']) || $userService->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    try {
        $csrfToken->validateToken();
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
                $items = $_POST['items'] ?? [];
                $userId = $_SESSION['user_id'];

                // Validaciones básicas
                if (empty($items))
                    throw new Exception("La orden debe tener al menos un producto.");
                if ($paymentMethodId == 0)
                    throw new Exception("Debes seleccionar un método de pago.");

                // Obtener tasa actual para registros
                $currentRate = $configService->get('exchange_rate');
                if (!$currentRate)
                    $currentRate = 1.00;

                // 1. CREAR ORDEN DE COMPRA (Inventario)
                // Esto guarda en purchase_orders y purchase_order_items
                $purchaseId = $purchaseOrderService->createPurchaseOrder($supplierId, $orderDate, $expectedDate, $items, $currentRate);

                if ($purchaseId) {
                    // 2. REGISTRAR PAGO / GASTO (Tesorería)

                    // Obtenemos el total calculado por el Service para ser precisos
                    $purchaseData = $purchaseOrderService->getPurchaseOrderById($purchaseId);
                    $totalAmountUsd = $purchaseData['total_amount'];

                    // Averiguar moneda del método de pago seleccionado
                    // Necesitamos obtener el método para saber su moneda
                    $methods = $transactionService->getPaymentMethods();
                    $currency = 'USD'; // Default
                    foreach ($methods as $method) {
                        if ($method['id'] == $paymentMethodId) {
                            $currency = $method['currency'];
                            break;
                        }
                    }

                    // Calcular monto en la moneda de pago
                    $amountToRegister = ($currency == 'VES') ? ($totalAmountUsd * $currentRate) : $totalAmountUsd;

                    // Llamamos al TransactionService para que registre el egreso
                    $result = $transactionService->registerPurchasePayment(
                        $purchaseId,
                        $amountToRegister,
                        $currency,
                        $paymentMethodId,
                        $userId
                    );

                    if ($result === false) {
                        // Si falla el pago, cancelar la orden
                        $purchaseOrderService->deletePurchaseOrder($purchaseId);
                        throw new Exception("Error financiero: No se pudo registrar el pago (¿Saldo insuficiente en Bóveda?). La orden fue cancelada.");
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
            $id = $_POST['id'] ?? 0;
            // Para delete, normalmente se usa GET en el botón, pero si se cambia a POST por seguridad:
            // $purchaseOrderService->deletePurchaseOrder($id);
            // Pero el botón en compras.php es un GET link.
            // Para mantener consistencia con el archivo original, mantienes esto por si acaso se llama vía POST.
            // Pero el link es: delete_purchase_order.php?id=... (que es otro archivo).
            // Este archivo parece manejar solo el POST del formulario de add.
            // El case 'delete' podría ser un residuo, pero lo mantendremos safe.
            if ($purchaseOrderService->deletePurchaseOrder($id)) {
                header('Location: compras.php');
            } else {
                echo "Error al eliminar.";
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