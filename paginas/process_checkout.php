<?php
session_start();
require_once '../templates/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: tienda.php");
    exit;
}

$userId = $_SESSION['user_id'] ?? null;

// 1. Validar Sesión de Caja
$sessionId = $cashRegisterManager->hasOpenSession($userId);
if (!$userId || !$sessionId) {
    die("Error: No tienes una caja abierta. <a href='apertura_caja.php'>Abrir Caja</a>");
}

// 2. Obtener datos del carrito
$cartItems = $cartManager->getCart($userId);
if (empty($cartItems)) {
    die("Error: El carrito está vacío. <a href='tienda.php'>Volver</a>");
}

$totals = $cartManager->calculateTotal($cartItems);
$totalOrderAmount = $totals['total_usd'];
$customerName = $_POST['customer_name'] ?? 'Cliente General';
$address = $_POST['shipping_address'] ?? 'Tienda';
$rawPayments = $_POST['payments'] ?? [];

// 3. Estructurar Pagos
$processedPayments = [];
$rate = $config->get('exchange_rate');

foreach ($rawPayments as $methodId => $amount) {
    if ($amount > 0) {
        // Consultar moneda del método para ser precisos
        $stmt = $db->prepare("SELECT currency FROM payment_methods WHERE id = ?");
        $stmt->execute([$methodId]);
        $currency = $stmt->fetchColumn();

        $processedPayments[] = [
            'method_id' => $methodId,
            'amount' => $amount,
            'currency' => $currency,
            'rate' => ($currency == 'VES') ? $rate : 1
        ];
    }
}

try {
    $db->beginTransaction();

    // 4. CREAR LA ORDEN (OrderManager)
    // El OrderManager se encarga de copiar todos los detalles granulares (is_takeaway, index)
    // desde el carrito a la orden.
    $orderId = $orderManager->createOrder($userId, $cartItems, $address);

    if (!$orderId) {
        throw new Exception("Error al guardar la orden en base de datos.");
    }

    // Actualizar estado a PAID inmediatamente (Venta de mostrador)
    $orderManager->updateOrderStatus($orderId, 'paid');

    // 5. REGISTRAR PAGOS (TransactionManager)
    $paymentSuccess = $transactionManager->processOrderPayments(
        $orderId,
        $processedPayments,
        $totalOrderAmount,
        $userId,
        $sessionId
    );

    if (!$paymentSuccess) {
        throw new Exception("Error al procesar la transacción financiera.");
    }

    // 6. DESCUENTO DE INVENTARIO INTELIGENTE
    // Aquí ocurre la magia: OrderManager leerá el campo 'is_takeaway' de cada ítem
    // y decidirá si descuenta el empaque o no.
    $orderManager->deductStockFromSale($orderId);

    // 7. LIMPIEZA FINAL
    // Borramos el carrito porque ya se convirtió en orden
    $cartManager->emptyCart($userId);

    $db->commit();

    // 8. REDIRECCIÓN AL TICKET
    // Abrimos el ticket en una pestaña nueva (usando JS en la redirección o target _blank en el link anterior)
    // Aquí redirigimos a una página de éxito que abre el ticket.

    header("Location: ticket.php?id=" . $orderId . "&print=true");
    exit;

  } catch (Exception $e) {
      // Verificar si hay transacción activa antes de hacer rollback
      if ($db->inTransaction()) {
          $db->rollBack();
      }

      // Mostrar error amigable pero técnico
      echo "<div style='padding:20px; font-family:sans-serif; color:#721c24; background-color:#f8d7da; border:1px solid #f5c6cb; margin:20px;'>";
      echo "<h3>🚫 Error al Procesar Venta</h3>";
      echo "<p><strong>Detalle:</strong> " . $e->getMessage() . "</p>";
      echo "<a href='checkout.php' style='font-weight:bold;'>Volver al pago</a>";
      echo "</div>";
      exit;
  }
?>
