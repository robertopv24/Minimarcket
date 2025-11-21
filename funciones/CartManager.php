<?php

class CartManager {
    private $db;
    private $table_name = 'cart';

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // Agregar producto al carrito (si ya existe, actualizar la cantidad)
    public function addToCart($user_id, $product_id, $quantity) {
        try {
            // Obtener información del producto
            $productManager = new ProductManager($this->db);
            $product = $productManager->getProductById($product_id);

            if (!$product) {
                return "Error: Producto no encontrado.";
            }

            // Verificar stock disponible
            if ($product['stock'] < $quantity) {
                return "Error: No hay suficiente stock disponible.";
            }

            // Verificar si el producto ya está en el carrito
            $stmt = $this->db->prepare("SELECT quantity FROM {$this->table_name} WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingItem) {
                // Actualizar la cantidad si el producto ya está en el carrito
                $newQuantity = $existingItem['quantity'] + $quantity;
                if ($product['stock'] < $newQuantity) {
                    return "Error: No hay suficiente stock disponible.";
                }

                $stmt = $this->db->prepare("UPDATE {$this->table_name} SET quantity = ? WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$newQuantity, $user_id, $product_id]);
            } else {
                // Agregar el producto al carrito si no existe
                $stmt = $this->db->prepare("INSERT INTO {$this->table_name} (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $product_id, $quantity]);
            }

            return true; // Éxito
        } catch (PDOException $e) {
            // Manejar errores de la base de datos
            error_log("Error en addToCart: " . $e->getMessage());
            return "Error: No se pudo agregar el producto al carrito.";
        }
    }

    // Obtener los productos del carrito de un usuario
    public function getCart($user_id) {
        $stmt = $this->db->prepare("SELECT c.*, p.name, p.price_usd, p.price_ves FROM {$this->table_name} c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // Calcular el total del carrito
    public function calculateTotal($cart_items) {
        $total_usd = 0;
        $total_ves = 0;
        foreach ($cart_items as $item) {
            $price_usd = $item['price_usd'] ?? 0;
            $price_ves = $item['price_ves'] ?? 0;
            $total_usd += $price_usd * $item['quantity'];
            $total_ves += $price_ves * $item['quantity'];
        }
        return ['total_usd' => $total_usd, 'total_ves' => $total_ves];
    }


    // Eliminar producto del carrito
    public function removeFromCart($user_id, $product_id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table_name} WHERE user_id = ? AND product_id = ?");
        return $stmt->execute([$user_id, $product_id]);
    }

    // Actualizar la cantidad de un producto en el carrito
    public function updateCartQuantity($user_id, $product_id, $quantity) {
        try {
            // Obtener información del producto
            $productManager = new ProductManager($this->db);
            $product = $productManager->getProductById($product_id);

            if (!$product) {
                return "Error: Producto no encontrado.";
            }

            // Verificar stock disponible
            if ($product['stock'] < $quantity) {
                return "Error: No hay suficiente stock disponible.";
            }

            // Actualizar la cantidad en el carrito
            $stmt = $this->db->prepare("UPDATE {$this->table_name} SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$quantity, $user_id, $product_id]);

            return true; // Éxito
        } catch (PDOException $e) {
            // Manejar errores de la base de datos
            error_log("Error en updateCartQuantity: " . $e->getMessage());
            return "Error: No se pudo actualizar la cantidad del producto en el carrito.";
        }
    }

    // Vaciar el carrito de un usuario
    public function emptyCart($user_id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table_name} WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    }
}
