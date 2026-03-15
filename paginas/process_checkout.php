<?php
session_start();
require_once '../templates/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: tienda.php");
    exit;
}

$userId = $_SESSION['user_id'] ?? null;

// 1. Validaciones Iniciales
$sessionId = $cashRegisterManager->hasOpenSession($userId);
if (!$userId || !$sessionId) {
    die("Error: No tienes una caja abierta. <a href='apertura_caja.php'>Abrir Caja</a>");
}

$orderId = $_POST['order_id'] ?: ($_SESSION['pos_editing_order_id'] ?? null);
$consumptionType = $_POST['consumption_type'] ?? 'dine_in';
$deliveryTier = $_POST['delivery_tier'] ?? 'A';
$cartItems = [];

// Siempre usamos el carrito; si hay orden de referencia, actualizaremos esa.
$cartItems = $cartManager->getCart($userId);
if (empty($cartItems) && $orderId) {
    // Fallback: si el carrito está vacío, cargar ítems de la orden
    $cartItems = $orderManager->getOrderItems($orderId);
}

if (empty($cartItems)) {
    die("Error: El carrito o la orden están vacíos. <a href='tienda.php'>Volver</a>");
}

// 1. Manejo de Delivery (Cálculo inicial)
$stmtD = $db->prepare("SELECT id FROM products WHERE name = 'Servicio Delivery' LIMIT 1");
$stmtD->execute();
$dId = $stmtD->fetchColumn();

