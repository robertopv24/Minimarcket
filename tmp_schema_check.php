<?php
require_once 'funciones/conexion.php';
$db = Database::getConnection();
$stmt = $db->query('DESCRIBE orders');
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($res, JSON_PRETTY_PRINT);
