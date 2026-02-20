<?php
require_once 'templates/autoload.php';
try {
    $db = Database::getConnection();
    
    // Check Products Stock
    echo "STOCK DATA:\n";
    $stmt = $db->query("SELECT id, name, stock FROM products WHERE id IN (214, 216) OR name LIKE '%COCA%'");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

    // Check Current Cart
    echo "\nCART DATA:\n";
    $cartStmt = $db->query("SELECT c.id, c.quantity, p.name, p.id as pid FROM cart c JOIN products p ON c.product_id = p.id");
    $cart = $cartStmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($cart);

    // Check Current Modifiers
    echo "\nMODIFIERS DATA:\n";
    $modsStmt = $db->query("SELECT cim.*, p.name as item_name FROM cart_item_modifiers cim LEFT JOIN products p ON cim.component_id = p.id WHERE cim.modifier_type IN ('add', 'side')");
    $mods = $modsStmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($mods);

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
