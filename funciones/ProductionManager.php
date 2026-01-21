<?php

class ProductionManager
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function searchManufacturedProducts($query = '')
    {
        $sql = "SELECT * FROM manufactured_products";
        $params = [];

        if (!empty($query)) {
            $sql .= " WHERE name LIKE ?";
            $params = ["%$query%"];
        }

        $sql .= " ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllManufactured()
    {
        return $this->searchManufacturedProducts();
    }

    public function createManufacturedProduct($name, $unit, $minStock = 0)
    {
        $stmt = $this->db->prepare("INSERT INTO manufactured_products (name, unit, stock, min_stock) VALUES (?, ?, 0, ?)");
        return $stmt->execute([$name, $unit, $minStock]);
    }

    public function updateManufacturedProduct($id, $name, $unit, $minStock)
    {
        $stmt = $this->db->prepare("UPDATE manufactured_products SET name = ?, unit = ?, min_stock = ? WHERE id = ?");
        return $stmt->execute([$name, $unit, $minStock, $id]);
    }

    public function getLowStockManufactured()
    {
        // Obtener todos los productos manufacturados
        $stmt = $this->db->query("SELECT * FROM manufactured_products ORDER BY name ASC");
        $all = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $lowStockItems = [];

        foreach ($all as $p) {
            // Calcular stock virtual y obtener el ingrediente limitante
            $analysis = $this->getVirtualStockAnalysis($p['id']);
            $virtualStock = $analysis['max_produceable'];
            $minStock = floatval($p['min_stock']);

            // Si el stock posible es MENOR al mínimo, es alerta
            // Si min_stock es 0, usamos un umbral por defecto (ej: 2) para alertar que se está agotando algo "crítico"
            $alertThreshold = ($minStock > 0) ? $minStock : 3;

            if ($virtualStock < $alertThreshold) {
                // Inyectamos el valor calculado para que el dashboard lo muestre
                $p['virtual_stock'] = $virtualStock;
                $p['display_stock'] = $virtualStock; // Para facilitar uso en vista
                // Agregamos info del cuello de botella
                $p['limiting_ingredient'] = $analysis['limiting_ingredient'];
                $lowStockItems[] = $p;
            }
        }

        return $lowStockItems;
    }

    public function getMaxProduceableQuantity($manufId)
    {
        $analysis = $this->getVirtualStockAnalysis($manufId);
        return $analysis['max_produceable'];
    }

    public function getVirtualStockAnalysis($manufId)
    {
        $recipe = $this->getRecipe($manufId);

        // Si no tiene receta, nos basamos en stock físico (si aplica)
        if (empty($recipe)) {
            $stmt = $this->db->prepare("SELECT stock FROM manufactured_products WHERE id = ?");
            $stmt->execute([$manufId]);
            return [
                'max_produceable' => floatval($stmt->fetchColumn() ?: 0),
                'limiting_ingredient' => null
            ];
        }

        $minProduceable = null;
        $limitingIngredient = null;

        foreach ($recipe as $item) {
            $requiredQty = floatval($item['quantity_required']);
            if ($requiredQty <= 0)
                continue;

            // Obtener stock del ingrediente (Raw Material)
            $stmt = $this->db->prepare("SELECT name, stock_quantity, unit FROM raw_materials WHERE id = ?");
            $stmt->execute([$item['raw_material_id']]);
            $raw = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$raw)
                continue;

            $availableQty = floatval($raw['stock_quantity']);
            $possible = floor($availableQty / $requiredQty);

            if ($minProduceable === null || $possible < $minProduceable) {
                $minProduceable = $possible;
                $limitingIngredient = [
                    'name' => $raw['name'],
                    'available' => $availableQty,
                    'unit' => $raw['unit'],
                    'required_per_unit' => $requiredQty
                ];
            }
        }

        return [
            'max_produceable' => ($minProduceable === null) ? 0 : $minProduceable,
            'limiting_ingredient' => $limitingIngredient
        ];
    }

    public function deleteManufacturedProduct($id)
    {
        $this->db->prepare("DELETE FROM production_recipes WHERE manufactured_product_id = ?")->execute([$id]);
        $stmt = $this->db->prepare("DELETE FROM manufactured_products WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getRecipe($manufacturedId)
    {
        $sql = "SELECT pr.*, rm.name as material_name, rm.unit as material_unit
                FROM production_recipes pr
                JOIN raw_materials rm ON pr.raw_material_id = rm.id
                WHERE pr.manufactured_product_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$manufacturedId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addIngredientToRecipe($manufId, $rawId, $qty)
    {
        $check = $this->db->prepare("SELECT id FROM production_recipes WHERE manufactured_product_id = ? AND raw_material_id = ?");
        $check->execute([$manufId, $rawId]);

        if ($check->fetch()) {
            $stmt = $this->db->prepare("UPDATE production_recipes SET quantity_required = quantity_required + ? WHERE manufactured_product_id = ? AND raw_material_id = ?");
            return $stmt->execute([$qty, $manufId, $rawId]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO production_recipes (manufactured_product_id, raw_material_id, quantity_required) VALUES (?, ?, ?)");
            return $stmt->execute([$manufId, $rawId, $qty]);
        }
    }


    public function updateRecipeIngredientQuantity($recipeId, $newQty)
    {
        $stmt = $this->db->prepare("UPDATE production_recipes SET quantity_required = ? WHERE id = ?");
        return $stmt->execute([$newQty, $recipeId]);
    }

    public function replaceRecipe($manufId, $ingredients)
    {
        try {
            $this->db->beginTransaction();

            // 1. Eliminar receta anterior
            $stmtDel = $this->db->prepare("DELETE FROM production_recipes WHERE manufactured_product_id = ?");
            $stmtDel->execute([$manufId]);

            // 2. Insertar nuevos ingredientes
            $stmtAdd = $this->db->prepare("INSERT INTO production_recipes (manufactured_product_id, raw_material_id, quantity_required) VALUES (?, ?, ?)");
            foreach ($ingredients as $ing) {
                $stmtAdd->execute([$manufId, $ing['raw_id'], $ing['qty']]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error reemplazando receta: " . $e->getMessage());
            return false;
        }
    }

    public function removeIngredientFromRecipe($recipeId)
    {
        $stmt = $this->db->prepare("DELETE FROM production_recipes WHERE id = ?");
        return $stmt->execute([$recipeId]);
    }

    // --- VERSIÓN SIMPLIFICADA (SIN MANO DE OBRA) ---
    public function registerProduction($manufId, $qtyProduced, $userId)
    {
        try {
            $this->db->beginTransaction();

            $recipe = $this->getRecipe($manufId);
            if (empty($recipe)) {
                throw new Exception("Este producto no tiene receta definida.");
            }

            $totalMaterialCost = 0;

            // 1. Descontar materias primas y calcular costo material
            foreach ($recipe as $item) {
                $amountNeeded = $item['quantity_required'] * $qtyProduced;

                $matStmt = $this->db->prepare("SELECT cost_per_unit FROM raw_materials WHERE id = ?");
                $matStmt->execute([$item['raw_material_id']]);
                $unitCost = $matStmt->fetchColumn();

                $totalMaterialCost += ($unitCost * $amountNeeded);

                $upd = $this->db->prepare("UPDATE raw_materials SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $upd->execute([$amountNeeded, $item['raw_material_id']]);
            }

            // 2. Costo Total es puramente Material
            $grandTotalCost = $totalMaterialCost;
            // Costo Unitario = Costo Total Materiales / Cantidad Producida
            $unitCost = ($qtyProduced > 0) ? ($grandTotalCost / $qtyProduced) : 0;

            // 3. Sumar al Stock y Promediar Costo
            $prodStmt = $this->db->prepare("SELECT stock, unit_cost_average FROM manufactured_products WHERE id = ?");
            $prodStmt->execute([$manufId]);
            $currentProd = $prodStmt->fetch(PDO::FETCH_ASSOC);

            $oldStock = floatval($currentProd['stock']);
            $oldAvgCost = floatval($currentProd['unit_cost_average']);

            $newStock = $oldStock + $qtyProduced;

            // Promedio Ponderado
            if ($newStock > 0) {
                $newAvgCost = (($oldStock * $oldAvgCost) + $grandTotalCost) / $newStock;
            } else {
                $newAvgCost = $unitCost;
            }

            $updProd = $this->db->prepare("UPDATE manufactured_products SET stock = ?, unit_cost_average = ?, last_production_date = NOW() WHERE id = ?");
            $updProd->execute([$newStock, $newAvgCost, $manufId]);

            // 4. Guardar Historial (Labor Cost = 0)
            $log = $this->db->prepare("INSERT INTO production_orders (manufactured_product_id, quantity_produced, labor_cost, total_cost, created_by) VALUES (?, ?, 0, ?, ?)");
            $log->execute([$manufId, $qtyProduced, $grandTotalCost, $userId]);

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            return "Error: " . $e->getMessage();
        }
    }



}
?>