<?php
require_once 'c:/xampp/htdocs/Minimarcket/funciones/conexion.php';

try {
    $db = Database::getConnection();

    // 1. Nueva tabla para logs de tiempo
    $sqlLog = "CREATE TABLE IF NOT EXISTS order_time_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        station VARCHAR(50) NOT NULL,
        event_type VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (order_id),
        INDEX (station),
        INDEX (event_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $db->exec($sqlLog);
    echo "Tabla 'order_time_log' creada/verificada.\n";

    // 2. Columnas de preparación en orders
    $cols = ['kds_kitchen_preparing', 'kds_pizza_preparing'];
    foreach ($cols as $col) {
        try {
            $db->exec("ALTER TABLE orders ADD COLUMN $col TINYINT(1) DEFAULT 0 AFTER delivered_at");
            echo "Columna '$col' añadida.\n";
        } catch (PDOException $e) {
            echo "Columna '$col' ya existe o error: " . $e->getMessage() . "\n";
        }
    }

    // 3. Columna delivered_at en orders (por si acaso no se ejecutó antes)
    try {
        $db->exec("ALTER TABLE orders ADD COLUMN delivered_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at");
        echo "Columna 'delivered_at' añadida.\n";
    } catch (PDOException $e) {
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>