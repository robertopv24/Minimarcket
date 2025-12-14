<?php

namespace Minimarcket\Modules\SupplyChain\Controllers;

use Minimarcket\Modules\SupplyChain\Services\SupplierService;

class SupplierController
{
    protected SupplierService $service;

    public function __construct(SupplierService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        // LEGACY BRIDGE: Importar variables globales necesarias para las vistas viejas (header/footer)
        global $config;

        $search = $_GET['search'] ?? '';

        // La lógica de negocio está en el Servicio (que llama al Repositorio SaaS-ready)
        if (!empty($search)) {
            $suppliers = $this->service->searchSuppliers($search);
        } else {
            $suppliers = $this->service->getAllSuppliers();
        }

        // Renderizar Vista
        // En una app frameworks real usaríamos un ViewRenderer, aquí hacemos include simple y limpio.
        // Pasamos variables a la vista
        $viewData = [
            'suppliers' => $suppliers,
            'search' => $search
        ];

        // Extraemos variables para que la vista las use directamente ($suppliers, $search)
        extract($viewData);

        // Path a la vista
        require __DIR__ . '/../../../../templates/views/supply_chain/supplier_list.php';
    }

    public function create()
    {
        global $config;
        require __DIR__ . '/../../../../templates/views/supply_chain/supplier_form.php';
    }

    public function store()
    {
        // Validación básica
        $name = $_POST['name'] ?? '';
        $contact = $_POST['contact_person'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';

        if (empty($name)) {
            // Manejo de error simple por ahora
            die("El nombre es requerido");
        }

        // Llamar al servicio SaaS-ready
        $this->service->addSupplier($name, $contact, $email, $phone, $address);

        // Redireccionar
        header('Location: /admin/suppliers');
        exit;
    }

    public function edit()
    {
        global $config;
        $id = $_GET['id'] ?? 0;

        // El repositorio filtra por tenant_id, así que si el ID no es de este tenant, retorna null.
        // Aislamiento SaaS automático.
        $supplier = $this->service->getSupplierById($id);

        if (!$supplier) {
            die("Proveedor no encontrado o acceso denegado.");
        }

        require __DIR__ . '/../../../../templates/views/supply_chain/supplier_form.php';
    }

    public function update()
    {
        $id = $_GET['id'] ?? 0;
        if (!$id) {
            die("ID inválido");
        }

        // Validación básica
        $name = $_POST['name'] ?? '';
        $contact = $_POST['contact_person'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';

        if (empty($name)) {
            die("El nombre es requerido");
        }

        // Verificar existencia antes de actualizar (SaaS Check)
        $exists = $this->service->getSupplierById($id);
        if (!$exists) {
            die("Proveedor no encontrado o acceso denegado.");
        }

        $this->service->updateSupplier($id, $name, $contact, $email, $phone, $address);

        header('Location: /admin/suppliers');
        exit;
    }

    public function delete()
    {
        $id = $_GET['id'] ?? 0;

        // SaaS Check: Verify existence/ownership before deleting
        // Repository delete() relies on ID but BaseRepository delete() might not have SaaS check 
        // IF it generates "DELETE FROM table WHERE id = ?". 
        // BaseRepository::delete() calls newQuery() which ADDS tenant_id check? 
        // Let's verify BaseRepository::delete() implementation in Step 97.
        // Yes: "return $this->newQuery()->where('id', '=', $id)->delete() > 0;"
        // And newQuery() adds "where tenant_id = ?". 
        // So strict isolation is guaranteed.

        if ($this->service->deleteSupplier($id)) {
            // Success
        } else {
            // Failed (probably not found or wrong tenant)
        }

        header('Location: /admin/suppliers');
        exit;
    }
}
