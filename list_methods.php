<?php
require_once 'templates/autoload.php';
$stmt = $db->query("SELECT * FROM payment_methods");
$methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($methods, JSON_PRETTY_PRINT);
?>
