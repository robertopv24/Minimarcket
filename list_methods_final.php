<?php
require_once 'templates/autoload.php';
$stmt = $db->query("SELECT id, name FROM payment_methods");
$out = "";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $out .= $row['id'] . ":" . $row['name'] . "\n";
}
file_put_contents('methods_list.txt', $out);
?>
