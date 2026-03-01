<?php
require_once 'templates/autoload.php';
$stmt = $db->query("DESCRIBE cart");
echo implode(", ", $stmt->fetchAll(PDO::FETCH_COLUMN));
?>