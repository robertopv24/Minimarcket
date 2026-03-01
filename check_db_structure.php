<?php
require_once 'c:/xampp/htdocs/Minimarcket/funciones/conexion.php';

try {
    $db = Database::getConnection();

    echo "--- ESTRUCTURA TABLA ORDERS ---\n";
    $stmt = $db->query("DESCRIBE orders");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

    echo "\n--- ESTRUCTURA TABLA ORDER_ITEMS ---\n";
    $stmt = $db->query("DESCRIBE order_items");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

    echo "\n--- LISTA DE TABLAS ---\n";
    $stmt = $db->query("SHOW TABLES");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>