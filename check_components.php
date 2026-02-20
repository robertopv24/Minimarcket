<?php
require_once 'templates/autoload.php';
try {
    $db = Database::getConnection();
    
    echo "COMPONENTS FOR ID 216:\n";
    $stmt = $db->prepare("SELECT * FROM product_components WHERE product_id = 216");
    $stmt->execute();
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

    echo "\nCOMPANIONS FOR ID 216:\n";
    $stmt2 = $db->prepare("SELECT * FROM product_companions WHERE product_id = 216");
    $stmt2->execute();
    print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
