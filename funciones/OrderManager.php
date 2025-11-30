<?php

class OrderManager {
    private $db;

    public function __construct($db) {
        $this->db = Database::getConnection();
    }

    /**
     * 1. CREAR ORDEN (CON PROTECCIÓN DE TRANSACCIÓN)
     * Transfiere la configuración exacta a la orden, respetando transacciones externas.
     */
    public function createOrder($user_id, $items, $shipping_address, $shipping_method = null) {
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
                            (order_item_id, modifier_type, raw_material_id, quantity_adjustment, price_adjustment_usd, note, sub_item_index, is_takeaway)
                            SELECT ?, modifier_type, raw_material_id, quantity_adjustment, price_adjustment, note, sub_item_index, is_takeaway
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
     * 2. DESCUENTO DE INVENTARIO INTELIGENTE
     * Lee el campo booleano para decidir si descontar empaque o no.
     */
    public function deductStockFromSale($orderId) {
        $sql = "SELECT oi.id as order_item_id, oi.product_id, oi.quantity, p.product_type
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            $qtySold = $item['quantity'];

            if ($item['product_type'] === 'simple') {
                // Producto Simple (Refresco): Descuento directo
                $this->updateStock('products', $item['product_id'], $qtySold);
            } else {
                // Producto Preparado/Combo: Descuento con Lógica Granular
                $this->processRecipeDeduction($item['order_item_id'], $item['product_id'], $qtySold);
            }
        }
    }

    // Lógica interna para procesar la receta y el booleano
    private function processRecipeDeduction($orderItemId, $productId, $qtySold) {
        // 1. Obtener Modificadores y ESTADO BOOLEANO
        $stmtMods = $this->db->prepare("SELECT modifier_type, raw_material_id, sub_item_index, is_takeaway FROM order_item_modifiers WHERE order_item_id = ?");
        $stmtMods->execute([$orderItemId]);
        $modifiers = $stmtMods->fetchAll(PDO::FETCH_ASSOC);

        // Mapear configuración por índice
        $takeawayMap = [];      // [index => true/false]
        $removedIngredients = []; // [index => [id1, id2]]

        foreach ($modifiers as $mod) {
            $idx = $mod['sub_item_index'];

            // Si es un registro de tipo INFO, contiene el booleano de estado
            if ($mod['modifier_type'] == 'info') {
                $takeawayMap[$idx] = ($mod['is_takeaway'] == 1);
            }
            // Si es REMOVE, guardamos qué ingrediente se quitó en este índice
            if ($mod['modifier_type'] == 'remove') {
                $removedIngredients[$idx][] = $mod['raw_material_id'];
            }
        }

        // 2. Obtener Receta Base del Producto
        $stmtRecipe = $this->db->prepare("SELECT * FROM product_components WHERE product_id = ?");
        $stmtRecipe->execute([$productId]);
        $components = $stmtRecipe->fetchAll(PDO::FETCH_ASSOC);

        // 3. Iterar por componentes de la receta
        $componentIndex = 0;

        foreach ($components as $comp) {
            // Cantidad total de veces que este componente aparece en la venta total
            // Ajuste de bucle:
            // Si es un producto dentro de combo (Ej: 2 Pizzas), iteramos 2 veces por cada combo vendido.
            // Si es un ingrediente directo (Harina), iteramos 1 vez por cada producto vendido.

            $instancesPerUnit = ($comp['component_type'] == 'product') ? $comp['quantity'] : 1;
            $totalLoops = $instancesPerUnit * $qtySold;

            // Ajuste especial: Si es ingrediente de producto simple preparado, el loop es qtySold.
            if ($comp['component_type'] != 'product') {
                $totalLoops = $qtySold;
            }

            for ($i = 0; $i < $totalLoops; $i++) {
                // Calcular índice virtual para buscar configuración
                $currentIndex = ($comp['component_type'] == 'product') ? ($componentIndex + $i) : $i;

                // Obtener configuración para esta instancia específica
                $isTakeaway = $takeawayMap[$currentIndex] ?? false; // Default Dine In
                $itemsRemoved = $removedIngredients[$currentIndex] ?? [];

                // VALIDACIONES DE DESCUENTO:

                // A. Si se removió explícitamente (Sin Cebolla)
                if ($comp['component_type'] == 'raw' && in_array($comp['component_id'], $itemsRemoved)) {
                    continue;
                }

                // B. Lógica de Empaque: Si es MESA y es Packaging -> NO DESCONTAR
                if (!$isTakeaway && $comp['component_type'] == 'raw') {
                    $stmtCheck = $this->db->prepare("SELECT category FROM raw_materials WHERE id = ?");
                    $stmtCheck->execute([$comp['component_id']]);
                    $cat = $stmtCheck->fetchColumn();
                    if ($cat == 'packaging') continue;
                }

                // Cantidad a descontar
                // Si es un producto (Pizza del combo), descontamos 1 unidad de stock.
                // Si es un ingrediente (Harina), descontamos la cantidad definida en la receta.
                $qtyToDeduct = ($comp['component_type'] == 'product') ? 1 : $comp['quantity'];

                if ($comp['component_type'] == 'raw') $this->updateStock('raw_materials', $comp['component_id'], $qtyToDeduct);
                elseif ($comp['component_type'] == 'manufactured') $this->updateStock('manufactured_products', $comp['component_id'], $qtyToDeduct);
                elseif ($comp['component_type'] == 'product') $this->updateStock('products', $comp['component_id'], $qtyToDeduct);
            }

            // Avanzar índice global solo si es un componente principal (producto dentro de combo)
            if ($comp['component_type'] == 'product') {
                $componentIndex += ($comp['quantity'] * $qtySold);
            }
        }

        // 4. Descontar Extras (Modifiers 'add')
        foreach ($modifiers as $mod) {
            if ($mod['modifier_type'] == 'add') {
                $this->updateStock('raw_materials', $mod['raw_material_id'], 0.050); // 50g estándar
            }
        }
    }

