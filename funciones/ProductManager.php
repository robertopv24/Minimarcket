<?php

require_once __DIR__ . '/conexion.php';

class ProductManager
{
    private $db;

    public function __construct($db)
    {
        $this->db = Database::getConnection();
    }

    // Crear un producto con márgenes de ganancia
    public function createProduct($name, $description, $price_usd, $price_ves, $stock, $image_url = 'default.jpg', $profit_margin = 20.00)
    {
        $stmt = $this->db->prepare("INSERT INTO products (name, description, price_usd, price_ves, stock, image_url, profit_margin, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        return $stmt->execute([$name, $description, $price_usd, $price_ves, $stock, $image_url, $profit_margin]);
    }

    // Obtener un producto por ID
    public function getProductById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getTotalProduct()
    {
        $query = "SELECT COUNT(*) AS total FROM products";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    public function updateProductPriceVes($productId, $newPriceVes)
    {
        $stmt = $this->db->prepare("UPDATE products SET price_ves = ? WHERE id = ?");
        return $stmt->execute([$newPriceVes, $productId]);
    }

    // Obtener todos los productos
    public function getAllProducts()
    {
        $stmt = $this->db->prepare("SELECT * FROM products ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Buscar productos por nombre
    public function searchProducts($keyword)
    {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE name LIKE ? ORDER BY created_at DESC");
        $stmt->execute(["%$keyword%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener productos con stock disponible
    public function getAvailableProducts()
    {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE stock > 0 ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener productos con stock bajo
    public function getLowStockProducts($threshold = 5)
    {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE stock <= ? ORDER BY stock ASC");
        $stmt->execute([$threshold]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Actualizar un producto
    public function updateProduct($id, $name, $description, $price_usd, $price_ves, $stock, $image = null, $profit_margin = 20.00)
    {
        if ($image) {
            $stmt = $this->db->prepare("UPDATE products SET name = ?, description = ?, price_usd = ?, price_ves = ?, stock = ?, image_url = ?, profit_margin = ?, updated_at = NOW() WHERE id = ?");
            return $stmt->execute([$name, $description, $price_usd, $price_ves, $stock, $image, $profit_margin, $id]);
        } else {
            $stmt = $this->db->prepare("UPDATE products SET name = ?, description = ?, price_usd = ?, price_ves = ?, stock = ?, profit_margin = ?, updated_at = NOW() WHERE id = ?");
            return $stmt->execute([$name, $description, $price_usd, $price_ves, $stock, $profit_margin, $id]);
        }
    }

    // Actualizar stock de un producto
    public function updateProductStock($id, $stock)
    {
        $stmt = $this->db->prepare("UPDATE products SET stock = ?, created_at = NOW() WHERE id = ?");
        return $stmt->execute([$stock, $id]);
    }

    // Eliminar un producto
    public function deleteProduct($id)
    {
        $stmt = $this->db->prepare("DELETE FROM products WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function updateAllPricesBasedOnRate($newRate)
    {
        try {
            $sql = "UPDATE products SET price_ves = price_usd * :rate, updated_at = NOW()";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':rate' => $newRate]);
        } catch (PDOException $e) {
            error_log("Error en actualización masiva: " . $e->getMessage());
            return false;
        }
    }

    // --- NUEVAS FUNCIONES PARA MANEJO DE RECETAS DE VENTA ---

    public function updateProductType($id, $type)
    {
        $stmt = $this->db->prepare("UPDATE products SET product_type = ? WHERE id = ?");
        return $stmt->execute([$type, $id]);
    }

    public function getProductComponents($productId, $depth = 0)
    {
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
                    WHEN pc.component_type = 'product' THEN 0
                END as item_cost
                FROM product_components pc
                LEFT JOIN raw_materials rm ON pc.component_id = rm.id AND pc.component_type = 'raw'
                LEFT JOIN manufactured_products mp ON pc.component_id = mp.id AND pc.component_type = 'manufactured'
                LEFT JOIN products p ON pc.component_id = p.id AND pc.component_type = 'product'
                WHERE pc.product_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId]);
        $components = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // FIX: Para componentes de tipo 'product', calcular su costo real recursivamente
        foreach ($components as &$comp) {
            if ($comp['component_type'] == 'product' && $comp['component_id']) {
                $comp['item_cost'] = $this->calculateProductCost($comp['component_id'], $depth + 1);
            }
        }

        return $components;
    }

    /**
     * Calcula el costo de producción real de un producto sumando recursivamente
     * los costos de sus componentes (evita usar precio de venta)
     */
    public function calculateProductCost($productId, $depth = 0)
    {
        if ($depth > 10)
            return 0; // Prevent Infinite loop

        $components = $this->getProductComponents($productId, $depth);

        // Si tiene componentes (receta), sumar sus costos
        if (!empty($components)) {
            $totalCost = 0;
            foreach ($components as $comp) {
                $itemCost = floatval($comp['item_cost'] ?? 0);
                $totalCost += ($comp['quantity'] * $itemCost);
            }
            return $totalCost;
        }

        // Si NO tiene componentes = Producto de REVENTA (ej: refrescos, snacks)
        $product = $this->getProductById($productId);

        if (!$product) {
            return 0;
        }

        // Usar margen de ganancia para calcular costo
        // Fórmula: Costo = Precio × (1 - margen%)
        // Ejemplo: $1.00 con 20% margen → $1.00 × 0.80 = $0.80 costo
        $price = floatval($product['price_usd'] ?? 0);
        $margin = floatval($product['profit_margin'] ?? 20); // Default 20% si no está definido

        $cost = $price * (1 - ($margin / 100));

        return $cost;
    }

    public function addComponent($productId, $type, $componentId, $qty)
    {
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

    public function removeComponent($id)
    {
        $stmt = $this->db->prepare("DELETE FROM product_components WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // =========================================================
    // CÁLCULO DE STOCK POTENCIAL (RECURSIVO CORREGIDO)
    // =========================================================

    public function getVirtualStock($productId, $depth = 0)
    {
        if ($depth > 10)
            return 0; // Prevent Infinite Loop

        // 1. Obtener la receta
        $components = $this->getProductComponents($productId, $depth);

        // Si no tiene receta (y no es simple), no podemos calcular nada -> 0
        if (empty($components)) {
            return 0;
        }

        $minStock = null; // Empezamos sin límite definido

        foreach ($components as $comp) {
            $qtyNeeded = floatval($comp['quantity']);

            // Evitar división por cero
            if ($qtyNeeded <= 0)
                continue;

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
                // --- CORRECCIÓN AQUÍ: RECURSIVIDAD ---
                // Si es un producto dentro de otro (Combo -> Pizza), averiguamos qué tipo es.
                $subProduct = $this->getProductById($comp['component_id']);

                if ($subProduct) {
                    if ($subProduct['product_type'] === 'simple') {
                        // Si es Coca-Cola, usamos stock físico
                        $currentStock = floatval($subProduct['stock']);
                    } else {
                        // Si es Pizza/Tequeño, calculamos SU propia receta (Recursión)
                        $currentStock = $this->getVirtualStock($comp['component_id'], $depth + 1);
                    }
                } else {
                    $currentStock = 0;
                }
            }

            // 3. ¿Para cuántos platos me alcanza este ingrediente?
            $possibleWithThisItem = floor($currentStock / $qtyNeeded);

            // 4. Lógica del "Cuello de Botella"
            if ($minStock === null || $possibleWithThisItem < $minStock) {
                $minStock = $possibleWithThisItem;
            }
        }

        return ($minStock === null) ? 0 : $minStock;
    }

    // =========================================================
    // GESTIÓN DE EXTRAS VÁLIDOS (CONFIGURACIÓN)
    // =========================================================

    public function getValidExtras($productId)
    {
        $sql = "SELECT pve.*, rm.name, rm.cost_per_unit
                    FROM product_valid_extras pve
                    JOIN raw_materials rm ON pve.raw_material_id = rm.id
                    WHERE pve.product_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addValidExtra($productId, $rawMaterialId, $priceOverride = null)
    {
        // Verificar si ya existe para no duplicar
        $check = $this->db->prepare("SELECT id FROM product_valid_extras WHERE product_id = ? AND raw_material_id = ?");
        $check->execute([$productId, $rawMaterialId]);

        if ($check->fetch()) {
            // Si existe, actualizamos el precio
            $sql = "UPDATE product_valid_extras SET price_override = ? WHERE product_id = ? AND raw_material_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$priceOverride, $productId, $rawMaterialId]);
        } else {
            // Si no, insertamos
            $sql = "INSERT INTO product_valid_extras (product_id, raw_material_id, price_override) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$productId, $rawMaterialId, $priceOverride]);
        }
    }

    public function removeValidExtra($extraId)
    {
        $stmt = $this->db->prepare("DELETE FROM product_valid_extras WHERE id = ?");
        return $stmt->execute([$extraId]);
    }





}
?>