<?php
require_once 'funciones/conexion.php';

try {
    $db = Database::getConnection();
    
    echo "Sincronizando exchange_rate de transactions hacia orders...\n";
    
    $sql = "UPDATE orders o 
            JOIN (
                SELECT reference_id, MAX(exchange_rate) as rate 
                FROM transactions 
                WHERE reference_type = 'order' 
                GROUP BY reference_id
            ) t ON o.id = t.reference_id 
            SET o.exchange_rate = t.rate 
            WHERE o.exchange_rate = 1.0000";
            
    $count = $db->exec($sql);
    
    echo "¡Proceso completado! Se actualizaron $count órdenes.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
