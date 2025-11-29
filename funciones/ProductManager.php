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



        // --- NUEVAS FUNCIONES PARA MANEJO DE RECETAS DE VENTA ---

            // 1. Cambiar el tipo de producto (Simple, Combo, Preparado)
            public function updateProductType($id, $type) {
                $stmt = $this->db->prepare("UPDATE products SET product_type = ? WHERE id = ?");
                return $stmt->execute([$type, $id]);
            }

            // 2. Obtener los componentes actuales de un producto
            public function getProductComponents($productId) {
                $sql = "SELECT pc.*,
                        CASE
                            WHEN pc.component_type = 'raw' THEN rm.name
                            WHEN pc.component_type = 'manufactured' THEN mp.name
                            WHEN pc.component_type = 'product' THEN p.name
                        END as item_name,
                        CASE
                            WHEN pc.component_type = 'raw' THEN rm.unit
                            WHEN pc.component_type = 'manufactured' THEN mp.unit
                            WHEN pc.component_type = 'product' THEN 'und'
                        END as item_unit,
                        CASE
                            WHEN pc.component_type = 'raw' THEN rm.cost_per_unit
                            WHEN pc.component_type = 'manufactured' THEN mp.unit_cost_average
                            WHEN pc.component_type = 'product' THEN p.price_usd -- Usamos precio costo si lo tuvieras, por ahora precio venta
                        END as item_cost
                        FROM product_components pc
                        LEFT JOIN raw_materials rm ON pc.component_id = rm.id AND pc.component_type = 'raw'
                        LEFT JOIN manufactured_products mp ON pc.component_id = mp.id AND pc.component_type = 'manufactured'
                        LEFT JOIN products p ON pc.component_id = p.id AND pc.component_type = 'product'
                        WHERE pc.product_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$productId]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // 3. Agregar un componente a la receta de venta
            public function addComponent($productId, $type, $componentId, $qty) {
                // Verificar si ya existe para sumar cantidad
                $sqlCheck = "SELECT id FROM product_components WHERE product_id = ? AND component_type = ? AND component_id = ?";
                $stmtCheck = $this->db->prepare($sqlCheck);
                $stmtCheck->execute([$productId, $type, $componentId]);

                if ($stmtCheck->fetch()) {
                    $sql = "UPDATE product_components SET quantity = quantity + ? WHERE product_id = ? AND component_type = ? AND component_id = ?";
                    $stmt = $this->db->prepare($sql);
                    return $stmt->execute([$qty, $productId, $type, $componentId]);
                } else {
                    $sql = "INSERT INTO product_components (product_id, component_type, component_id, quantity) VALUES (?, ?, ?, ?)";
                    $stmt = $this->db->prepare($sql);
                    return $stmt->execute([$productId, $type, $componentId, $qty]);
                }
            }

            // 4. Eliminar un componente
            public function removeComponent($id) {
                $stmt = $this->db->prepare("DELETE FROM product_components WHERE id = ?");
                return $stmt->execute([$id]);
            }

            // =========================================================
                // CÁLCULO DE STOCK POTENCIAL (VIRTUAL)
                // =========================================================

                public function getVirtualStock($productId) {
                    // 1. Obtener la receta
                    $components = $this->getProductComponents($productId);

                    // Si no tiene receta (y no es simple), no podemos calcular nada -> 0
                    if (empty($components)) {
                        return 0;
                    }

                    $minStock = null; // Empezamos sin límite definido

                    foreach ($components as $comp) {
                        $qtyNeeded = floatval($comp['quantity']);

                        // Evitar división por cero
                        if ($qtyNeeded <= 0) continue;

                        $currentStock = 0;

                        // 2. Averiguar cuánto stock hay de este ingrediente específico
                        if ($comp['component_type'] == 'raw') {
                            $stmt = $this->db->prepare("SELECT stock_quantity FROM raw_materials WHERE id = ?");
                            $stmt->execute([$comp['component_id']]);
                            $currentStock = floatval($stmt->fetchColumn());

                        } elseif ($comp['component_type'] == 'manufactured') {
                            $stmt = $this->db->prepare("SELECT stock FROM manufactured_products WHERE id = ?");
                            $stmt->execute([$comp['component_id']]);
                            $currentStock = floatval($stmt->fetchColumn());

                        } elseif ($comp['component_type'] == 'product') {
                            // Si es un combo que incluye otro producto (ej: Refresco)
                            $stmt = $this->db->prepare("SELECT stock FROM products WHERE id = ?");
                            $stmt->execute([$comp['component_id']]);
                            $currentStock = floatval($stmt->fetchColumn());
                        }

                        // 3. ¿Para cuántos platos me alcanza este ingrediente?
                        // floor() redondea hacia abajo (tienes 1.9 porciones de queso = 1 pizza, no 2)
                        $possibleWithThisItem = floor($currentStock / $qtyNeeded);

                        // 4. Lógica del "Cuello de Botella"
                        // El máximo que puedo vender es el mínimo que me permite mi ingrediente más escaso.
                        if ($minStock === null || $possibleWithThisItem < $minStock) {
                            $minStock = $possibleWithThisItem;
                        }
                    }

                    return ($minStock === null) ? 0 : $minStock;
                }


}

// Uso de la clase
// $productManager = new ProductManager();
// $productManager->createProduct("Ejemplo", "Descripción del producto", 19.99, 750, 10, "image.jpg", 25.00);
