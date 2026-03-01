<?php
require_once 'templates/autoload.php';
$stmt = $db->query("DESCRIBE orders");
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo implode(", ", $cols);
?>