<?php
require_once 'templates/autoload.php';
$stmt = $db->query("DESCRIBE orders");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    if ($c['Field'] === 'delivery_tier') {
        echo "OK: Columna encontrada";
        exit;
    }
}
echo "ERROR: Columna no encontrada";
?>