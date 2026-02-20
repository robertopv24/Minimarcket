<?php
require_once 'templates/autoload.php';
try {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT id, name, stock, product_type FROM products WHERE id = 134");
    $stmt->execute();
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
