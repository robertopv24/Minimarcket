<?php

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/ProductManager.php';

class OrderManager
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * 1. CREAR ORDEN (CON PROTECCIÓN DE TRANSACCIÓN)
     * Transfiere la configuración exacta a la orden, respetando transacciones externas.
     */
    public function createOrder($user_id, $items, $shipping_address, $shipping_method = null, $delivery_tier = null, $customerNote = null)
    {
        // Detectar si ya estamos dentro de una transacción (ej: desde process_checkout.php)
        $inTransaction = $this->db->inTransaction();

        try {
            // Solo iniciamos transacción si NO hay una activa
            if (!$inTransaction) {
                $this->db->beginTransaction();
            }

            // Calcular el total de la orden
            $total_price = 0;
            foreach ($items as $item) {
                $price = $item['unit_price_final'] ?? $item['price'];
                $total_price += $price * $item['quantity'];
            }

            // Obtener tasa de cambio actual
            $exchange_rate = isset($GLOBALS['config']) ? $GLOBALS['config']->get('exchange_rate') : 1.0000;

            // Insertar la cabecera de la orden
            $sql = "INSERT INTO orders (user_id, client_id, employee_id, total_price, exchange_rate, shipping_address, customer_note, shipping_method, consumption_type, delivery_tier, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
            $stmt = $this->db->prepare($sql);

            // Determinar si es cliente o empleado
            $clientId = $items[0]['client_id'] ?? null;
            $empId = $items[0]['employee_id'] ?? null;

            $stmt->execute([
                $user_id,
                $clientId,
                $empId,
                $total_price,
                $exchange_rate,
                $shipping_address,
                $customerNote,
                $shipping_method,
                $items[0]['consumption_type'] ?? 'dine_in',
                $delivery_tier
            ]);
            $order_id = $this->db->lastInsertId();

            // Preparar consultas para Ítems (Agregamos cost_at_sale)
            $stmtItem = $this->db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, cost_at_sale, consumption_type) VALUES (?, ?, ?, ?, ?, ?)");

            // SQL PARA COPIAR MODIFICADORES (CORREGIDO: Column Count Match)
            // Seleccionamos '?' como primer valor para inyectar el order_item_id
            $sqlCopyMods = "INSERT INTO order_item_modifiers
                            (order_item_id, modifier_type, component_id, component_type, quantity_adjustment, price_adjustment_usd, note, sub_item_index, is_takeaway)
                            SELECT ?, modifier_type, component_id, component_type, quantity_adjustment, price_adjustment, note, sub_item_index, is_takeaway
                            FROM cart_item_modifiers
                            WHERE cart_id = ?";

            $stmtCopy = $this->db->prepare($sqlCopyMods);
            $productManager = new ProductManager($this->db);

            foreach ($items as $item) {
                $price = $item['unit_price_final'] ?? $item['price'];
                $cost_at_sale = $productManager->calculateProductCost($item['product_id']);

                // El tipo global se puede dejar como dine_in, la verdad granular está en los modificadores
                $cType = $item['consumption_type'] ?? 'dine_in';

                // Insertar Item
                $stmtItem->execute([$order_id, $item['product_id'], $item['quantity'], $price, $cost_at_sale, $cType]);
                $order_item_id = $this->db->lastInsertId();

                // Copiar sus modificadores si existen en el carrito
                if (isset($item['id'])) {
                    // Pasamos el ID del nuevo item de orden Y el ID del carrito original
                    $stmtCopy->execute([$order_item_id, $item['id']]);
                }
            }

            // Solo hacemos commit si NOSOTROS iniciamos la transacción
            if (!$inTransaction) {
                $this->db->commit();
            }

            // REGISTRO DE TRACABILIDAD INICIAL POR ESTACIÓN
            $stations = [];
            foreach ($items as $item) {
                $pInfo = $productManager->getProductById($item['product_id']);
                if ($pInfo && !empty($pInfo['kitchen_station'])) {
                    $stations[] = $pInfo['kitchen_station'];
                }
                // Si es combo, revisar estaciones de componentes
                if (($item['product_type'] ?? '') === 'compound') {
                    $comps = $productManager->getProductComponents($item['product_id']);
                    foreach ($comps as $c) {
                        $st = $c['category_station'] ?? $c['kitchen_station'] ?? '';
                        if ($st)
                            $stations[] = $st;
                    }
                }
            }
            $stations = array_unique($stations);
            foreach ($stations as $st) {
                $this->logStatusMilestone($order_id, $st, 'received');
            }

            return $order_id;

        } catch (PDOException $e) {
            // Solo hacemos rollback si NOSOTROS iniciamos la transacción
            if (!$inTransaction) {
                $this->db->rollBack();
            }
            // Re-lanzamos la excepción para que el padre (process_checkout) maneje el error global
            throw $e;
        }
    }

    /**
     * 1b. AÑADIR ÍTEMS A UNA ORDEN EXISTENTE
     * Útil para consolidar pedidos de la misma mesa.
     */
    public function addItemsToOrder($orderId, $items)
    {
        $inTransaction = $this->db->inTransaction();
        try {
            if (!$inTransaction)
                $this->db->beginTransaction();

            $newItemsTotal = 0;
            $exchange_rate = isset($GLOBALS['config']) ? $GLOBALS['config']->get('exchange_rate') : 1.0000;

            $stmtItem = $this->db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, cost_at_sale, consumption_type) VALUES (?, ?, ?, ?, ?, ?)");
            $sqlCopyMods = "INSERT INTO order_item_modifiers
                            (order_item_id, modifier_type, component_id, component_type, quantity_adjustment, price_adjustment_usd, note, sub_item_index, is_takeaway)
                            SELECT ?, modifier_type, component_id, component_type, quantity_adjustment, price_adjustment, note, sub_item_index, is_takeaway
                            FROM cart_item_modifiers
                            WHERE cart_id = ?";
            $stmtCopy = $this->db->prepare($sqlCopyMods);
            $productManager = new ProductManager($this->db);

            foreach ($items as $item) {
                $price = $item['unit_price_final'] ?? $item['price'];
                $cost_at_sale = $productManager->calculateProductCost($item['product_id']);
                $cType = $item['consumption_type'] ?? 'dine_in';

                $stmtItem->execute([$orderId, $item['product_id'], $item['quantity'], $price, $cost_at_sale, $cType]);
                $order_item_id = $this->db->lastInsertId();

                if (isset($item['id'])) {
                    $stmtCopy->execute([$order_item_id, $item['id']]);
                }
                $newItemsTotal += $price * $item['quantity'];
            }

            // Actualizar total de la orden
            $stmtUpdate = $this->db->prepare("UPDATE orders SET total_price = total_price + ? WHERE id = ?");
            $stmtUpdate->execute([$newItemsTotal, $orderId]);

            if (!$inTransaction)
                $this->db->commit();
            return true;
        } catch (Exception $e) {
            if (!$inTransaction)
                $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * 2. DESCUENTO DE INVENTARIO INTELIGENTE (RECURSIVO)
     * Maneja:
     * - Productos Simples (Link Directo o Tabla Ventas)
     * - Productos Preparados (Recetas)
     * - Combos (Recursividad a hijos)
     * - Extras Globales (Salsas, etc)
     */
    public function deductStockFromSale($orderId)
    {
        $this->handleStockChange($orderId, false);
    }

    /**
     * 2b. REVERSIÓN DE STOCK (NUEVO)
     * Devuelve los productos al inventario al cancelar una orden.
     */
    public function revertStockFromSale($orderId)
    {
        $this->handleStockChange($orderId, true);
    }

    private function handleStockChange($orderId, $isReversion = false)
    {
        $sql = "SELECT oi.id as order_item_id, oi.product_id, oi.quantity
                FROM order_items oi
                WHERE oi.order_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            // 1. Procesar el producto principal
            $this->processProductDeduction($item['product_id'], $item['quantity'], $item['order_item_id'], null, $isReversion);

            // 2. Procesar el empaque (Solo si es Para Llevar o Delivery)
            $cType = $item['consumption_type'] ?? 'dine_in';
            if ($cType === 'takeaway' || $cType === 'delivery') {
                $this->processPackagingDeduction($item['product_id'], $item['quantity'], $isReversion);
            }
        }
    }

    /**
     * @param int $targetIndex Índice del sub-item en el combo (para extras granulares)
     * @param bool $isReversion Si es true, suma stock en lugar de restar
     */
    private function processProductDeduction($productId, $qty, $orderItemId = null, $targetIndex = null, $isReversion = false)
    {
        // A. Obtener datos del producto (Tipo y Link)
        $stmt = $this->db->prepare("SELECT product_type, linked_manufactured_id, stock FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product)
            return;

        // B. Decisión de Descuento Base
        $stockDeducted = false;

        // CASO 1: Enlace Directo (Prioridad Máxima)
        // Ej: Postre de Vaso, Refresco (si decidimos enlazarlo)
        if (!empty($product['linked_manufactured_id'])) {
            $this->updateStock('manufactured_products', $product['linked_manufactured_id'], $qty, $isReversion);
            $stockDeducted = true;
        }
        // CASO 2: Producto Simple SIN Enlace
        // Ej: Lata de Refresco (Reventa pura)
        elseif ($product['product_type'] === 'simple') {
            // NUEVO: Verificar si es un producto "Virtual" (tiene componentes aunque sea simple)
            $stmtC = $this->db->prepare("SELECT COUNT(*) FROM product_components WHERE product_id = ?");
            $stmtC->execute([$productId]);
            $hasComponents = intval($stmtC->fetchColumn()) > 0;

            if (!$hasComponents) {
                $this->updateStock('products', $productId, $qty, $isReversion);
                $stockDeducted = true;
            } else {
                // Es un producto virtual/contenedor. No descontamos stock físico,
                // dejamos que la lógica de Explosión de Componentes (Caso 3) actúe.
                $stockDeducted = false;
            }
        }
        // CASO 3: Preparado o Compuesto (Pizza, Combo)
        // NO DESCONTAMOS stock de 'products' (evita negativos).
        // Se explota la receta/componentes.

        // C. Explosión de Componentes (Recetas y Combos)
        $hasRecursed = false;
        if (!$stockDeducted) {

            // NUEVA LOGICA: Verificar si este item tiene una receta personalizada por ser Acompañante
            $customComponents = null;
            if ($orderItemId) {
                // Buscamos si hay un modifier 'companion_origin'
                $stmtCheck = $this->db->prepare("SELECT component_id FROM order_item_modifiers WHERE order_item_id = ? AND modifier_type = 'companion_origin'");
                $stmtCheck->execute([$orderItemId]);
                $originCompanionId = $stmtCheck->fetchColumn();

                if ($originCompanionId) {
                    // Instanciamos ProductManager para buscar la receta exacta
                    $pm = new ProductManager($this->db);
                    $recipeData = $pm->getCompanionRecipe($originCompanionId);

                    // Si es custom, usamos esa. Si es original, $customComponents sigue null y usa lógica estándar
                    if ($recipeData['type'] === 'custom') {
                        $customComponents = $recipeData['components'];
                        // OJO: La estructura retornada por getCompanionRecipe ya viene normalizada
                    }
                }
            }

            if ($customComponents !== null) {
                // Usar Receta Personalizada
                $components = $customComponents;
            } else {
                // Usar Receta Estándar
                $stmtComp = $this->db->prepare("SELECT component_type, component_id, quantity FROM product_components WHERE product_id = ?");
                $stmtComp->execute([$productId]);
                $components = $stmtComp->fetchAll(PDO::FETCH_ASSOC);
            }

            $subItemIndex = 0; // Contador para saber qué hijo del combo somos

            foreach ($components as $comp) {
                // Cantidad total requerida de este componente
                $totalNeeded = $comp['quantity'] * $qty;

                if ($comp['component_type'] == 'product') {
                    $hasRecursed = true;
                    // RECURSIVIDAD: Si el componente es otro producto (vender Combo -> Pizza)
                    $baseOffset = $targetIndex !== null ? $targetIndex : 0;

                    for ($i = 0; $i < $totalNeeded; $i++) {
                        $currentSubLoopIndex = $subItemIndex + $i;
                        // Llamada recursiva (Descontar 1 unidad del hijo)
                        $this->processProductDeduction($comp['component_id'], 1, $orderItemId, $currentSubLoopIndex, $isReversion);
                    }

                    $subItemIndex += $totalNeeded;

                } elseif ($comp['component_type'] == 'raw') {
                    // Ingrediente directo (Harina)
                    $this->updateStock('raw_materials', $comp['component_id'], $totalNeeded, $isReversion);
                } elseif ($comp['component_type'] == 'manufactured') {
                    // Sub-producto (Salsa Napolitana)
                    $this->updateStock('manufactured_products', $comp['component_id'], $totalNeeded, $isReversion);
                }
            }
        }

        // D. Procesar Extras (Modificadores)
        // Solo si estamos en el contexto de una orden (tenemos ID)
        // Y NO hemos delegado la responsabilidad a hijos (evita duplicación en Combos)
        if ($orderItemId && (!$hasRecursed || $stockDeducted)) {
            // Si es la llamada RAÍZ ($targetIndex === null) y qty > 1, procesamos todos los índices
            if ($targetIndex === null && $qty > 1) {
                for ($idx = 0; $idx < $qty; $idx++) {
                    $this->processExtras($orderItemId, $idx, $productId, $isReversion);
                }
            } else {
                $this->processExtras($orderItemId, $targetIndex, $productId, $isReversion);
            }
        }
    }

    private function processExtras($orderItemId, $targetIndex, $productId, $isReversion = false)
    {
        // 1. Obtener configuración del PRODUCTO ACTUAL (para saber si aplicamos lógica proporcional)
        $stmtProd = $this->db->prepare("SELECT contour_logic_type FROM products WHERE id = ?");
        $stmtProd->execute([$productId]);
        $prodConfig = $stmtProd->fetch(PDO::FETCH_ASSOC);
        $isProportional = ($prodConfig && ($prodConfig['contour_logic_type'] === 'proportional'));

        // 2. Buscar modificadores 'add' y 'side' para este índice
        $sql = "SELECT modifier_type, component_id, component_type, quantity_adjustment 
                FROM order_item_modifiers 
                WHERE order_item_id = ? AND modifier_type IN ('add', 'side')";

        $params = [$orderItemId];

        if ($targetIndex !== null) {
            $sql .= " AND sub_item_index = ?";
            $params[] = $targetIndex;
        } else {
            $sql .= " AND sub_item_index = 0";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $modifiers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. CALCULAR DIVISOR (Si es proporcional)
        $divisor = 1;
        if ($isProportional) {
            $totalSides = 0;
            foreach ($modifiers as $mod) {
                if ($mod['modifier_type'] === 'side') {
                    $totalSides++;
                }
            }
            if ($totalSides > 0) {
                $divisor = $totalSides;
            }
        }

        foreach ($modifiers as $mod) {
            $qty = floatval($mod['quantity_adjustment']) > 0 ? $mod['quantity_adjustment'] : 1;

            // Si es un CONTORNO y la lógica es PROPORCIONAL, dividimos la cantidad base
            if ($isProportional && $mod['modifier_type'] === 'side') {
                $qty = $qty / $divisor;
            }

            $type = $mod['component_type'] ?? 'raw';
            $id = $mod['component_id'];

            if ($type == 'raw') {
                $this->updateStock('raw_materials', $id, $qty, $isReversion);
            } elseif ($type == 'manufactured') {
                $this->updateStock('manufactured_products', $id, $qty, $isReversion);
            } elseif ($type == 'product') {
                // RECURSIVIDAD: Si el contorno es un producto (ej: un combo que deja elegir otro producto)
                $this->processProductDeduction($id, $qty, null, null, $isReversion);
            }
        }
    }

    private function processPackagingDeduction($productId, $qty, $isReversion = false)
    {
        $sql = "SELECT raw_material_id, quantity FROM product_packaging WHERE product_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId]);
        $packaging = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($packaging as $pack) {
            $totalNeeded = $pack['quantity'] * $qty;
            $this->updateStock('raw_materials', $pack['raw_material_id'], $totalNeeded, $isReversion);
        }
    }

    private function updateStock($table, $id, $qty, $isReversion = false)
    {
        $field = ($table == 'raw_materials') ? 'stock_quantity' : 'stock';

        if ($isReversion) {
            // En reversión simplemente sumamos, no hace falta check de negativo
            $sql = "UPDATE {$table} SET {$field} = {$field} + ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$qty, $id]);
        } else {
            // ATOMIC CHECK: Only update if stock >= qty
            $sql = "UPDATE {$table} SET {$field} = {$field} - ? WHERE id = ? AND {$field} >= ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$qty, $id, $qty]);

            if ($stmt->rowCount() === 0) {
                // Rollback is handled by the parent try-catch in createOrder due to this exception
                throw new Exception("Stock insuficiente en {$table} (ID: {$id}) para cubrir la demanda.");
            }
        }
    }

    public function updateOrderStatus($id, $status, $tracking_number = null)
    {
        $stmt = $this->db->prepare("UPDATE orders SET status = ?, tracking_number = ?, updated_at = NOW() WHERE id = ?");
        $res = $stmt->execute([$status, $tracking_number, $id]);

        // Autoregistro de hitos globales
        if (in_array($status, ['preparing', 'ready', 'delivered'])) {
            $this->logStatusMilestone($id, 'system', $status);

            // Si entregamos, nos aseguramos que todas las estaciones queden como 'ready' en el log
            if ($status === 'delivered') {
                $items = $this->getOrderItems($id);
                $stations = [];
                foreach ($items as $item) {
                    $pInfo = (new ProductManager($this->db))->getProductById($item['product_id']);
                    if ($pInfo && !empty($pInfo['kitchen_station']))
                        $stations[] = $pInfo['kitchen_station'];
                }
                $stations = array_unique($stations);

                foreach ($stations as $st) {
                    // Verificar si ya tiene un 'ready'
                    $check = $this->db->prepare("SELECT id FROM order_time_log WHERE order_id = ? AND station = ? AND event_type = 'ready'");
                    $check->execute([$id, $st]);
                    if (!$check->fetch()) {
                        $this->logStatusMilestone($id, $st, 'ready');
                    }
                }
            }
        }

        return $res;
    }

    public function getOrderById($id)
    {
        $sql = "SELECT orders.*, users.name AS customer_name FROM orders JOIN users ON orders.user_id = users.id WHERE orders.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getOrderItems($order_id)
    {
        // CORRECCIÓN: Agregamos p.product_type al SELECT
        $stmt = $this->db->prepare("
                SELECT oi.*, p.name, p.price_usd, p.product_type, p.short_code
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE order_id = ?
            ");
        $stmt->execute([$order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getItemModifiers($orderItemId)
    {
        $sql = "SELECT m.*, 
                    CASE 
                        WHEN m.component_type = 'raw' OR m.component_type IS NULL THEN rm.name
                        WHEN m.component_type = 'manufactured' THEN mp.name
                        WHEN m.component_type = 'product' THEN p.name
                    END as ingredient_name,
                    CASE 
                        WHEN m.component_type = 'raw' OR m.component_type IS NULL THEN rm.short_code
                        WHEN m.component_type = 'manufactured' THEN mp.short_code
                        WHEN m.component_type = 'product' THEN p.short_code
                    END as short_code
                FROM order_item_modifiers m
                LEFT JOIN raw_materials rm ON m.component_id = rm.id AND (m.component_type = 'raw' OR m.component_type IS NULL)
                LEFT JOIN manufactured_products mp ON m.component_id = mp.id AND m.component_type = 'manufactured'
                LEFT JOIN products p ON m.component_id = p.id AND m.component_type = 'product'
                WHERE m.order_item_id = ?
                ORDER BY m.sub_item_index ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderItemId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Funciones auxiliares para reportes y compatibilidad
    public function getOrdersBySearchAndFilter($search = '', $filter = '')
    {
        $sql = "SELECT orders.*, users.name AS customer_name FROM orders JOIN users ON orders.user_id = users.id WHERE 1";
        if (!empty($search)) {
            $search = "%" . $search . "%";
            $sql .= " AND (orders.id LIKE ? OR users.name LIKE ?)";
        }
        if (!empty($filter)) {
            $sql .= " AND orders.status = :status";
        }
        $stmt = $this->db->prepare($sql);
        if (!empty($search)) {
            $stmt->bindValue(1, $search);
            $stmt->bindValue(2, $search);
        }
        if (!empty($filter)) {
            $stmt->bindValue(':status', $filter);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalVentas()
    {
        $query = "SELECT SUM(total_price) AS total_ventas FROM orders WHERE status = 'delivered' OR status = 'paid'";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total_ventas'] ?? 0;
    }

    public function countOrdersByStatus($status)
    {
        $query = "SELECT COUNT(*) AS total FROM orders WHERE status = :status";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':status', $status);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    }

    public function getTotalProductosVendidos()
    {
        $query = "SELECT SUM(quantity) AS total_productos FROM order_items";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total_productos'] ?? 0;
    }

    public function getUltimosPedidos($limit = 10)
    {
        $query = "SELECT o.id, o.user_id, u.name, o.total_price, o.status, o.created_at FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT :limit";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalVentasAnio()
    {
        $sql = "SELECT SUM(total_price) as total FROM orders WHERE YEAR(created_at) = YEAR(CURRENT_DATE()) AND (status = 'paid' OR status = 'delivered')";
        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    }

    public function getTotalVentasMes()
    {
        $query = "SELECT SUM(total_price) AS total FROM orders WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())";
        $stmt = $this->db->query($query);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    }

    public function getTotalVentasSemana()
    {
        $query = "SELECT SUM(total_price) AS total FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $stmt = $this->db->query($query);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    }

    public function getTotalVentasDia()
    {
        $query = "SELECT SUM(total_price) AS total FROM orders WHERE DATE(created_at) = CURDATE()";
        $stmt = $this->db->query($query);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    }

    public function getCountVentasDia()
    {
        $query = "SELECT COUNT(*) AS total FROM orders WHERE DATE(created_at) = CURDATE() AND (status = 'paid' OR status = 'delivered' OR status = 'pending')";
        $stmt = $this->db->query($query);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    }

    /**
     * REGISTRO DE TRACABILIDAD DE TIEMPOS (PRODUCTIVIDAD)
     * @param int $orderId ID de la orden
     * @param string $station Estación ('kitchen', 'pizza', 'bar', 'system', etc)
     * @param string $eventType Evento ('received', 'preparing', 'ready', 'delivered')
     */
    public function logStatusMilestone($orderId, $station, $eventType)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO order_time_log (order_id, station, event_type, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$orderId, $station, $eventType]);

            // Si el evento es 'delivered', actualizamos también la tabla orders
            if ($eventType === 'delivered') {
                $this->db->prepare("UPDATE orders SET delivered_at = NOW(), status = 'delivered' WHERE id = ?")->execute([$orderId]);
            }
            return true;
        } catch (PDOException $e) {
            error_log("Error logging status milestone: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener órdenes activas para el monitor KDS con filtrado opcional por estación.
     */
    public function getKDSOrders($station = null)
    {
        $sql = "SELECT o.id, o.created_at, o.status, o.shipping_address, 
                       o.kds_kitchen_ready, o.kds_pizza_ready,
                       o.kds_kitchen_preparing, o.kds_pizza_preparing, o.delivery_tier,
                       o.client_id, o.employee_id, o.consumption_type,
                       u_creator.name as fallback_name,
                       c.name as client_name,
                       u_emp.name as employee_name
                FROM orders o
                JOIN users u_creator ON o.user_id = u_creator.id
                LEFT JOIN clients c ON o.client_id = c.id
                LEFT JOIN users u_emp ON o.employee_id = u_emp.id
                WHERE o.status IN ('paid', 'preparing', 'ready')
                ORDER BY o.created_at ASC";

        $stmt = $this->db->query($sql);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($orders as &$o) {
            // Procesar nombre del cliente
            if (!empty($o['employee_name'])) {
                $o['cliente_display'] = "EMP: " . $o['employee_name'];
            } elseif (!empty($o['client_name'])) {
                $o['cliente_display'] = $o['client_name'] . " (C)";
            } else {
                $o['cliente_display'] = $o['fallback_name'];
            }

            // Flags de estación actual
            $o['is_ready_here'] = ($station === 'kitchen') ? $o['kds_kitchen_ready'] : (($station === 'pizza') ? $o['kds_pizza_ready'] : false);
            $o['is_prep_here'] = ($station === 'kitchen') ? $o['kds_kitchen_preparing'] : (($station === 'pizza') ? $o['kds_pizza_preparing'] : false);

            // Filtrar si ya está lista en esta estación
            if ($station && $o['is_ready_here']) {
                $o['skip'] = true;
                continue;
            }
            $o['skip'] = false;
        }

        return array_filter($orders, function ($o) {
            return !$o['skip'];
        });
    }
}
?>