<?php
require_once 'templates/autoload.php';
$stmt = $db->query("SELECT id, name, type FROM payment_methods");
$out = "";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $out .= "ID: " . $row['id'] . " | NAME: " . $row['name'] . " | TYPE: " . $row['type'] . "\n";
}
file_put_contents('methods.txt', $out);
?>
