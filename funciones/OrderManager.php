<?php

class OrderManager {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }





    // Crear una nueva orden
    public function createOrder($user_id, $items, $shipping_address, $shipping_method = null) {
        try {
            $this->db->beginTransaction();

            // Calcular el total de la orden
            $total_price = 0;
            foreach ($items as $item) {
                $total_price += $item['price'] * $item['quantity'];
            }

            // Insertar la orden en la base de datos
            $stmt = $this->db->prepare("INSERT INTO orders (user_id, total_price, shipping_address, shipping_method, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$user_id, $total_price, $shipping_address, $shipping_method]);

            $order_id = $this->db->lastInsertId();

            // Insertar los productos en order_items
            $stmt = $this->db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($items as $item) {
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);



            }

            $this->db->commit();
            return $order_id;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error al crear la orden: " . $e->getMessage());
            return false;
        }
    }







    // Obtener una orden por ID
    public function getOrderById($id) {
          $sql = "SELECT orders.*, users.name AS customer_name FROM orders JOIN users ON orders.user_id = users.id WHERE orders.id = ?";
          $stmt = $this->db->prepare($sql);
          $stmt->execute([$id]);
          return $stmt->fetch(PDO::FETCH_ASSOC);
      }


        public function getOrdersBySearchAndFilter($search = '', $filter = '') {
            $sql = "SELECT orders.*, users.name AS customer_name FROM orders JOIN users ON orders.user_id = users.id WHERE 1";

            if (!empty($search)) {
                $search = "%" . $search . "%";
                $sql .= " AND (orders.id LIKE ? OR users.name LIKE ?)";
            }

            if ($filter === 'pending') {
                $sql .= " AND orders.status = 'pending'";
            } elseif ($filter === 'paid') {
                $sql .= " AND orders.status = 'paid'";
            } elseif ($filter === 'shipped') {
                $sql .= " AND orders.status = 'shipped'";
            } elseif ($filter === 'delivered') {
                $sql .= " AND orders.status = 'delivered'";
            } elseif ($filter === 'cancelled') {
                $sql .= " AND orders.status = 'cancelled'";
            }

            $stmt = $this->db->prepare($sql);

            if (!empty($search)) {
                $stmt->execute([$search, $search]);
            } else {
                $stmt->execute();
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }



    // Obtener los ítems de una orden
    public function getOrderItems($order_id) {
        $stmt = $this->db->prepare("SELECT oi.*, p.name, p.price_usd FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE order_id = ?");
        $stmt->execute([$order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener todas las órdenes de un usuario
    public function getOrdersByUser($user_id) {
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Actualizar el estado de una orden
    public function updateOrderStatus($id, $status, $tracking_number = null) {
        $stmt = $this->db->prepare("UPDATE orders SET status = ?, tracking_number = ?, created_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $tracking_number, $id]);
    }

    // Actualizar detalles de una orden
    public function updateOrderDetails($id, $shipping_address, $shipping_method, $total_price) {
        $stmt = $this->db->prepare("UPDATE orders SET shipping_address = ?, shipping_method = ?, total_price = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$shipping_address, $shipping_method, $total_price, $id]);
    }

    // Actualizar los productos de una orden
    public function updateOrderItems($order_id, $items) {
        try {
            $this->db->beginTransaction();

            // Eliminar los productos existentes de la orden
            $stmt = $this->db->prepare("DELETE FROM order_items WHERE order_id = ?");
            $stmt->execute([$order_id]);

            // Insertar los nuevos productos
            $stmt = $this->db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($items as $item) {
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error al actualizar los productos de la orden: " . $e->getMessage());
            return false;
        }
    }

    public function getTotalVentas() {
        $query = "SELECT SUM(total_price) AS total_ventas FROM orders WHERE status = 'delivered'";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_ventas'] ?? 0;
    }

    public function countOrdersByStatus($status) {
        $query = "SELECT COUNT(*) AS total FROM orders WHERE status = :status";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    public function getTotalProductosVendidos() {
        $query = "SELECT SUM(quantity) AS total_productos FROM order_items";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_productos'] ?? 0;
    }

    public function getUltimosPedidos($limit = 25) {
        $query = "SELECT o.id, o.user_id, u.name, o.total_price, o.status, o.created_at
                  FROM orders o
                  JOIN users u ON o.user_id = u.id
                  ORDER BY o.created_at DESC
                  LIMIT :limit";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



        public function getTotalVentasSemana() {
            $endDate = new DateTime(); // Obtiene la fecha y hora actual
            $startDate = (clone $endDate)->modify('-7 days'); // Calcula la fecha de hace 7 días

            $startDateStr = $startDate->format('Y-m-d H:i:s'); // Formatea las fechas para SQL
            $endDateStr = $endDate->format('Y-m-d H:i:s');

            $sql = "SELECT SUM(total_price) AS total FROM orders WHERE created_at BETWEEN :startDate AND :endDate";

            try {
                $stmt = $this->db->prepare($sql); // Prepara la consulta SQL
                $stmt->bindParam(':startDate', $startDateStr); // Asigna los valores a los parámetros
                $stmt->bindParam(':endDate', $endDateStr);
                $stmt->execute(); // Ejecuta la consulta
                $result = $stmt->fetch(PDO::FETCH_ASSOC); // Obtiene el resultado

                if ($result && $result['total'] !== null) {
                    return $result['total']; // Devuelve el total de ventas
                } else {
                    return 0; // No hay ventas en la última semana
                }
            } catch (PDOException $e) {
                error_log("Error de base de datos en getTotalVentasSemana: " . $e->getMessage()); // Registra el error
                return 0; // Devuelve 0 en caso de error
            }
        }



            public function getTotalVentasDia() {
                $today = new DateTime(); // Obtiene la fecha y hora actual
                $todayStr = $today->format('Y-m-d'); // Formatea la fecha para SQL (solo la fecha)

                $sql = "SELECT SUM(total_price) AS total FROM orders WHERE DATE(created_at) = :today";

                try {
                    $stmt = $this->db->prepare($sql);
                    $stmt->bindParam(':today', $todayStr);
                    $stmt->execute();
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($result && $result['total'] !== null) {
                        return $result['total'];
                    } else {
                        return 0; // No hay ventas hoy
                    }
                } catch (PDOException $e) {
                    error_log("Error de base de datos en getTotalVentasDia: " . $e->getMessage());
                    return 0;
                }
            }





        // Método para obtener el total de ventas del mes actual
        public function getTotalVentasMes() {
            $query = "SELECT SUM(total_price) AS total_ventas FROM orders WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total_ventas'] ?? 0; // Devuelve 0 si no hay ventas
        }




    // Eliminar una orden
    public function deleteOrder($id) {
        try {
            $this->db->beginTransaction();

            // Eliminar los productos de la orden
            $stmt = $this->db->prepare("DELETE FROM order_items WHERE order_id = ?");
            $stmt->execute([$id]);

            // Eliminar la orden
            $stmt = $this->db->prepare("DELETE FROM orders WHERE id = ?");
            $stmt->execute([$id]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error al eliminar la orden: " . $e->getMessage());
            return false;
        }
    }
}
