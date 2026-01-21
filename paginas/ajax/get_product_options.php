<?php
session_start();
require_once '../../templates/autoload.php';

$productId = $_GET['product_id'] ?? null;

if (!$productId) {
    echo json_encode(['error' => 'ID de producto no proporcionado']);
    exit;
}

$product = $productManager->getProductById($productId);
if (!$product) {
    echo json_encode(['error' => 'Producto no encontrado']);
    exit;
}

$sides = $productManager->getValidSides($productId);

echo json_encode([
    'product_name' => $product['name'],
    'max_sides' => $product['max_sides'],
    'sides' => $sides
]);
