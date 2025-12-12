<?php
use Minimarcket\Core\Container;
use Minimarcket\Modules\Manufacturing\Services\ProductionService;

/**
 * @deprecated This class is a legacy proxy. Use Minimarcket\Modules\Manufacturing\Services\ProductionService instead.
 */
class ProductionManager
{
    private $service;

    public function __construct($db = null)
    {
        $container = Container::getInstance();
        try {
            $this->service = $container->get(ProductionService::class);
        } catch (Exception $e) {
            $this->service = new ProductionService($db);
        }
    }

    public function searchManufacturedProducts($query = '')
    {
        return $this->service->searchManufacturedProducts($query);
    }

    public function getAllManufactured()
    {
        return $this->service->getAllManufactured();
    }

    public function createManufacturedProduct($name, $unit)
    {
        return $this->service->createManufacturedProduct($name, $unit);
    }

    public function deleteManufacturedProduct($id)
    {
        return $this->service->deleteManufacturedProduct($id);
    }

    public function getRecipe($manufacturedId)
    {
        return $this->service->getRecipe($manufacturedId);
    }

    public function addIngredientToRecipe($manufId, $rawId, $qty)
    {
        return $this->service->addIngredientToRecipe($manufId, $rawId, $qty);
    }

    public function removeIngredientFromRecipe($recipeId)
    {
        return $this->service->removeIngredientFromRecipe($recipeId);
    }

    public function registerProduction($manufId, $qtyProduced, $userId)
    {
        return $this->service->registerProduction($manufId, $qtyProduced, $userId);
    }
}