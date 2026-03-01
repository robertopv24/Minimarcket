<?php
require_once 'templates/autoload.php';
$stmt = $db->query("SELECT id, status, total_price, consumption_type FROM orders WHERE id IN (67, 68)");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
file_put_contents('debug_orders.txt', print_r($orders, true));
?>