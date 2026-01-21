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
    public function createProduct($name, $description, $price_usd, $price_ves, $stock, $image_url = 'default.jpg', $profit_margin = 20.00, $min_stock = 5)
    {
        $stmt = $this->db->prepare("INSERT INTO products (name, description, price_usd, price_ves, stock, image_url, profit_margin, min_stock, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        return $stmt->execute([$name, $description, $price_usd, $price_ves, $stock, $image_url, $profit_margin, $min_stock]);
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

    // Obtener productos con stock bajo (Dinámico según min_stock)
    public function getLowStockProducts()
    {
        $lowStock = [];

        // A. Productos SIMPLE (Reventa) - Validación directa SQL
        $stmtEx = $this->db->query("SELECT * FROM products WHERE product_type = 'simple' AND stock < min_stock");
        $simpleLow = $stmtEx->fetchAll(PDO::FETCH_ASSOC);
        foreach ($simpleLow as $s) {
            $s['stock_source'] = 'physical';
            $lowStock[] = $s;
        }

        // B. Productos PREPARED/COMPOUND (Cocina/Combos) - Validación Virtual
        $stmtPrep = $this->db->query("SELECT * FROM products WHERE product_type IN ('prepared', 'compound')");
        $prepared = $stmtPrep->fetchAll(PDO::FETCH_ASSOC);

        foreach ($prepared as $p) {
            $analysis = $this->getVirtualStockAnalysis($p['id']);
            $virtualStock = $analysis['max_produceable'];
            $minStock = floatval($p['min_stock']);

            if ($virtualStock < $minStock) {
                // Sobrescribir 'stock' visualmente para que el dashboard muestre la realidad disponible
                $p['stock'] = $virtualStock;
                $p['stock_source'] = 'virtual';
                $p['limiting_component'] = $analysis['limiting_component'];
                $lowStock[] = $p;
            }
        }

        return $lowStock;
    }

    // Actualizar un producto
    public function updateProduct($id, $name, $description, $price_usd, $price_ves, $stock, $image = null, $profit_margin = 20.00, $min_stock = 5)
    {
        if ($image) {
            $stmt = $this->db->prepare("UPDATE products SET name = ?, description = ?, price_usd = ?, price_ves = ?, stock = ?, image_url = ?, profit_margin = ?, min_stock = ?, updated_at = NOW() WHERE id = ?");
            return $stmt->execute([$name, $description, $price_usd, $price_ves, $stock, $image, $profit_margin, $min_stock, $id]);
        } else {
            $stmt = $this->db->prepare("UPDATE products SET name = ?, description = ?, price_usd = ?, price_ves = ?, stock = ?, profit_margin = ?, min_stock = ?, updated_at = NOW() WHERE id = ?");
            return $stmt->execute([$name, $description, $price_usd, $price_ves, $stock, $profit_margin, $min_stock, $id]);
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
                END as item_cost,
                rm.category as item_category
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

    public function updateComponentQuantity($rowId, $newQuantity)
    {
        $stmt = $this->db->prepare("UPDATE product_components SET quantity = ? WHERE id = ?");
        return $stmt->execute([$newQuantity, $rowId]);
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
        $analysis = $this->getVirtualStockAnalysis($productId, $depth);
        return $analysis['max_produceable'];
    }

    public function getVirtualStockAnalysis($productId, $depth = 0)
    {
        if ($depth > 10)
            return ['max_produceable' => 0, 'limiting_component' => null];

        // 1. Obtener la receta
        $components = $this->getProductComponents($productId, $depth);

        // Si no tiene receta (y no es simple), no podemos calcular nada -> 0
        if (empty($components)) {
            return ['max_produceable' => 0, 'limiting_component' => null];
        }

        $minProduceable = null;
        $limitingComponent = null;

        foreach ($components as $comp) {
            $qtyNeeded = floatval($comp['quantity']);

            // Evitar división por cero
            if ($qtyNeeded <= 0)
                continue;

            $currentStock = 0;
            $itemName = 'Componente';
            $itemUnit = 'Und';

            // 2. Averiguar cuánto stock hay de este ingrediente específico
            if ($comp['component_type'] == 'raw') {
                $stmt = $this->db->prepare("SELECT name, stock_quantity, unit FROM raw_materials WHERE id = ?");
                $stmt->execute([$comp['component_id']]);
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                $currentStock = floatval($data['stock_quantity'] ?? 0);
                $itemName = $data['name'] ?? 'Insumo';
                $itemUnit = $data['unit'] ?? 'Und';

            } elseif ($comp['component_type'] == 'manufactured') {
                // Para manufacturados, debemos ver cuánto hay YA producido O cuánto se puede producir
                // En este contexto simplificado para Ventas, usamos lo que hay en STOCK (manufactured_products.stock)
                // Opcional: Podríamos hacer recursividad si quisiéramos "producir al vuelo", pero usualmente
                // la venta consume lo que ya está en nevera.
                $stmt = $this->db->prepare("SELECT name, stock, unit FROM manufactured_products WHERE id = ?");
                $stmt->execute([$comp['component_id']]);
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                $currentStock = floatval($data['stock'] ?? 0);
                $itemName = $data['name'] ?? 'Manufacturado';
                $itemUnit = $data['unit'] ?? 'Und';

            } elseif ($comp['component_type'] == 'product') {
                // Si es un producto dentro de otro (Combo -> Pizza)
                $subProduct = $this->getProductById($comp['component_id']);

                if ($subProduct) {
                    $itemName = $subProduct['name'];
                    if ($subProduct['product_type'] === 'simple') {
                        // Si es Coca-Cola, usamos stock físico
                        $currentStock = floatval($subProduct['stock']);
                    } else {
                        // Si es Pizza/Tequeño, calculamos SU propia receta (Recursión)
                        $subAnalysis = $this->getVirtualStockAnalysis($comp['component_id'], $depth + 1);
                        $currentStock = $subAnalysis['max_produceable'];
                    }
                } else {
                    $currentStock = 0;
                }
            }

            // 3. ¿Para cuántos platos me alcanza este ingrediente?
            $possibleWithThisItem = floor($currentStock / $qtyNeeded);

            // 4. Lógica del "Cuello de Botella"
            if ($minProduceable === null || $possibleWithThisItem < $minProduceable) {
                $minProduceable = $possibleWithThisItem;
                $limitingComponent = [
                    'name' => $itemName,
                    'available' => $currentStock,
                    'unit' => $itemUnit,
                    'required_per_unit' => $qtyNeeded
                ];
            }
        }

        return [
            'max_produceable' => ($minProduceable === null) ? 0 : $minProduceable,
            'limiting_component' => $limitingComponent
        ];
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

    public function addValidExtra($productId, $rawMaterialId, $priceOverride = null, $qtyRequired = 1.00)
    {
        // Verificar si ya existe para no duplicar
        $check = $this->db->prepare("SELECT id FROM product_valid_extras WHERE product_id = ? AND raw_material_id = ?");
        $check->execute([$productId, $rawMaterialId]);

        if ($check->fetch()) {
            // Si existe, actualizamos el precio y la cantidad
            $sql = "UPDATE product_valid_extras SET price_override = ?, quantity_required = ? WHERE product_id = ? AND raw_material_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$priceOverride, $qtyRequired, $productId, $rawMaterialId]);
        } else {
            // Si no, insertamos
            $sql = "INSERT INTO product_valid_extras (product_id, raw_material_id, price_override, quantity_required) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$productId, $rawMaterialId, $priceOverride, $qtyRequired]);
        }
    }

    public function updateValidExtraQuantity($rowId, $priceOverride, $qtyRequired)
    {
        $stmt = $this->db->prepare("UPDATE product_valid_extras SET price_override = ?, quantity_required = ? WHERE id = ?");
        return $stmt->execute([$priceOverride, $qtyRequired, $rowId]);
    }

    public function removeValidExtra($extraId)
    {
        $stmt = $this->db->prepare("DELETE FROM product_valid_extras WHERE id = ?");
        return $stmt->execute([$extraId]);
    }





    // =========================================================
    // GESTIÓN DE CONTORNOS / LADOS VÁLIDOS (OPCIONES)
    // =========================================================

    public function getValidSides($productId)
    {
        $sql = "SELECT pvs.*,
                CASE
                    WHEN pvs.component_type = 'raw' THEN rm.name
                    WHEN pvs.component_type = 'manufactured' THEN mp.name
                    WHEN pvs.component_type = 'product' THEN p.name
                END as item_name,
                CASE
                    WHEN pvs.component_type = 'raw' THEN rm.unit
                    WHEN pvs.component_type = 'manufactured' THEN mp.unit
                    WHEN pvs.component_type = 'product' THEN 'und'
                END as item_unit
                FROM product_valid_sides pvs
                LEFT JOIN raw_materials rm ON pvs.component_id = rm.id AND pvs.component_type = 'raw'
                LEFT JOIN manufactured_products mp ON pvs.component_id = mp.id AND pvs.component_type = 'manufactured'
                LEFT JOIN products p ON pvs.component_id = p.id AND pvs.component_type = 'product'
                WHERE pvs.product_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addValidSide($productId, $type, $componentId, $qty, $priceOverride = 0)
    {
        $sql = "INSERT INTO product_valid_sides (product_id, component_type, component_id, quantity, price_override) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$productId, $type, $componentId, $qty, $priceOverride]);
    }

    public function updateValidSide($sideId, $qty, $priceOverride)
    {
        $stmt = $this->db->prepare("UPDATE product_valid_sides SET quantity = ?, price_override = ? WHERE id = ?");
        return $stmt->execute([$qty, $priceOverride, $sideId]);
    }

    public function removeValidSide($sideId)
    {
        $stmt = $this->db->prepare("DELETE FROM product_valid_sides WHERE id = ?");
        return $stmt->execute([$sideId]);
    }

    public function updateMaxSides($productId, $maxSides)
    {
        $stmt = $this->db->prepare("UPDATE products SET max_sides = ? WHERE id = ?");
        return $stmt->execute([$maxSides, $productId]);
    }


    // =========================================================
    // GESTIÓN DE EMPAQUES (PACKAGING)
    // =========================================================

    public function getProductPackaging($productId)
    {
        $sql = "SELECT pp.*, rm.name, rm.unit, rm.cost_per_unit
                FROM product_packaging pp
                JOIN raw_materials rm ON pp.raw_material_id = rm.id
                WHERE pp.product_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addProductPackaging($productId, $rawMaterialId, $qty)
    {
        // Verificar si ya existe
        $check = $this->db->prepare("SELECT id FROM product_packaging WHERE product_id = ? AND raw_material_id = ?");
        $check->execute([$productId, $rawMaterialId]);

        if ($check->fetch()) {
            $stmt = $this->db->prepare("UPDATE product_packaging SET quantity = quantity + ? WHERE product_id = ? AND raw_material_id = ?");
            return $stmt->execute([$qty, $productId, $rawMaterialId]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO product_packaging (product_id, raw_material_id, quantity) VALUES (?, ?, ?)");
            return $stmt->execute([$productId, $rawMaterialId, $qty]);
        }
    }

    public function updateProductPackaging($packagingId, $qty)
    {
        $stmt = $this->db->prepare("UPDATE product_packaging SET quantity = ? WHERE id = ?");
        return $stmt->execute([$qty, $packagingId]);
    }

    public function removeProductPackaging($packagingId)
    {
        $stmt = $this->db->prepare("DELETE FROM product_packaging WHERE id = ?");
        return $stmt->execute([$packagingId]);
    }

    // =========================================================
    // DUPLICACIÓN DE PRODUCTOS
    // =========================================================

    public function duplicateProduct($productId)
    {
        try {
            $this->db->beginTransaction();

            // 1. Obtener datos originales
            $original = $this->getProductById($productId);
            if (!$original) {
                throw new Exception("Producto no encontrado");
            }

            // 2. Crear copia (Nombre + Copia)
            $newName = $original['name'] . " (Copia)";

            // Usamos la inserción manual para tener control total de los campos
            $sql = "INSERT INTO products (
                name, description, price_usd, price_ves, 
                stock, image_url, profit_margin, product_type, max_sides, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $newName,
                $original['description'],
                $original['price_usd'],
                $original['price_ves'],
                $original['stock'],
                $original['image_url'],
                $original['profit_margin'],
                $original['product_type'],
                $original['max_sides'] ?? 0
            ]);

            $newId = $this->db->lastInsertId();

            // 3. Duplicar receta (Componentes)
            $stmtComps = $this->db->prepare("SELECT * FROM product_components WHERE product_id = ?");
            $stmtComps->execute([$productId]);
            $rawComponents = $stmtComps->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rawComponents as $comp) {
                $sqlAddComp = "INSERT INTO product_components (product_id, component_type, component_id, quantity) VALUES (?, ?, ?, ?)";
                $stmtAdd = $this->db->prepare($sqlAddComp);
                $stmtAdd->execute([$newId, $comp['component_type'], $comp['component_id'], $comp['quantity']]);
            }

            // 4. Duplicar Extras Válidos
            $stmtExtras = $this->db->prepare("SELECT * FROM product_valid_extras WHERE product_id = ?");
            $stmtExtras->execute([$productId]);
            $rawExtras = $stmtExtras->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rawExtras as $extra) {
                $sqlAddExtra = "INSERT INTO product_valid_extras (product_id, raw_material_id, price_override, quantity_required) VALUES (?, ?, ?, ?)";
                $stmtAddEx = $this->db->prepare($sqlAddExtra);
                $stmtAddEx->execute([
                    $newId,
                    $extra['raw_material_id'],
                    $extra['price_override'],
                    $extra['quantity_required'] ?? 1.000000
                ]);
            }

            // 5. Duplicar Contornos (NUEVO)
            $stmtSides = $this->db->prepare("SELECT * FROM product_valid_sides WHERE product_id = ?");
            $stmtSides->execute([$productId]);
            $rawSides = $stmtSides->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rawSides as $side) {
                $sqlAddSide = "INSERT INTO product_valid_sides (product_id, component_type, component_id, quantity, price_override) VALUES (?, ?, ?, ?, ?)";
                $stmtAddSi = $this->db->prepare($sqlAddSide);
                $stmtAddSi->execute([
                    $newId,
                    $side['component_type'],
                    $side['component_id'],
                    $side['quantity'],
                    $side['price_override']
                ]);
            }

            // 6. Duplicar Empaque (NUEVO)
            $stmtPack = $this->db->prepare("SELECT * FROM product_packaging WHERE product_id = ?");
            $stmtPack->execute([$productId]);
            $rawPack = $stmtPack->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rawPack as $pack) {
                $sqlAddPack = "INSERT INTO product_packaging (product_id, raw_material_id, quantity) VALUES (?, ?, ?)";
                $stmtAddPa = $this->db->prepare($sqlAddPack);
                $stmtAddPa->execute([
                    $newId,
                    $pack['raw_material_id'],
                    $pack['quantity']
                ]);
            }

            $this->db->commit();
            return $newId;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error duplicando producto: " . $e->getMessage());
            return false;
        }
    }

    // =========================================================
    // COPIADO DE CONFIGURACIÓN ENTRE PRODUCTOS
    // =========================================================

    public function copyComponents($fromId, $toId)
    {
        $stmt = $this->db->prepare("SELECT * FROM product_components WHERE product_id = ?");
        $stmt->execute([$fromId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            $this->addComponent($toId, $item['component_type'], $item['component_id'], $item['quantity']);
        }
        return true;
    }

    public function copyExtras($fromId, $toId)
    {
        $stmt = $this->db->prepare("SELECT * FROM product_valid_extras WHERE product_id = ?");
        $stmt->execute([$fromId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            $this->addValidExtra($toId, $item['raw_material_id'], $item['price_override'], $item['quantity_required']);
        }
        return true;
    }

    public function copySides($fromId, $toId)
    {
        $stmt = $this->db->prepare("SELECT * FROM product_valid_sides WHERE product_id = ?");
        $stmt->execute([$fromId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            // Verificar si ya existe en destino
            $check = $this->db->prepare("SELECT id FROM product_valid_sides WHERE product_id = ? AND component_type = ? AND component_id = ?");
            $check->execute([$toId, $item['component_type'], $item['component_id']]);
            $existing = $check->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Si existe, actualizar esa fila
                $this->updateValidSide($existing['id'], $item['quantity'], $item['price_override']);
            } else {
                // Si no, agregar nueva
                $this->addValidSide($toId, $item['component_type'], $item['component_id'], $item['quantity'], $item['price_override']);
            }
        }
        return true;
    }

    public function copyPackaging($fromId, $toId)
    {
        $stmt = $this->db->prepare("SELECT * FROM product_packaging WHERE product_id = ?");
        $stmt->execute([$fromId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            $this->addProductPackaging($toId, $item['raw_material_id'], $item['quantity']);
        }
        return true;
    }

}
?>