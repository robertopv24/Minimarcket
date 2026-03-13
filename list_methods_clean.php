<?php
require_once 'templates/autoload.php';
$stmt = $db->query("SELECT id, name FROM payment_methods");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $row['id'] . " | NAME: " . $row['name'] . "\n";
}
?>
