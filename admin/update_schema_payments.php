<?php
require_once __DIR__ . '/../templates/autoload.php';

echo "--- INICIO DE ACTUALIZACIÓN DE ESQUEMA (PAGOS) ---\n";

try {
    $db = Database::getConnection();

    // 1. Añadir columna payment_reference
    echo "1. Añadiendo columna 'payment_reference' a la tabla 'transactions'... ";
    try {
        $db->query("ALTER TABLE transactions ADD COLUMN payment_reference VARCHAR(255) DEFAULT NULL AFTER description");
        echo "OK\n";
    } catch (PDOException $e) {
        echo "AVISO (Probablemente ya existe): " . $e->getMessage() . "\n";
    }

    // 2. Añadir columna sender_name
    echo "2. Añadiendo columna 'sender_name' a la tabla 'transactions'... ";
    try {
        $db->query("ALTER TABLE transactions ADD COLUMN sender_name VARCHAR(255) DEFAULT NULL AFTER payment_reference");
        echo "OK\n";
    } catch (PDOException $e) {
        echo "AVISO (Probablemente ya existe): " . $e->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo "ERROR CRÍTICO: " . $e->getMessage() . "\n";
}

echo "--- FIN ---\n";
?>