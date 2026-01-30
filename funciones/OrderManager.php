<?php

require_once __DIR__ . '/conexion.php';

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
    public function createOrder($user_id, $items, $shipping_address, $shipping_method = null)
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

            // Insertar la cabecera de la orden
            $stmt = $this->db->prepare("INSERT INTO orders (user_id, total_price, shipping_address, shipping_method, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
            $stmt->execute([$user_id, $total_price, $shipping_address, $shipping_method]);
            $order_id = $this->db->lastInsertId();

            // Preparar consultas para Ítems
            $stmtItem = $this->db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, consumption_type) VALUES (?, ?, ?, ?, ?)");

            // SQL PARA COPIAR MODIFICADORES (CORREGIDO: Column Count Match)
            // Seleccionamos '?' como primer valor para inyectar el order_item_id
            $sqlCopyMods = "INSERT INTO order_item_modifiers
                            (order_item_id, modifier_type, component_id, component_type, quantity_adjustment, price_adjustment_usd, note, sub_item_index, is_takeaway)
                            SELECT ?, modifier_type, component_id, component_type, quantity_adjustment, price_adjustment, note, sub_item_index, is_takeaway
                            FROM cart_item_modifiers
                            WHERE cart_id = ?";

            $stmtCopy = $this->db->prepare($sqlCopyMods);

            foreach ($items as $item) {
                $price = $item['unit_price_final'] ?? $item['price'];
                // El tipo global se puede dejar como dine_in, la verdad granular está en los modificadores
                $cType = $item['consumption_type'] ?? 'dine_in';

                // Insertar Item
                $stmtItem->execute([$order_id, $item['product_id'], $item['quantity'], $price, $cType]);
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
     * 2. DESCUENTO DE INVENTARIO INTELIGENTE (RECURSIVO)
     * Maneja:
     * - Productos Simples (Link Directo o Tabla Ventas)
     * - Productos Preparados (Recetas)
     * - Combos (Recursividad a hijos)
     * - Extras Globales (Salsas, etc)
     */
    public function deductStockFromSale($orderId)
    {
        $sql = "SELECT oi.id as order_item_id, oi.product_id, oi.quantity
                FROM order_items oi
                WHERE oi.order_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            // 1. Procesar el producto principal (y sus sub-recetas recursivamente)
            // Pasamos el order_item_id para buscar sus modificadores específicos
            $this->processProductDeduction($item['product_id'], $item['quantity'], $item['order_item_id']);

            // 2. Procesar el empaque vinculado a este producto (NUEVO)
            $this->processPackagingDeduction($item['product_id'], $item['quantity']);
        }
    }

    /**
     * Función Recursiva Central
     * @param int $productId ID del producto a descontar
     * @param float $qty Cantidad a descontar
     * @param int|null $orderItemId ID del item original (solo para buscar extras del nivel superior)
     * @param int $targetIndex Índice del sub-item en el combo (para extras granulares)
     */
    private function processProductDeduction($productId, $qty, $orderItemId = null, $targetIndex = null)
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
            $this->updateStock('manufactured_products', $product['linked_manufactured_id'], $qty);
            $stockDeducted = true;
        }
        // CASO 2: Producto Simple SIN Enlace
        // Ej: Lata de Refresco (Reventa pura)
        elseif ($product['product_type'] === 'simple') {
            $this->updateStock('products', $productId, $qty);
            $stockDeducted = true;
        }
        // CASO 3: Preparado o Compuesto (Pizza, Combo)
        // NO DESCONTAMOS stock de 'products' (evita negativos).
        // Se explota la receta/componentes.

        // C. Explosión de Componentes (Recetas y Combos)
        if (!$stockDeducted) {
            // Buscar componentes
            $stmtComp = $this->db->prepare("SELECT component_type, component_id, quantity FROM product_components WHERE product_id = ?");
            $stmtComp->execute([$productId]);
            $components = $stmtComp->fetchAll(PDO::FETCH_ASSOC);

            $subItemIndex = 0; // Contador para saber qué hijo del combo somos

            foreach ($components as $comp) {
                // Cantidad total requerida de este componente
                $totalNeeded = $comp['quantity'] * $qty;

                if ($comp['component_type'] == 'product') {
                    // RECURSIVIDAD: Si el componente es otro producto (vender Combo -> Pizza)
                    // Importante: Pasamos el $orderItemId original y el índice actual para que los extras sepan a quién pegarse
                    // Nota: La lógica de cycle loops (recursividad infinita) debería prevenirse a nivel de datos, aquí asumimos DAG.

                    // Iteramos qty veces porque los indices de extras son por UNIDAD de combo
                    // Ej: 2 Combos. Combo tiene 1 Pizza.
                    // Pizza #1 (Index 0 global del combo) -> Extras index 0
                    // Pizza #2 (Index 1 global del combo) -> Extras index 1

                    // Ajuste: Si vendimos 2 Combos, y el combo tiene 1 Pizza.
                    // Tenemos Pizza_1 (del Combo_1) y Pizza_2 (del Combo_2).
                    // Los modificadores están guardados linealmente por sub_item_index.

                    // El $targetIndex que recibimos (o null) es el offset base.
                    // Si esto es nivel raiz, offset es 0.

                    $baseOffset = $targetIndex !== null ? $targetIndex : 0;

                    // Si el componente se repite N veces DENTRO del producto padre (ej: 2 Pizzas en 1 Combo)
                    // Debemos iterar por cada instancia.

                    for ($i = 0; $i < $totalNeeded; $i++) {
                        // Calculamos el índice "absoluto" de este sub-item en la lista de modificadores
                        // Esta lógica es simplificada. Asume que los modificadores se aplanan en orden de componentes.
                        // Para una implementación perfecta se requeriría un mapeo más robusto en cart_item_modifiers.
                        // Por ahora usaremos el contador lineal $subItemIndex.

                        $currentSubLoopIndex = $subItemIndex + $i;

                        // Llamada recursiva (Descontar 1 unidad del hijo)
                        $this->processProductDeduction($comp['component_id'], 1, $orderItemId, $currentSubLoopIndex);
                    }

                    $subItemIndex += $totalNeeded;

                } elseif ($comp['component_type'] == 'raw') {
                    // Ingrediente directo (Harina)
                    $this->updateStock('raw_materials', $comp['component_id'], $totalNeeded);
                } elseif ($comp['component_type'] == 'manufactured') {
                    // Sub-producto (Salsa Napolitana)
                    $this->updateStock('manufactured_products', $comp['component_id'], $totalNeeded);
                }
            }
        }

        // D. Procesar Extras (Modificadores)
        // Solo si estamos en el contexto de una orden (tenemos ID) y coincidimos con el índice
        if ($orderItemId) {
            $this->processExtras($orderItemId, $targetIndex, $productId);
        }
    }

    private function processExtras($orderItemId, $targetIndex, $productId)
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
                $this->updateStock('raw_materials', $id, $qty);
            } elseif ($type == 'manufactured') {
                $this->updateStock('manufactured_products', $id, $qty);
            } elseif ($type == 'product') {
                // RECURSIVIDAD: Si el contorno es un producto (ej: un combo que deja elegir otro producto)
                $this->processProductDeduction($id, $qty);
            }
        }
    }

    private function processPackagingDeduction($productId, $qty)
    {
        $sql = "SELECT raw_material_id, quantity FROM product_packaging WHERE product_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId]);
        $packaging = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($packaging as $pack) {
            $totalNeeded = $pack['quantity'] * $qty;
            $this->updateStock('raw_materials', $pack['raw_material_id'], $totalNeeded);
        }
    }

    private function updateStock($table, $id, $qty)
    {
        $field = ($table == 'raw_materials') ? 'stock_quantity' : 'stock';
        // ATOMIC CHECK: Only update if stock >= qty
        $sql = "UPDATE {$table} SET {$field} = {$field} - ? WHERE id = ? AND {$field} >= ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$qty, $id, $qty]);

        if ($stmt->rowCount() === 0) {
            // Rollback is handled by the parent try-catch in createOrder due to this exception
            throw new Exception("Stock insuficiente en {$table} (ID: {$id}) para cubrir la demanda.");
        }
    }

    public function updateOrderStatus($id, $status, $tracking_number = null)
    {
        $stmt = $this->db->prepare("UPDATE orders SET status = ?, tracking_number = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $tracking_number, $id]);
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
}
?>