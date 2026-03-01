<?php
require_once 'funciones/conexion.php';
$db = Database::getConnection();
try {
    $db->exec("ALTER TABLE orders ADD COLUMN client_id INT(11) NULL DEFAULT NULL AFTER user_id");
    $db->exec("ALTER TABLE orders ADD COLUMN employee_id INT(11) NULL DEFAULT NULL AFTER client_id");
    echo "Columnas client_id y employee_id añadidas con éxito.";
} catch (PDOException $e) {
    echo "Error al modificar la tabla: " . $e->getMessage();
}
