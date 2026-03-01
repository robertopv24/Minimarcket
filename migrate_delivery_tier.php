<?php
require_once 'templates/autoload.php';
try {
    $db->exec("ALTER TABLE orders ADD COLUMN delivery_tier CHAR(1) DEFAULT NULL AFTER consumption_type");
    echo "Columna delivery_tier añadida con éxito.";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "La columna delivery_tier ya existe.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>