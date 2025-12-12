<?php

namespace Minimarcket\Modules\Manufacturing\Services;

use Minimarcket\Core\Database;
use PDO;
use Exception;

class ProductionService
{
    private $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
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

    public function removeIngredientFromRecipe($recipeId)
    {
        $stmt = $this->db->prepare("DELETE FROM production_recipes WHERE id = ?");
        return $stmt->execute([$recipeId]);
    }

    public function registerProduction($manufId, $qtyProduced, $userId)
    {
        try {
            $this->db->beginTransaction();

            $recipe = $this->getRecipe($manufId);
            if (empty($recipe)) {
                throw new Exception("Este producto no tiene receta definida.");
            }

            $totalMaterialCost = 0;

            foreach ($recipe as $item) {
                $amountNeeded = $item['quantity_required'] * $qtyProduced;

                $matStmt = $this->db->prepare("SELECT cost_per_unit FROM raw_materials WHERE id = ?");
                $matStmt->execute([$item['raw_material_id']]);
                $unitCost = $matStmt->fetchColumn();

                $totalMaterialCost += ($unitCost * $amountNeeded);

                $upd = $this->db->prepare("UPDATE raw_materials SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $upd->execute([$amountNeeded, $item['raw_material_id']]);
            }

            $grandTotalCost = $totalMaterialCost;
            $unitCost = ($qtyProduced > 0) ? ($grandTotalCost / $qtyProduced) : 0;

            $prodStmt = $this->db->prepare("SELECT stock, unit_cost_average FROM manufactured_products WHERE id = ?");
            $prodStmt->execute([$manufId]);
            $currentProd = $prodStmt->fetch(PDO::FETCH_ASSOC);

            $oldStock = floatval($currentProd['stock']);
            $oldAvgCost = floatval($currentProd['unit_cost_average']);

            $newStock = $oldStock + $qtyProduced;

            if ($newStock > 0) {
                $newAvgCost = (($oldStock * $oldAvgCost) + $grandTotalCost) / $newStock;
            } else {
                $newAvgCost = $unitCost;
            }

            $updProd = $this->db->prepare("UPDATE manufactured_products SET stock = ?, unit_cost_average = ?, last_production_date = NOW() WHERE id = ?");
            $updProd->execute([$newStock, $newAvgCost, $manufId]);

            $log = $this->db->prepare("INSERT INTO production_orders (manufactured_product_id, quantity_produced, labor_cost, total_cost, created_by) VALUES (?, ?, 0, ?, ?)");
            $log->execute([$manufId, $qtyProduced, $grandTotalCost, $userId]);

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            if ($this->db->inTransaction())
                $this->db->rollBack();
            return "Error: " . $e->getMessage();
        }
    }
}
