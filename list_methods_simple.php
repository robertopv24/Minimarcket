<?php
require_once 'templates/autoload.php';
$stmt = $db->query("SELECT id, name, type FROM payment_methods");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['id'] . " | " . $row['name'] . " | " . $row['type'] . "\n";
}
?>
