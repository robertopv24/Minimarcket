<?php
// SupplierManager.php

class SupplierManager {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function addSupplier($name, $contactPerson, $email, $phone, $address) {
        try {
            $stmt = $this->db->prepare("INSERT INTO suppliers (name, contact_person, email, phone, address) VALUES (?, ?, ?, ?, ?)");
            return $stmt->execute([$name, $contactPerson, $email, $phone, $address]);
        } catch (PDOException $e) {
            error_log("Error al agregar proveedor: " . $e->getMessage());
            return false;
        }
    }

    public function updateSupplier($id, $name, $contactPerson, $email, $phone, $address) {
        try {
            $stmt = $this->db->prepare("UPDATE suppliers SET name = ?, contact_person = ?, email = ?, phone = ?, address = ? WHERE id = ?");
            return $stmt->execute([$name, $contactPerson, $email, $phone, $address, $id]);
        } catch (PDOException $e) {
            error_log("Error al actualizar proveedor: " . $e->getMessage());
            return false;
        }
    }

    public function deleteSupplier($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM suppliers WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error al eliminar proveedor: " . $e->getMessage());
            return false;
        }
    }

    public function getSupplierById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM suppliers WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener proveedor por ID: " . $e->getMessage());
            return false;
        }
    }

    public function getAllSuppliers() {
        try {
            $stmt = $this->db->query("SELECT * FROM suppliers");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener todos los proveedores: " . $e->getMessage());
            return false;
        }
    }
}
?>
