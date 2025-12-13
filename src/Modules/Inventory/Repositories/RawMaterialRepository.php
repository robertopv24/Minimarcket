<?php

namespace Minimarcket\Modules\Inventory\Repositories;

use Minimarcket\Core\Database\BaseRepository;

/**
 * Class RawMaterialRepository
 * 
 * Repositorio para gestión de materias primas e insumos.
 */
class RawMaterialRepository extends BaseRepository
{
    protected string $table = 'raw_materials';

    /**
     * Busca materias primas por nombre
     */
    public function search(string $query): array
    {
        if (empty($query)) {
            return $this->all();
        }

        return $this->newQuery()
            ->where('name', 'LIKE', "%{$query}%")
            ->orderBy('name', 'ASC')
            ->get();
    }

    /**
     * Obtiene materias primas con stock bajo (alerta)
     */
    public function getLowStock(): array
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->query("SELECT * FROM {$this->table} WHERE stock_quantity <= min_stock ORDER BY stock_quantity ASC");
        return $stmt->fetchAll();
    }

    /**
     * Incrementa el stock de un insumo y actualiza su costo unitario promedio
     */
    public function addStock(int $id, float $quantity, float $newUnitCost): bool
    {
        $current = $this->find($id);
        if (!$current)
            return false;

        $oldStock = floatval($current['stock_quantity']);
        $oldCost = floatval($current['cost_per_unit']);

        $newStock = $oldStock + $quantity;
        $totalValue = ($oldStock * $oldCost) + ($quantity * $newUnitCost);
        $newAvgCost = ($newStock > 0) ? ($totalValue / $newStock) : $oldCost;

        return $this->update($id, [
            'stock_quantity' => $newStock,
            'cost_per_unit' => $newAvgCost
        ]);
    }

    /**
     * Reduce el stock de un insumo (consumo) sin afectar el costo unitario
     */
    public function reduceStock(int $id, float $quantity): bool
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare("UPDATE {$this->table} SET stock_quantity = stock_quantity - ? WHERE id = ?");
        return $stmt->execute([$quantity, $id]);
    }

    /**
     * Verifica si un material está en uso en recetas
     */
    public function isUsedInRecipes(int $id): bool
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM production_recipes WHERE raw_material_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Verifica si un material está en uso en combos
     */
    public function isUsedInCombos(int $id): bool
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM product_components WHERE component_type = 'raw' AND component_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return ($result['count'] ?? 0) > 0;
    }
}
