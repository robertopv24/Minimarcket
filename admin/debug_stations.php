<?php
// Script: Mover bebidas de kitchen → bar
require_once '../templates/autoload.php';

$ids = [208, 209, 215, 211, 212, 213, 214, 216]; // COCA-COLA, CHINOTTO, FRESCOLITA, NARANJA, MANZANITA, TORONJA, UVA, REFRESCO
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $db->prepare("UPDATE products SET kitchen_station = 'bar' WHERE id IN ($placeholders)");
$stmt->execute($ids);
echo "✅ " . $stmt->rowCount() . " bebidas movidas a estación bar correctamente.";
