<?php
$host = 'localhost';
$db_name = 'minimarket';
$user = 'root';
$pass = '19451788';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to DB\n";
    
    echo "--- TV Playlist Items ---\n";
    $stmt = $pdo->query("SELECT id, custom_title, custom_description FROM tv_playlist_items");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($items);

    echo "\n--- Recent Products ---\n";
    $stmt = $pdo->query("SELECT id, name, description FROM products ORDER BY id DESC LIMIT 5");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($products);
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
