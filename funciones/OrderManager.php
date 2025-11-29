<?php

class OrderManager {
    private $db;

    public function __construct($db) {
        $this->db = Database::getConnection();
    }

    /**
     * 1. CREAR ORDEN Y TRANSFERIR DETALLES (AJUSTADO)
     * Ahora guarda modificadores y tipo de consumo por ítem.
     */
    public function createOrder($user_id, $items, $shipping_address, $shipping_method = null) {
        try {
            $this->db->beginTransaction();

            // Calcular el total de la orden
            $total_price = 0;
            foreach ($items as $item) {
                // Usamos unit_price_final si existe (calculado en CartManager), sino el precio base
                $price = $item['unit_price_final'] ?? $item['price'];
                $total_price += $price * $item['quantity'];
            }

            // Insertar la cabecera de la orden
            $stmt = $this->db->prepare("INSERT INTO orders (user_id, total_price, shipping_address, shipping_method, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
            $stmt->execute([$user_id, $total_price, $shipping_address, $shipping_method]);
            $order_id = $this->db->lastInsertId();

            // Preparar consultas para Ítems y Modificadores
            // Nota: Agregamos 'consumption_type' al insert
            $stmtItem = $this->db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, consumption_type) VALUES (?, ?, ?, ?, ?)");

            // SQL para copiar modificadores desde el carrito a la orden
            $sqlCopyMods = "INSERT INTO order_item_modifiers
                            (order_item_id, modifier_type, raw_material_id, quantity_adjustment, price_adjustment_usd)
                            SELECT ?, modifier_type, raw_material_id, quantity_adjustment, price_adjustment
                            FROM cart_item_modifiers
                            WHERE cart_id = ?";
            $stmtCopy = $this->db->prepare($sqlCopyMods);

            foreach ($items as $item) {
                $price = $item['unit_price_final'] ?? $item['price'];
                $cType = $item['consumption_type'] ?? 'takeaway'; // Default para llevar

                // Insertar Item
                $stmtItem->execute([$order_id, $item['product_id'], $item['quantity'], $price, $cType]);
                $order_item_id = $this->db->lastInsertId();

                // Copiar sus modificadores (Si el ítem tiene ID de carrito)
                if (isset($item['id'])) {
                    $stmtCopy->execute([$order_item_id, $item['id']]);
                }
            }

            $this->db->commit();
            return $order_id;

        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error en createOrder: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 2. DESCUENTO DE INVENTARIO INTELIGENTE (NUEVO)
     * Se debe llamar al confirmar el pago en process_checkout.php
     */
    public function deductStockFromSale($orderId) {
        // Obtener productos de la orden con su tipo de consumo
        $sql = "SELECT oi.id as order_item_id, oi.product_id, oi.quantity, oi.consumption_type, p.product_type
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
                // Producto Preparado/Combo: Descuento de Receta con Reglas
                $this->processRecipeDeduction($item['order_item_id'], $item['product_id'], $qtySold, $item['consumption_type']);
            }
        }
    }

    // Lógica interna para procesar la receta (Privada)
    private function processRecipeDeduction($orderItemId, $productId, $qtySold, $consumptionType) {
        // A. Obtener Modificadores (Qué quitó el cliente)
        $stmtMods = $this->db->prepare("SELECT modifier_type, raw_material_id, quantity_adjustment FROM order_item_modifiers WHERE order_item_id = ?");
        $stmtMods->execute([$orderItemId]);
        $modifiers = $stmtMods->fetchAll(PDO::FETCH_ASSOC);

        $removedIngredients = [];
        foreach ($modifiers as $mod) {
            if ($mod['modifier_type'] == 'remove') $removedIngredients[] = $mod['raw_material_id'];
        }

        // B. Obtener Receta Base
        $stmtRecipe = $this->db->prepare("SELECT * FROM product_components WHERE product_id = ?");
        $stmtRecipe->execute([$productId]);
        $components = $stmtRecipe->fetchAll(PDO::FETCH_ASSOC);

        // C. Descontar Receta Base (Aplicando Filtros)
        foreach ($components as $comp) {
            // Regla 1: Si se removió, no descontar
            if ($comp['component_type'] == 'raw' && in_array($comp['component_id'], $removedIngredients)) continue;

            // Regla 2: Si es 'Comer aquí' y es Empaque, no descontar
            if ($consumptionType == 'dine_in' && $comp['component_type'] == 'raw') {
                $stmtCheck = $this->db->prepare("SELECT category FROM raw_materials WHERE id = ?");
                $stmtCheck->execute([$comp['component_id']]);
                $cat = $stmtCheck->fetchColumn();
                if ($cat == 'packaging') continue;
            }

            $totalNeeded = $comp['quantity'] * $qtySold;

            if ($comp['component_type'] == 'raw') {
                $this->updateStock('raw_materials', $comp['component_id'], $totalNeeded);
            } elseif ($comp['component_type'] == 'manufactured') {
                $this->updateStock('manufactured_products', $comp['component_id'], $totalNeeded);
            } elseif ($comp['component_type'] == 'product') {
                $this->updateStock('products', $comp['component_id'], $totalNeeded);
            }
        }

        // D. Descontar Extras
        foreach ($modifiers as $mod) {
            if ($mod['modifier_type'] == 'add') {
                $qtyExtra = $mod['quantity_adjustment'] * $qtySold;
                $this->updateStock('raw_materials', $mod['raw_material_id'], $qtyExtra);
            }
        }
    }

    // Helper para actualizar stock
    private function updateStock($table, $id, $qty) {
        $field = ($table == 'raw_materials') ? 'stock_quantity' : 'stock';
        $sql = "UPDATE {$table} SET {$field} = {$field} - ? WHERE id = ?";
        $this->db->prepare($sql)->execute([$qty, $id]);
    }

    // --- FUNCIONES EXISTENTES (MANTENIDAS) ---

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
        $stmt = $this->db->prepare("SELECT oi.*, p.name, p.price_usd FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE order_id = ?");
        $stmt->execute([$order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Nuevo: Para ver detalles en el ticket
    public function getItemModifiers($orderItemId) {
        $sql = "SELECT m.*, rm.name as ingredient_name
                FROM order_item_modifiers m
                JOIN raw_materials rm ON m.raw_material_id = rm.id
                WHERE m.order_item_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderItemId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

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
