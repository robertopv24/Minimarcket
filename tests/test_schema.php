<?php
require_once 'templates/autoload.php';

echo "ORDERS SCHEMA:\n";
$stmt = $db->query("DESCRIBE orders");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " - " . $row['Type'] . " - Null: " . $row['Null'] . "\n";
}

echo "\nORDER_ITEMS SCHEMA:\n";
$stmt = $db->query("DESCRIBE order_items");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " - " . $row['Type'] . " - Null: " . $row['Null'] . "\n";
}
?>