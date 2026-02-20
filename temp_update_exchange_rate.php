<?php
require_once 'funciones/conexion.php';

try {
    $db = Database::getConnection();
    
    echo "Agregando columna exchange_rate a la tabla orders...\n";
    $db->exec("ALTER TABLE orders ADD COLUMN exchange_rate DECIMAL(10,4) DEFAULT 1.0000 AFTER total_price");
    
    // Opcional: Intentar poblar registros antiguos con la tasa actual para no dejarlos en 1.00 si se desea consistencia retroactiva
    // Pero por seguridad, lo dejamos en 1.00 por defecto y solo afectará a nuevas órdenes.
    
    echo "¡Columna agregada con éxito!\n";
    
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') {
        echo "La columna exchange_rate ya existe.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
