<?php
require_once 'templates/autoload.php';
$stmt = $db->query("DESCRIBE order_item_modifiers");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>