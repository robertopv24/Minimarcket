<?php
require_once 'templates/autoload.php';

try {
    $db = Database::getConnection();
    echo "Conectado a la base de datos.\n";

    $sql = "CREATE TABLE IF NOT EXISTS product_component_overrides (
        id INT AUTO_INCREMENT PRIMARY KEY,
        component_row_id INT NOT NULL,
        ingredient_type ENUM('raw', 'manufactured', 'product') NOT NULL,
        ingredient_id INT NOT NULL,
        quantity DECIMAL(15,6) NOT NULL,
        FOREIGN KEY (component_row_id) REFERENCES product_components(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $db->exec($sql);
    echo "Tabla 'product_component_overrides' creada exitosamente.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
