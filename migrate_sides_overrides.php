<?php
require_once 'templates/autoload.php';

try {
    $db = Database::getConnection();
    echo "Conectado a la base de datos.\n";

    $sql = "CREATE TABLE IF NOT EXISTS product_component_side_overrides (
        id INT AUTO_INCREMENT PRIMARY KEY,
        component_row_id INT NOT NULL,
        side_type ENUM('raw', 'manufactured', 'product') NOT NULL,
        side_id INT NOT NULL,
        quantity DECIMAL(15,6) NOT NULL,
        is_default TINYINT(1) DEFAULT 0,
        FOREIGN KEY (component_row_id) REFERENCES product_components(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $db->exec($sql);
    echo "Tabla 'product_component_side_overrides' creada exitosamente.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