if ($consumptionType === 'delivery') {
    // Aplicar Etiquetas de Delivery a los ítems del carrito (si es nueva)
    if (!$orderId) {
        foreach ($cartItems as &$item) {
            $item['is_takeaway'] = 1;
            $item['consumption_type'] = 'delivery';
        }
    }

    // Calcular Cargo por Servicio
    $base = floatval($config->get('delivery_base_cost', 0));
    $fee = ($deliveryTier === 'C') ? ($base * 2) : ($deliveryTier === 'B' ? $base : 0);

    if ($fee > 0) {
        // Asegurar Categoría 'DOMICILIO'
        $stmtCat = $db->prepare("SELECT id FROM categories WHERE name = 'DOMICILIO' LIMIT 1");
        $stmtCat->execute();
        $catId = $stmtCat->fetchColumn();
        if (!$catId) {
            $db->prepare("INSERT INTO categories (name, kitchen_station, icon, description) VALUES ('DOMICILIO', 'none', 'fa-truck', 'Gastos de envío')")->execute();
            $catId = $db->lastInsertId();
        }

        // Crear Producto 'Servicio Delivery' si no existe
        if (!$dId) {
            $db->prepare("INSERT INTO products (name, description, price_usd, price_ves, product_type, category_id, stock, is_visible, kitchen_station, created_at) 
                         VALUES ('Servicio Delivery', 'Servicio de entrega a domicilio', 0, 0, 'simple', ?, 9999, 0, '', NOW())")->execute([$catId]);
            $dId = $db->lastInsertId();
        }

        if (!$orderId) {
            // Nueva venta: añadir ítem al array del carrito para totalizar abajo
            $cartItems[] = [
                'product_id' => $dId,
                'quantity' => 1,
                'price' => $fee,
                'unit_price_final' => $fee,
                'consumption_type' => 'delivery',
                'name' => 'Servicio Delivery',
                'product_type' => 'simple',
                'total_price' => $fee
            ];
        }
        // Nota: Si es $orderId, la persistencia se hace después de la limpieza de ítems abajo
    }
}

// 2. Preparar Datos
$alreadyPaid = 0;
if ($orderId) {
    $orderData = $orderManager->getOrderById($orderId);
    $totalOrderAmount = $orderData['total_price'];
    $alreadyPaid = 0; // Se calculará después de la limpieza
} else {
    $totals = $cartManager->calculateTotal($cartItems);
    $totalOrderAmount = $totals['total_usd'];
}
$customerName = $_POST['customer_name'] ?? 'Cliente General';
$address = $_POST['shipping_address'] ?? 'Tienda';
$rate = $config->get('exchange_rate');

// 3. Estructurar Array de Pagos
$rawPayments = $_POST['payments'] ?? [];
$processedPayments = [];

foreach ($rawPayments as $methodId => $amounts) {
    // Asegurar que tratamos montos como array (por los campos dinámicos [])
    if (!is_array($amounts)) {
        $amounts = [$amounts];
    }

    foreach ($amounts as $index => $amount) {
        if ($amount > 0) {
            $stmt = $db->prepare("SELECT currency FROM payment_methods WHERE id = ?");
            $stmt->execute([$methodId]);
            $currency = $stmt->fetchColumn();

            // CAPTURAR DETALLES ADICIONALES (Referencia y Remitente) de la fila correspondiente
            $details = $_POST['payment_details'][$methodId] ?? [];
            $paymentRef = (isset($details['reference']) && is_array($details['reference'])) ? ($details['reference'][$index] ?? null) : null;
            $senderName = (isset($details['sender']) && is_array($details['sender'])) ? ($details['sender'][$index] ?? null) : null;

            $processedPayments[] = [
                'method_id' => $methodId,
                'amount' => $amount,
                'currency' => $currency,
                'payment_reference' => $paymentRef,
                'sender_name' => $senderName
            ];
        }
    }
}

try {
    // --- INICIO TRANSACCIÓN MAESTRA ---
    $db->beginTransaction();

    // B. IDENTIFICACIÓN Y LIMPIEZA DE AJUSTES PREVIOS
    // Si estamos editando, buscamos al cliente ORIGINAL de la orden para devolverle su saldo antes de cualquier cambio.
    // Esto evita que al cambiar de cliente en el POS, el saldo se "traspase" erróneamente de una persona a otra.
    $originalClientId = null;
    $originalEmployeeId = null;
    if ($orderId) {
        $orderInfo = $orderManager->getOrderById($orderId);
        $originalClientId = $orderInfo['client_id'] ?? null;
        $originalEmployeeId = $orderInfo['employee_id'] ?? null;
    }

    if ($orderId > 0 && $originalClientId) {
        $stmtAdjust = $db->prepare("SELECT id, amount, type FROM transactions 
                                    WHERE reference_id = ? AND reference_type = 'order' 
                                    AND (payment_method_id = 7 OR (type = 'expense' AND (description LIKE '%Saldo%' OR reference_type = 'order_credit'))) ");
        $stmtAdjust->execute([$orderId]);
        $adjustments = $stmtAdjust->fetchAll(PDO::FETCH_ASSOC);

        foreach ($adjustments as $adj) {
            if ($adj['type'] === 'income') {
                // Revertir Consumo: El cliente ORIGINAL recupera su saldo a favor
                $db->prepare("UPDATE clients SET current_debt = current_debt - ? WHERE id = ?")->execute([$adj['amount'], $originalClientId]);
            } else {
                // Revertir Saldo a Favor Otorgado: El cliente ORIGINAL pierde ese saldo
                $db->prepare("UPDATE clients SET current_debt = current_debt + ? WHERE id = ?")->execute([$adj['amount'], $originalClientId]);
            }
            $db->prepare("DELETE FROM transactions WHERE id = ?")->execute([$adj['id']]);
        }
    }

    // Identificar Cliente NUEVO (el que está en la sesión o formulario actual)
    $clientId = ($_POST['credit_client_id'] ?? null) ?: (($_POST['pos_client_id'] ?? null) ?: ($_SESSION['pos_client_id'] ?? null));
    $empId = ($_POST['credit_employee_id'] ?? null) ?: (($_POST['pos_employee_id'] ?? null) ?: ($_SESSION['pos_employee_id'] ?? null));

    // AHORA SÍ: Calcular lo pagado REALMENTE antes de esta sesión (Billetes físicos que quedan en la orden)
    if ($orderId) {
        $alreadyPaid = $transactionManager->getTotalPaidByOrder($orderId);
    }

    // A. VALIDAR CRÉDITO/BENEFICIO
    $isCredit = isset($_POST['is_credit']) && $_POST['is_credit'] === '1';

    if ($isCredit) {
        // 1. Validar Autorización
        $adminPass = $_POST['admin_password'] ?? '';
        if (!$userManager->validateAnyAdminPassword($adminPass)) {
            throw new Exception("⛔ Contraseña de Administrador Incorrecta.");
        }

        // 3. Procesar según Tipo
        $creditType = $_POST['credit_type'] ?? ''; // client_credit, employee_credit, benefit

        // 2. Crear o Obtener Orden
        if (!$orderId) {
            // Enriquecer cartItems con nombres de productos si faltan y asignar IDs de crédito
            foreach ($cartItems as &$item) {
                if (empty($item['name'])) {
                    $pData = $productManager->getProductById($item['product_id']);
                    $item['name'] = $pData['name'] ?? 'Producto';
                    $item['short_code'] = $pData['short_code'] ?? '';
                }
                $item['client_id'] = $clientId;
                $item['employee_id'] = $empId;
            }
            unset($item);

            $orderId = $orderManager->createOrder($userId, $cartItems, $address, null, $_POST['delivery_tier'] ?? null, $customerName);
            if (!$orderId)
                throw new Exception("Error al crear la orden.");
            // Inventario solo se descuenta si la orden es NUEVA
            $orderManager->deductStockFromSale($orderId);
        }

        // ACTUALIZAR CABECERA (Por si cambió delivery o nombre durante el checkout)
        if ($orderId) {
            $newItems = $orderManager->getOrderItems($orderId);
            $newTotal = 0;
            foreach ($newItems as $ni) {
                $newTotal += ($ni['price'] * $ni['quantity']);
            }
            $totalOrderAmount = $newTotal;

            $stmtUpd = $db->prepare("UPDATE orders SET total_price = ?, consumption_type = ?, delivery_tier = ?, customer_note = ?, shipping_address = ?, client_id = ?, employee_id = ? WHERE id = ?");
            $stmtUpd->execute([$newTotal, $consumptionType, $deliveryTier, $customerName, $address, $clientId, $empId, $orderId]);
        }

        $notes = "Autorizado por Admin. Ref: " . date('Y-m-d H:i');

        if ($creditType === 'benefit') {
            // Registrar como Gasto de la empresa (Ingreso para la orden para cuadrar ticket)
            $transactionManager->registerTransaction('income', $totalOrderAmount, "Beneficio Empresa (Cortesía)", $userId, 'order', $orderId, 'USD', 7);
            $orderManager->updateOrderStatus($orderId, 'paid');
        } elseif ($creditType === 'client_credit') {
            if (!$clientId) throw new Exception("Falta ID de Cliente para Crédito.");
            $res = $creditManager->registerDebt($orderId, $totalOrderAmount, $clientId, null, null, $notes, false);
            if (strpos($res, 'Error') !== false) throw new Exception($res);
            
            // Registrar Transacción para aparecer en Ticket
            $transactionManager->registerTransaction('income', $totalOrderAmount, "Venta a Crédito Autorizada", $userId, 'order', $orderId, 'USD', 7);
            
            $orderManager->updateOrderStatus($orderId, 'paid');
        } elseif ($creditType === 'employee_credit') {
            if (!$empId) throw new Exception("Falta ID de Empleado para Crédito.");
            $userManager->getUserById($empId);
            $creditManager->registerDebt($orderId, $totalOrderAmount, null, $empId, null, $notes, false);
            
            // Registrar Transacción para aparecer en Ticket
            $transactionManager->registerTransaction('income', $totalOrderAmount, "Venta a Crédito Emp. Autorizada", $userId, 'order', $orderId, 'USD', 7);
            
            $orderManager->updateOrderStatus($orderId, 'paid');
        } else {
            throw new Exception("Tipo de operación inválida.");
        }

        // ...
        $db->commit();
        // Enviar total como pagado hoy
        header("Location: ticket.php?id=" . $orderId . "&print=true&new_paid_usd=" . $totalOrderAmount);
        exit;
    }

    // A. OBTENER O CREAR LA ORDEN
    if (!$orderId) {
        // Vincular cliente/empleado desde sesión si no está en los ítems
        $sessionClientId  = $_SESSION['pos_client_id']  ?? null;
        $sessionEmployeeId = $_SESSION['pos_employee_id'] ?? null;
        foreach ($cartItems as &$item) {
            if (empty($item['client_id']))   $item['client_id']   = $sessionClientId;
            if (empty($item['employee_id'])) $item['employee_id'] = $sessionEmployeeId;
        }
        unset($item);

        $orderId = $orderManager->createOrder($userId, $cartItems, $address, null, $_POST['delivery_tier'] ?? null, $customerName);
        if (!$orderId)
            throw new Exception("Error al crear la orden.");
        $orderManager->updateOrderStatus($orderId, 'preparing');
        // Solo descontamos inventario si la orden es NUEVA
        $orderManager->deductStockFromSale($orderId);
    } else {
        // SEGURIDAD: Si la orden ya está pagada/entregada, no registramos pagos de nuevo
        $existingOrder = $orderManager->getOrderById($orderId);
        if ($existingOrder && $existingOrder['status'] === 'delivered') {
            $db->commit();
            header("Location: ticket.php?id=" . $orderId . "&print=true");
            exit;
        }

        // RECALCULAR TOTAL Y ACTUALIZAR CABECERA
        // Si venimos del flujo de edición (carrito), reemplazamos los ítems de la orden
        $isEditFlow = !empty($_SESSION['pos_editing_order_id']);
        if ($isEditFlow) {
            // Eliminar ítems viejos de la orden
            $db->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$orderId]);
            // Insertar los ítems nuevos (del carrito)
            $stmtItem = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, cost_at_sale, consumption_type) VALUES (?, ?, ?, ?, ?, ?)");
            $productManager2 = new ProductManager($db);
            foreach ($cartItems as $ci) {
                // Saltar ítems huérfanos
                if (empty($ci['product_id'])) continue;
                // EVITAR DUPLICACIÓN: Saltar el ítem de delivery del carrito, ya que se inserta por separado abajo
                if ($dId && $ci['product_id'] == $dId) continue;

                // ESTANDARIZACIÓN DE PRECIO: unit_price_final (Carrito) > price (Manual) > price_usd (Fijo)
                $pr = $ci['unit_price_final'] ?? $ci['price'] ?? $ci['price_usd'] ?? 0;
                $cost = $productManager2->calculateProductCost($ci['product_id']);
                $stmtItem->execute([$orderId, $ci['product_id'], $ci['quantity'], $pr, $cost, $ci['consumption_type'] ?? 'dine_in']);
            }

            // PERSISTENCIA DEL CARGO DE DELIVERY TRAS LA LIMPIEZA (Solo si es flujo edición)
            if ($consumptionType === 'delivery' && isset($fee) && $fee > 0 && $dId) {
                $costD = $productManager2->calculateProductCost($dId);
                $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, cost_at_sale, consumption_type) VALUES (?, ?, 1, ?, ?, 'delivery')")
                   ->execute([$orderId, $dId, $fee, $costD]);
            }
        }

        $newItems = $orderManager->getOrderItems($orderId);
        $newTotal = 0;
        foreach ($newItems as $ni) {
            $newTotal += ($ni['price'] * $ni['quantity']);
        }
        $totalOrderAmount = $newTotal; // Para el registro de transacciones más abajo

        $newStatus = ($consumptionType === 'delivery') ? 'paid' : 'delivered';
        $stmtUpd = $db->prepare("UPDATE orders SET total_price = ?, consumption_type = ?, delivery_tier = ?, customer_note = ?, shipping_address = ?, status = ?, client_id = ?, employee_id = ? WHERE id = ?");
        $stmtUpd->execute([$newTotal, $consumptionType, $deliveryTier, $customerName, $address, $newStatus, $clientId, $empId, $orderId]);
    }

    // C. REGISTRAR PAGOS FÍSICOS Y PROCESAR SALDOS
    // 1. Registrar los ingresos (billetes) recibidos en esta sesión
    $transactionManager->processOrderPayments($orderId, $processedPayments, $userId, $sessionId);

    // 2. Obtener Saldo Actual del Cliente (Tras reversión si fue edición) para el cálculo de vueltos
    $currentClientBalance = 0;
    if ($clientId) {
        $stmtBalance = $db->prepare("SELECT current_debt FROM clients WHERE id = ?");
        $stmtBalance->execute([$clientId]);
        $currentDebt = floatval($stmtBalance->fetchColumn() ?: 0);
        // Saldo a favor es deuda negativa (Ej: Deuda -10 -> Saldo 10)
        $currentClientBalance = max(0, -$currentDebt);
    }

    // 3. Calcular cuánto pagó realmente en USD en ESTA sesión
    $realPaidUsd = 0;
    foreach ($processedPayments as $p) {
        if ($p['currency'] == 'VES')
            $realPaidUsd += ($p['amount'] / $rate);
        else
            $realPaidUsd += $p['amount'];
    }

    // 2. Calcular Vuelto Teórico (Pagos Previos + Pagos Hoy + Saldo Disponible - Total Nueva Orden)
    $totalPhysicalPaidTodayAndBefore = $alreadyPaid + $realPaidUsd;
    $theoreticalChange = ($totalPhysicalPaidTodayAndBefore + $currentClientBalance) - $totalOrderAmount;

    // 3. Manejo Unificado de Diferencia de Pago
    if (abs($theoreticalChange) > 0.005 || ($totalOrderAmount > $totalPhysicalPaidTodayAndBefore)) {
        $changeMethodId = $_POST['change_method_id'] ?? null;
        
        // Determinar si se entrega vuelto físico
        $physicalChangeGiven = 0;
        if ($theoreticalChange > 0.005 && !empty($changeMethodId) && $changeMethodId !== 'store_credit') {
            $physicalChangeGiven = $theoreticalChange;
        }

        // El monto neto que el cliente "consume" de su cuenta (Saldo o Deuda) es:
        // Lo que cuesta la orden + lo que se lleva en efectivo - lo que pagó físicamente.
        $netToChargeToAccount = ($totalOrderAmount + $physicalChangeGiven) - $totalPhysicalPaidTodayAndBefore;

        if (abs($netToChargeToAccount) > 0.005) {
            if ($clientId) {
                if ($netToChargeToAccount > 0) {
                    // CASO: DÉFICIT o RETIRO (Cargar a cuenta)
                    $db->prepare("UPDATE clients SET current_debt = current_debt + ? WHERE id = ?")->execute([$netToChargeToAccount, $clientId]);
                    
                    $desc = ($physicalChangeGiven > 0) ? "Retiro Efectivo de Saldo (Vuelto Orden #$orderId)" : "Consumo de Saldo (Orden #$orderId)";
                    $transactionManager->registerTransaction('income', $netToChargeToAccount, $desc, $userId, 'order', $orderId, 'USD', 7, $sessionId);
                } else {
                    // CASO: EXCEDENTE (Abonar a cuenta / Saldo a Favor)
                    $excess = abs($netToChargeToAccount);
                    $db->prepare("UPDATE clients SET current_debt = current_debt - ? WHERE id = ?")->execute([$excess, $clientId]);
                    $transactionManager->registerTransaction('expense', $excess, "Saldo a favor Orden #$orderId (Excedente)", $userId, 'order', $orderId, 'USD', 7, $sessionId);
                }
            } elseif ($netToChargeToAccount > 0.005) {
                // Bloqueo de seguridad si falta dinero y no hay cliente
                throw new Exception("Error de Caja: Hay un faltante de $" . number_format($netToChargeToAccount, 2) . " pero no hay cliente asignado para registrar deuda.");
            }
        }

        // Registrar egreso de caja si hubo vuelto físico
        if ($physicalChangeGiven > 0.005) {
            $stmtM = $db->prepare("SELECT currency FROM payment_methods WHERE id = ?");
            $stmtM->execute([$changeMethodId]);
            $changeCurrency = $stmtM->fetchColumn();
            $changeAmountNominal = ($changeCurrency == 'VES') ? ($physicalChangeGiven * $rate) : $physicalChangeGiven;

            $transactionManager->registerOrderChange($orderId, $changeAmountNominal, $changeCurrency, $changeMethodId, $userId, $sessionId);
        }
    }

    // D. LIMPIEZA
    $cartManager->emptyCart($userId);

    // Limpiar variables de sesión del POS tras completar la venta
    unset($_SESSION['pos_client_id']);
    unset($_SESSION['pos_client_name']);
    unset($_SESSION['pos_editing_order_id']);

    // 4. Finalizar Transacción y Redirigir
    // El "Pago Real de Hoy" para el ticket es lo pagado físicamente en esta sesión
    $netPaidToday = $realPaidUsd;

    $db->commit();
    header("Location: ticket.php?id=" . $orderId . "&print=true&new_paid_usd=" . max(0, $netPaidToday) . "&session_id=" . $sessionId);
    exit;

} catch (Exception $e) {
    if ($db->inTransaction())
        $db->rollBack();

    echo "<div style='padding:20px; background:#f8d7da; color:#721c24; margin:20px; border:1px solid #f5c6cb;'>";
    echo "<h3>🚫 Error al Procesar</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<a href='checkout.php' style='font-weight:bold;'>Volver</a>";
    echo "</div>";
    exit;
}
?>