<?php

namespace Minimarcket\Modules\SupplyChain\Services;

use Minimarcket\Core\Database;
use PDO;
use Exception;

class SupplierService
{
    private $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function addSupplier($name, $contactPerson, $email, $phone, $address)
    {
        $stmt = $this->db->prepare("INSERT INTO suppliers (name, contact_person, email, phone, address) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$name, $contactPerson, $email, $phone, $address]);
    }

    public function updateSupplier($id, $name, $contactPerson, $email, $phone, $address)
    {
        $stmt = $this->db->prepare("UPDATE suppliers SET name = ?, contact_person = ?, email = ?, phone = ?, address = ? WHERE id = ?");
        return $stmt->execute([$name, $contactPerson, $email, $phone, $address, $id]);
    }

    public function deleteSupplier($id)
    {
        $stmt = $this->db->prepare("DELETE FROM suppliers WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getSupplierById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM suppliers WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function searchSuppliers($query = '')
    {
        $sql = "SELECT * FROM suppliers";
        $params = [];

        if (!empty($query)) {
            $sql .= " WHERE name LIKE ? OR contact_person LIKE ? OR email LIKE ?";
            $term = "%$query%";
            $params = [$term, $term, $term];
        }

        $sql .= " ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllSuppliers()
    {
        return $this->searchSuppliers();
    }
}
