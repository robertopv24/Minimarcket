<?php
// admin/fix_schema.php
// Script para corregir problemas de esquema (Falta AUTO_INCREMENT)
// Bloqueado en tests unitarios.

// Use __DIR__ to find autoload correctly
require_once __DIR__ . '/../templates/autoload.php';

echo "--- INICIO DE CORRECCIÓN DE ESQUEMA ---\n";

try {
    $db = Database::getConnection();

    // 1. Corregir payroll_payments
    echo "1. Applying AUTO_INCREMENT to payroll_payments... ";
    try {
        $stmt = $db->query("ALTER TABLE payroll_payments MODIFY id INT(11) NOT NULL AUTO_INCREMENT");
        echo "OK\n";
    } catch (PDOException $e) {
        echo "FAIL (Maybe already exists/FK issues): " . $e->getMessage() . "\n";
    }

    // 2. Corregir accounts_receivable
    echo "2. Applying AUTO_INCREMENT to accounts_receivable... ";
    try {
        $stmt = $db->query("ALTER TABLE accounts_receivable MODIFY id INT(11) NOT NULL AUTO_INCREMENT");
        echo "OK\n";
    } catch (PDOException $e) {
        echo "FAIL: " . $e->getMessage() . "\n";
    }

    // 3. Corregir clients
    echo "3. Applying AUTO_INCREMENT to clients... ";
    try {
        $stmt = $db->query("ALTER TABLE clients MODIFY id INT(11) NOT NULL AUTO_INCREMENT");
        echo "OK\n";
    } catch (PDOException $e) {
        echo "FAIL: " . $e->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
}

echo "--- FIN ---\n";
?>