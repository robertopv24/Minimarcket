<?php
require_once 'funciones/conexion.php';
$db = Database::getConnection();
$stmt = $db->query('DESCRIBE orders');
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($res as $row) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
