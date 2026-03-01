<?php
require_once 'templates/autoload.php';
$stmt = $db->query("SELECT * FROM transactions WHERE reference_type = 'order' AND reference_id IN (67, 68)");
$txs = $stmt->fetchAll(PDO::FETCH_ASSOC);
file_put_contents('debug_txs.txt', print_r($txs, true));
?>