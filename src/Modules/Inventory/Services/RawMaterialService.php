<?php

namespace Minimarcket\Modules\Inventory\Services;

use Minimarcket\Modules\Inventory\Repositories\RawMaterialRepository;
use Exception;

/**
 * Class RawMaterialService
 * 
 * Servicio de lógica de negocio para materias primas e insumos.
 */
class RawMaterialService
{
    protected RawMaterialRepository $repository;

    public function __construct(RawMaterialRepository $repository)
    {
        $this->repository = $repository;
    }

    public function searchMaterials(string $query = ''): array
    {
        return $this->repository->search($query);
    }

    public function getAllMaterials(): array
    {
        return $this->repository->all();
    }

    public function getLowStockMaterials(): array
    {
        return $this->repository->getLowStock();
    }

    public function getMaterialById(int $id): ?array
    {
        return $this->repository->find($id);
    }

    public function createMaterial(string $name, string $unit, float $cost, float $minStock, int $isCookingSupply, string $category = 'ingredient'): string
    {
        return $this->repository->create([
            'name' => $name,
            'unit' => $unit,
            'cost_per_unit' => $cost,
            'min_stock' => $minStock,
            'is_cooking_supply' => $isCookingSupply,
            'category' => $category,
            'stock_quantity' => 0
        ]);
    }

    public function updateMaterial(int $id, string $name, string $unit, float $minStock, int $isCookingSupply): bool
    {
        return $this->repository->update($id, [
            'name' => $name,
            'unit' => $unit,
            'min_stock' => $minStock,
            'is_cooking_supply' => $isCookingSupply
        ]);
    }

    public function addStock(int $id, float $quantity, float $newUnitCost): bool
    {
        return $this->repository->addStock($id, $quantity, $newUnitCost);
    }

    public function reduceStock(int $id, float $quantity, string $reason = ''): bool
    {
        // TODO: Podríamos loguear la razón del consumo en una tabla de movimientos de inventario futura
        return $this->repository->reduceStock($id, $quantity);
    }

    public function deleteMaterial(int $id): string|bool
    {
        // Verificar integridad referencial
        if ($this->repository->isUsedInRecipes($id)) {
            return "No se puede eliminar: Este insumo es parte de una receta de producción.";
        }

        if ($this->repository->isUsedInCombos($id)) {
            return "No se puede eliminar: Este insumo es parte de un Combo de venta.";
        }

        return $this->repository->delete($id);
    }
}
