<?php

class ProductManager {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // Crear un producto con márgenes de ganancia
    public function createProduct($name, $description, $price_usd, $price_ves, $stock, $image_url = 'default.jpg', $profit_margin = 20.00) {
        $stmt = $this->db->prepare("INSERT INTO products (name, description, price_usd, price_ves, stock, image_url, profit_margin, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        return $stmt->execute([$name, $description, $price_usd, $price_ves, $stock, $image_url, $profit_margin]);
    }

    // Obtener un producto por ID
    public function getProductById($id) {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }


        public function getTotalProduct() { // <-- NOTA: Nombre corregido
            $query = "SELECT COUNT(*) AS total FROM products";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        }


        public function updateProductPriceVes($productId, $newPriceVes) {
            $stmt = $this->db->prepare("UPDATE products SET price_ves = ? WHERE id = ?");
            return $stmt->execute([$newPriceVes, $productId]);
        }



    // Obtener todos los productos
    public function getAllProducts() {
        $stmt = $this->db->prepare("SELECT * FROM products ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Buscar productos por nombre
    public function searchProducts($keyword) {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE name LIKE ? ORDER BY created_at DESC");
        $stmt->execute(["%$keyword%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener productos con stock disponible
    public function getAvailableProducts() {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE stock > 0 ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener productos con stock bajo (menos de 5 unidades)
    public function getLowStockProducts($threshold = 5) {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE stock <= ? ORDER BY stock ASC");
        $stmt->execute([$threshold]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Actualizar un producto
    public function updateProduct($id, $name, $description, $price_usd, $price_ves, $stock, $image = null, $profit_margin = 20.00) {
        if ($image) {
            $stmt = $this->db->prepare("UPDATE products SET name = ?, description = ?, price_usd = ?, price_ves = ?, stock = ?, image_url = ?, profit_margin = ?, updated_at = NOW() WHERE id = ?");
            return $stmt->execute([$name, $description, $price_usd, $price_ves, $stock, $image, $profit_margin, $id]);
        } else {
            $stmt = $this->db->prepare("UPDATE products SET name = ?, description = ?, price_usd = ?, price_ves = ?, stock = ?, profit_margin = ?, updated_at = NOW() WHERE id = ?");
            return $stmt->execute([$name, $description, $price_usd, $price_ves, $stock, $profit_margin, $id]);
        }
    }

    // Actualizar stock de un producto
    public function updateProductStock($id, $stock) {
        $stmt = $this->db->prepare("UPDATE products SET stock = ?, created_at = NOW() WHERE id = ?");
        return $stmt->execute([$stock, $id]);
    }

    // Eliminar un producto
    public function deleteProduct($id) {
        $stmt = $this->db->prepare("DELETE FROM products WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function updateAllPricesBasedOnRate($newRate) {
        try {
            // Multiplica price_usd * tasa y actualiza price_ves en toda la tabla
            $sql = "UPDATE products SET price_ves = price_usd * :rate, updated_at = NOW()";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':rate' => $newRate]);
        } catch (PDOException $e) {
            error_log("Error en actualización masiva: " . $e->getMessage());
            return false;
        }
    }


}

// Uso de la clase
// $productManager = new ProductManager();
// $productManager->createProduct("Ejemplo", "Descripción del producto", 19.99, 750, 10, "image.jpg", 25.00);
