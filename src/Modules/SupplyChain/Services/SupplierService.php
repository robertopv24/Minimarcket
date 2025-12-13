<?php

namespace Minimarcket\Modules\SupplyChain\Services;

use Minimarcket\Modules\SupplyChain\Repositories\SupplierRepository;

class SupplierService
{
    private SupplierRepository $repository;

    public function __construct(SupplierRepository $repository)
    {
        $this->repository = $repository;
    }

    public function addSupplier($name, $contactPerson, $email, $phone, $address)
    {
        return $this->repository->create([
            'name' => $name,
            'contact_person' => $contactPerson,
            'email' => $email,
            'phone' => $phone,
            'address' => $address
        ]);
    }

    public function updateSupplier($id, $name, $contactPerson, $email, $phone, $address)
    {
        return $this->repository->update($id, [
            'name' => $name,
            'contact_person' => $contactPerson,
            'email' => $email,
            'phone' => $phone,
            'address' => $address
        ]);
    }

    public function deleteSupplier($id)
    {
        return $this->repository->delete($id);
    }

    public function getSupplierById($id)
    {
        return $this->repository->find($id);
    }

    public function searchSuppliers($query = '')
    {
        return $this->repository->search($query);
    }

    public function getAllSuppliers()
    {
        return $this->repository->all();
    }
}
