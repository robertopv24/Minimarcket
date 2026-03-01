<?php
require_once 'c:/xampp/htdocs/Minimarcket/funciones/conexion.php';

function dumpTable($db, $table)
{
    echo "\n--- ESTRUCTURA TABLA $table ---\n";
    $stmt = $db->query("DESCRIBE $table");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        printf("%-20s %-20s %-5s %s\n", $r['Field'], $r['Type'], $r['Null'], $r['Default']);
    }
}

try {
    $db = Database::getConnection();
    dumpTable($db, 'orders');
    dumpTable($db, 'order_items');
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>