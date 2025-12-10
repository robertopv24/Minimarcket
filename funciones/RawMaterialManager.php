<?php

class RawMaterialManager
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Búsqueda de insumos
    public function searchMaterials($query = '')
    {
        $sql = "SELECT * FROM raw_materials";
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

    // Obtener todos los insumos
    public function getAllMaterials()
    {
        return $this->searchMaterials();
    }

    // Obtener insumos bajos de stock (Alerta)
    public function getLowStockMaterials()
    {
        $stmt = $this->db->query("SELECT * FROM raw_materials WHERE stock_quantity <= min_stock ORDER BY stock_quantity ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener uno por ID
    public function getMaterialById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM raw_materials WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Crear nuevo insumo
    public function createMaterial($name, $unit, $cost, $minStock, $isCookingSupply)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO raw_materials (name, unit, cost_per_unit, min_stock, is_cooking_supply, stock_quantity) VALUES (?, ?, ?, ?, ?, 0)");
            return $stmt->execute([$name, $unit, $cost, $minStock, $isCookingSupply]);
        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }
    }

    // Actualizar datos básicos
    public function updateMaterial($id, $name, $unit, $minStock, $isCookingSupply)
    {
        $stmt = $this->db->prepare("UPDATE raw_materials SET name = ?, unit = ?, min_stock = ?, is_cooking_supply = ? WHERE id = ?");
        return $stmt->execute([$name, $unit, $minStock, $isCookingSupply, $id]);
    }

    // Sumar Stock (Compra) y actualizar costo promedio
    public function addStock($id, $quantity, $newUnitCost)
    {
        // Obtenemos datos actuales
        $current = $this->getMaterialById($id);
        if (!$current)
            return false;

        $oldStock = floatval($current['stock_quantity']);
        $oldCost = floatval($current['cost_per_unit']);

        $newStock = $oldStock + $quantity;

        // Cálculo de Costo Promedio Ponderado
        // ((StockViejo * CostoViejo) + (CantidadNueva * CostoNuevo)) / TotalNuevo
        if ($newStock > 0) {
            $avgCost = (($oldStock * $oldCost) + ($quantity * $newUnitCost)) / $newStock;
        } else {
            $avgCost = $newUnitCost;
        }

        $stmt = $this->db->prepare("UPDATE raw_materials SET stock_quantity = ?, cost_per_unit = ? WHERE id = ?");
        return $stmt->execute([$newStock, $avgCost, $id]);
    }

    // Eliminar
    public function deleteMaterial($id)
    {
        // Verificar si se usa en recetas antes de borrar (Integridad Referencial)
        $check = $this->db->prepare("SELECT COUNT(*) FROM production_recipes WHERE raw_material_id = ?");
        $check->execute([$id]);
        if ($check->fetchColumn() > 0) {
            return "No se puede eliminar: Este insumo es parte de una receta de producción.";
        }

        $check2 = $this->db->prepare("SELECT COUNT(*) FROM product_components WHERE component_type = 'raw' AND component_id = ?");
        $check2->execute([$id]);
        if ($check2->fetchColumn() > 0) {
            return "No se puede eliminar: Este insumo es parte de un Combo de venta.";
        }

        $stmt = $this->db->prepare("DELETE FROM raw_materials WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
?>