<?php

use Minimarcket\Core\Container;
use Minimarcket\Modules\SupplyChain\Services\SupplierService;

/**
 * @deprecated This class is a legacy proxy. Use Minimarcket\Modules\SupplyChain\Services\SupplierService instead.
 */
class SupplierManager
{
    private $service;

    public function __construct($db = null)
    {
        global $app;
        if (isset($app)) {
            $this->service = $app->getContainer()->get(SupplierService::class);
        } else {
            $this->service = new SupplierService($db);
        }
    }

    public function addSupplier($name, $contactPerson, $email, $phone, $address)
    {
        return $this->service->addSupplier($name, $contactPerson, $email, $phone, $address);
    }

    public function updateSupplier($id, $name, $contactPerson, $email, $phone, $address)
    {
        return $this->service->updateSupplier($id, $name, $contactPerson, $email, $phone, $address);
    }

    public function deleteSupplier($id)
    {
        return $this->service->deleteSupplier($id);
    }

    public function getSupplierById($id)
    {
        return $this->service->getSupplierById($id);
    }

    public function searchSuppliers($query = '')
    {
        return $this->service->searchSuppliers($query);
    }

    public function getAllSuppliers()
    {
        return $this->service->getAllSuppliers();
    }
}