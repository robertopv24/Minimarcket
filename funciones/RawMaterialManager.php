<?php

use Minimarcket\Modules\Inventory\Services\RawMaterialService;

/**
 * @deprecated This class is a legacy proxy. Use Minimarcket\Modules\Inventory\Services\RawMaterialService instead.
 */
class RawMaterialManager
{
    private $service;

    public function __construct($db = null)
    {
        global $app;
        if (isset($app)) {
            $this->service = $app->getContainer()->get(RawMaterialService::class);
        } else {
            throw new \Exception("Application not bootstrapped. Cannot instantiate RawMaterialManager.");
        }
    }

    public function searchMaterials($query = '')
    {
        return $this->service->searchMaterials($query);
    }

    public function getAllMaterials()
    {
        return $this->service->getAllMaterials();
    }

    public function getLowStockMaterials()
    {
        return $this->service->getLowStockMaterials();
    }

    public function getMaterialById($id)
    {
        return $this->service->getMaterialById($id);
    }

    public function createMaterial($name, $unit, $cost, $minStock, $isCookingSupply, $category = 'ingredient')
    {
        return $this->service->createMaterial($name, $unit, $cost, $minStock, $isCookingSupply, $category);
    }

    public function updateMaterial($id, $name, $unit, $minStock, $isCookingSupply)
    {
        return $this->service->updateMaterial($id, $name, $unit, $minStock, $isCookingSupply);
    }

    public function addStock($id, $quantity, $newUnitCost)
    {
        return $this->service->addStock($id, $quantity, $newUnitCost);
    }

    public function deleteMaterial($id)
    {
        return $this->service->deleteMaterial($id);
    }
}