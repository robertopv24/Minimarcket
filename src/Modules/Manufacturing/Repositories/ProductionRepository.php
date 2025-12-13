<?php

namespace Minimarcket\Modules\Manufacturing\Repositories;

use Minimarcket\Core\Database\BaseRepository;
use PDO;

class ProductionRepository extends BaseRepository
{
    protected string $table = 'manufactured_products';

    /**
     * Busca productos manufacturados por nombre
     */
    public function search(string $query = ''): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($query)) {
            $sql .= " WHERE name LIKE ?";
            $params[] = "%$query%";
        }

        $sql .= " ORDER BY name ASC";

        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Elimina un producto manufacturado y sus recetas asociadas
     */
    public function deleteDefinition(int $id): bool
    {
        $pdo = $this->connection->getConnection();

        // Eliminar recetas primero (aunque FK debería manejarlo si está configurada, es más seguro explícito)
        $stmtRecipe = $pdo->prepare("DELETE FROM production_recipes WHERE manufactured_product_id = ?");
        $stmtRecipe->execute([$id]);

        return $this->delete($id);
    }

    /**
     * Obtiene la receta de un producto manufacturado con detalles de insumos
     */
    public function getRecipe(int $manufacturedId): array
    {
        $sql = "SELECT pr.*, rm.name as material_name, rm.unit as material_unit
                FROM production_recipes pr
                JOIN raw_materials rm ON pr.raw_material_id = rm.id
                WHERE pr.manufactured_product_id = ?";

        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$manufacturedId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica si un ingrediente ya existe en la receta
     */
    public function checkIngredientExists(int $manufId, int $rawId): ?int
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare("SELECT id FROM production_recipes WHERE manufactured_product_id = ? AND raw_material_id = ?");
        $stmt->execute([$manufId, $rawId]);
        return $stmt->fetchColumn() ?: null;
    }

    /**
     * Actualiza la cantidad de un ingrediente en una receta
     */
    public function updateIngredientQuantity(int $manufId, int $rawId, float $qty): bool
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare("UPDATE production_recipes SET quantity_required = quantity_required + ? WHERE manufactured_product_id = ? AND raw_material_id = ?");
        return $stmt->execute([$qty, $manufId, $rawId]);
    }

    /**
     * Agrega un nuevo ingrediente a la receta
     */
    public function addIngredient(int $manufId, int $rawId, float $qty): bool
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare("INSERT INTO production_recipes (manufactured_product_id, raw_material_id, quantity_required) VALUES (?, ?, ?)");
        return $stmt->execute([$manufId, $rawId, $qty]);
    }

    /**
     * Elimina un ingrediente de una receta por ID de receta
     */
    public function removeIngredient(int $recipeId): bool
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare("DELETE FROM production_recipes WHERE id = ?");
        return $stmt->execute([$recipeId]);
    }

    /**
     * Actualiza stock y costo promedio de un producto manufacturado
     */
    public function updateStockAndCost(int $id, float $newStock, float $newAvgCost): bool
    {
        return $this->update($id, [
            'stock' => $newStock,
            'unit_cost_average' => $newAvgCost,
            'last_production_date' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Registra una orden de producción completada
     */
    public function logProductionOrder(array $data): bool
    {
        $cols = implode(", ", array_keys($data));
        $vals = implode(", ", array_fill(0, count($data), "?"));

        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare("INSERT INTO production_orders ($cols) VALUES ($vals)");
        return $stmt->execute(array_values($data));
    }

    // Métodos para transacciones si se necesitan explícitos aparte de la conexión
    public function beginTransaction(): void
    {
        $this->connection->getConnection()->beginTransaction();
    }
    public function commit(): void
    {
        $this->connection->getConnection()->commit();
    }
    public function rollBack(): void
    {
        $this->connection->getConnection()->rollBack();
    }
}
