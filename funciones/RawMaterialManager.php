<?php

use Minimarcket\Core\Container;
use Minimarcket\Modules\SupplyChain\Services\RawMaterialService;

/**
 * @deprecated This class is a legacy proxy. Use Minimarcket\Modules\SupplyChain\Services\RawMaterialService instead.
 */
class RawMaterialManager
{
    private $service;

    public function __construct($db = null)
    {
        $container = Container::getInstance();
        try {
            $this->service = $container->get(RawMaterialService::class);
        } catch (Exception $e) {
            $this->service = new RawMaterialService($db);
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

    public function createMaterial($name, $unit, $cost, $minStock, $isCookingSupply)
    {
        return $this->service->createMaterial($name, $unit, $cost, $minStock, $isCookingSupply);
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