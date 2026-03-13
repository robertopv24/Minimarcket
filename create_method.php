<?php
require_once 'templates/autoload.php';
$stmt = $db->prepare("INSERT INTO payment_methods (name, type, currency, is_active) VALUES ('Consumo de Saldo', 'digital', 'USD', 1)");
$stmt->execute();
echo "ID: " . $db->lastInsertId();
?>
