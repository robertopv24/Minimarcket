<?php
require_once 'templates/autoload.php';
$orderId = 68;
$stmt = $db->prepare("SELECT * FROM transactions WHERE reference_type = 'order' AND reference_id = ?");
$stmt->execute([$orderId]);
$txs = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "TRANSACTIONS FOR ORDER #$orderId:\n";
print_r($txs);
?>