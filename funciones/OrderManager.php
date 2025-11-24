<?php

class OrderManager {
    private $db;

    public function __construct($db) {
        $this->db = Database::getConnection();
    }

    // Crear una nueva orden (SIN transacciones internas)
    public function createOrder($user_id, $items, $shipping_address, $shipping_method = null) {
        // Eliminamos try-catch y transactions aquí para que el controlador principal (checkout) maneje todo.

        // Calcular el total de la orden
        $total_price = 0;
        foreach ($items as $item) {
            $total_price += $item['price'] * $item['quantity'];
        }

        // Insertar la orden
        $stmt = $this->db->prepare("INSERT INTO orders (user_id, total_price, shipping_address, shipping_method, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $total_price, $shipping_address, $shipping_method]);

        $order_id = $this->db->lastInsertId();

        // Insertar los productos
        $stmt = $this->db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($items as $item) {
            $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
        }

        return $order_id;
    }

    // Actualizar el estado de una orden
    public function updateOrderStatus($id, $status, $tracking_number = null) {
        $stmt = $this->db->prepare("UPDATE orders SET status = ?, tracking_number = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $tracking_number, $id]);
    }

    // --- MÉTODOS DE CONSULTA (Lectura) ---

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

    // Métodos de reportes
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
        // Ventas de ESTE AÑO
        $sql = "SELECT SUM(total_price) as total FROM orders
                WHERE YEAR(created_at) = YEAR(CURRENT_DATE())
                AND (status = 'paid' OR status = 'delivered')";
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
