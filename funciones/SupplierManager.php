<?php
// SupplierManager.php

class SupplierManager
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function addSupplier($name, $contactPerson, $email, $phone, $address)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO suppliers (name, contact_person, email, phone, address) VALUES (?, ?, ?, ?, ?)");
            return $stmt->execute([$name, $contactPerson, $email, $phone, $address]);
        } catch (PDOException $e) {
            error_log("Error al agregar proveedor: " . $e->getMessage());
            return false;
        }
    }

    public function updateSupplier($id, $name, $contactPerson, $email, $phone, $address)
    {
        try {
            $stmt = $this->db->prepare("UPDATE suppliers SET name = ?, contact_person = ?, email = ?, phone = ?, address = ? WHERE id = ?");
            return $stmt->execute([$name, $contactPerson, $email, $phone, $address, $id]);
        } catch (PDOException $e) {
            error_log("Error al actualizar proveedor: " . $e->getMessage());
            return false;
        }
    }

    public function deleteSupplier($id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM suppliers WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error al eliminar proveedor: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener proveedor por ID
     * @param int $id
     * @return array|false Datos del proveedor o false si falla/no existe
     */
    public function getSupplierById($id)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM suppliers WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener proveedor por ID: " . $e->getMessage());
            return false;
        }
    }

    public function searchSuppliers($query = '')
    {
        try {
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
        } catch (PDOException $e) {
            error_log("Error al buscar proveedores: " . $e->getMessage());
            return [];
        }
    }

    public function getAllSuppliers()
    {
        return $this->searchSuppliers();
    }
}
?>