    private function updateStock($table, $id, $qty) {
        $field = ($table == 'raw_materials') ? 'stock_quantity' : 'stock';
        $sql = "UPDATE {$table} SET {$field} = {$field} - ? WHERE id = ?";
        $this->db->prepare($sql)->execute([$qty, $id]);
    }

    public function updateOrderStatus($id, $status, $tracking_number = null) {
        $stmt = $this->db->prepare("UPDATE orders SET status = ?, tracking_number = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $tracking_number, $id]);
    }

    public function getOrderById($id) {
        $sql = "SELECT orders.*, users.name AS customer_name FROM orders JOIN users ON orders.user_id = users.id WHERE orders.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getOrderItems($order_id) {
            // CORRECCIÓN: Agregamos p.product_type al SELECT
            $stmt = $this->db->prepare("
                SELECT oi.*, p.name, p.price_usd, p.product_type
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE order_id = ?
            ");
            $stmt->execute([$order_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

    public function getItemModifiers($orderItemId) {
        $sql = "SELECT m.*, rm.name as ingredient_name
                FROM order_item_modifiers m
                LEFT JOIN raw_materials rm ON m.raw_material_id = rm.id
                WHERE m.order_item_id = ?
                ORDER BY m.sub_item_index ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderItemId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Funciones auxiliares para reportes y compatibilidad
    public function getOrdersBySearchAndFilter($search = '', $filter = '') {
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

    public function getTotalVentas() {
        $query = "SELECT SUM(total_price) AS total_ventas FROM orders WHERE status = 'delivered' OR status = 'paid'";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total_ventas'] ?? 0;
    }

    public function countOrdersByStatus($status) {
        $query = "SELECT COUNT(*) AS total FROM orders WHERE status = :status";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':status', $status);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    }

    public function getTotalProductosVendidos() {
        $query = "SELECT SUM(quantity) AS total_productos FROM order_items";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total_productos'] ?? 0;
    }

    public function getUltimosPedidos($limit = 10) {
        $query = "SELECT o.id, o.user_id, u.name, o.total_price, o.status, o.created_at FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT :limit";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalVentasAnio() {
        $sql = "SELECT SUM(total_price) as total FROM orders WHERE YEAR(created_at) = YEAR(CURRENT_DATE()) AND (status = 'paid' OR status = 'delivered')";
        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    }

    public function getTotalVentasMes() {
        $query = "SELECT SUM(total_price) AS total FROM orders WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())";
        $stmt = $this->db->query($query);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    }

    public function getTotalVentasSemana() {
        $query = "SELECT SUM(total_price) AS total FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $stmt = $this->db->query($query);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    }

    public function getTotalVentasDia() {
        $query = "SELECT SUM(total_price) AS total FROM orders WHERE DATE(created_at) = CURDATE()";
        $stmt = $this->db->query($query);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    }
}
?>
