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

    public function createManufacturedProduct($name, $unit)
    {
        $stmt = $this->db->prepare("INSERT INTO manufactured_products (name, unit, stock) VALUES (?, ?, 0)");
        return $stmt->execute([$name, $unit]);
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