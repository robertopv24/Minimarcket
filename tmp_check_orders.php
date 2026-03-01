<?php
require_once 'templates/autoload.php';
try {
    $stmt = $db->query("DESCRIBE orders");
    $cols = $stmt->fetchAll();
    echo json_encode(['success' => true, 'columns' => $cols]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>