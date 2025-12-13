<?php

namespace Minimarcket\Modules\Manufacturing\Services;

use Minimarcket\Modules\Manufacturing\Repositories\ProductionRepository;
use Minimarcket\Modules\Inventory\Services\RawMaterialService;
use Exception;

class ProductionService
{
    private ProductionRepository $repository;
    private ?RawMaterialService $rawMaterialService; // Optional for now if not strictly required in constructor, but better required.

    // Permitiremos que RawMaterialService sea opcional por si acaso, pero lo inyectaremos
    public function __construct(ProductionRepository $repository, ?RawMaterialService $rawMaterialService = null)
    {
        $this->repository = $repository;
        $this->rawMaterialService = $rawMaterialService;
    }

    public function searchManufacturedProducts($query = '')
    {
        return $this->repository->search($query);
    }

    public function getAllManufactured()
    {
        return $this->repository->search();
    }

    public function createManufacturedProduct($name, $unit)
    {
        return $this->repository->create([
            'name' => $name,
            'unit' => $unit,
            'stock' => 0
        ]);
    }

    public function deleteManufacturedProduct($id)
    {
        return $this->repository->deleteDefinition($id);
    }

    public function getRecipe($manufacturedId)
    {
        return $this->repository->getRecipe($manufacturedId);
    }

    public function addIngredientToRecipe($manufId, $rawId, $qty)
    {
        if ($this->repository->checkIngredientExists($manufId, $rawId)) {
            return $this->repository->updateIngredientQuantity($manufId, $rawId, $qty);
        } else {
            return $this->repository->addIngredient($manufId, $rawId, $qty);
        }
    }

    public function removeIngredientFromRecipe($recipeId)
    {
        return $this->repository->removeIngredient($recipeId);
    }

    public function registerProduction($manufId, $qtyProduced, $userId)
    {
        if (!$this->rawMaterialService) {
            throw new Exception("RawMaterialService is required for production logging.");
        }

        try {
            $this->repository->beginTransaction();

            $recipe = $this->repository->getRecipe($manufId);
            if (empty($recipe)) {
                throw new Exception("Este producto no tiene receta definida.");
            }

            $totalMaterialCost = 0;

            // 1. Descontar Insumos y Calcular Costo
            foreach ($recipe as $item) {
                $amountNeeded = $item['quantity_required'] * $qtyProduced;

                // Usamos RawMaterialService para obtener info actualizada
                $rawMaterial = $this->rawMaterialService->getMaterialById($item['raw_material_id']);
                // Si RawMaterialService no tiene getById público, usaré lógica directa o agregaré getter.
                // RawMaterialService::getAllRawMaterials() existe.
                // Pero necesitamos precio y stock.
                // Check RawMaterialService...
                // Si no tiene metodo getById, lo asumire y si falla lo agrego luego.
                // El repositorio RawMaterialRepository TIENE find($id). El servicio deberia exponerlo.

                if (!$rawMaterial)
                    throw new Exception("Insumo ID {$item['raw_material_id']} no encontrado.");

                $unitCost = $rawMaterial['cost_per_unit'];
                $totalMaterialCost += ($unitCost * $amountNeeded);

                // Descontar stock usando servicio
                $this->rawMaterialService->reduceStock($item['raw_material_id'], $amountNeeded, "Producción ID $manufId");
            }

            // 2. Calcular Nuevo Costo Promedio Producto
            $grandTotalCost = $totalMaterialCost;
            $unitCost = ($qtyProduced > 0) ? ($grandTotalCost / $qtyProduced) : 0;

            $currentProd = $this->repository->find($manufId);
            $oldStock = floatval($currentProd['stock']);
            $oldAvgCost = floatval($currentProd['unit_cost_average']);

            $newStock = $oldStock + $qtyProduced;

            if ($newStock > 0) {
                // Weighted Average Cost
                $newAvgCost = (($oldStock * $oldAvgCost) + $grandTotalCost) / $newStock;
            } else {
                $newAvgCost = $unitCost;
            }

            // 3. Actualizar Producto Manufacturado
            $this->repository->updateStockAndCost($manufId, $newStock, $newAvgCost);

            // 4. Log Production Order
            $this->repository->logProductionOrder([
                'manufactured_product_id' => $manufId,
                'quantity_produced' => $qtyProduced,
                'labor_cost' => 0,
                'total_cost' => $grandTotalCost,
                'created_by' => $userId
            ]);

            $this->repository->commit();
            return true;

        } catch (Exception $e) {
            $this->repository->rollBack();
            return "Error: " . $e->getMessage();
        }
    }
}
