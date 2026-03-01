<?php
session_start();
require_once 'templates/autoload.php';

echo "SESSION USER_ID: " . json_encode($_SESSION['user_id'] ?? 'NULL') . "\n";
echo "SESSION POS_CLIENT_NAME: " . json_encode($_SESSION['pos_client_name'] ?? 'NULL') . "\n";
echo "SESSION_ID: " . session_id() . "\n";

if (isset($_SESSION['user_id'])) {
    $items = $cartManager->getCart($_SESSION['user_id']);
    echo "CART ITEMS COUNT: " . count($items) . "\n";
    foreach ($items as $i) {
        echo "- ID: {$i['id']}, Name: {$i['name']}, Qty: {$i['quantity']}\n";
    }
} else {
    echo "NO USER_ID IN SESSION\n";
}
?>