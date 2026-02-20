<?php
require_once '../templates/autoload.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if (!isset($_GET['row_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta row_id']);
    exit;
}

$rowId = (int)$_GET['row_id'];

// 1. Obtener la fila del componente para saber de qué producto base hablamos
$stmt = $db->prepare("SELECT component_id FROM product_components WHERE id = ?");
$stmt->execute([$rowId]);
$baseProductId = $stmt->fetchColumn();

if (!$baseProductId) {
    http_response_code(404);
    echo json_encode(['error' => 'Componente no encontrado']);
    exit;
}

// 2. Obtener Overrides de RECETA
$recipeOverrides = $productManager->getComponentOverrides($rowId);
$isRecipeFallback = false;

if (empty($recipeOverrides)) {
    // FALLBACK: Obtener receta base del producto original
    $baseRecipe = $productManager->getProductComponents($baseProductId);
    $recipeOverrides = array_map(function($c) {
        return [
            'ingredient_id' => $c['component_id'],
            'ingredient_type' => $c['component_type'],
            'quantity' => $c['quantity'],
            'item_name' => $c['item_name'],
            'item_unit' => $c['item_unit'],
            'item_cost' => $c['item_cost']
        ];
    }, $baseRecipe);
    $isRecipeFallback = true;
}

// 3. Obtener Overrides de CONTORNOS
$sideOverrides = $productManager->getComponentSideOverrides($rowId);
$isSidesFallback = false;

if (empty($sideOverrides)) {
    // FALLBACK: Obtener contornos base del producto original
    $baseSides = $productManager->getValidSides($baseProductId);
    $sideOverrides = array_map(function($s) {
        return [
            'side_id' => $s['component_id'],
            'side_type' => $s['component_type'],
            'quantity' => $s['quantity'],
            'is_default' => $s['is_default'],
            'item_name' => $s['item_name'],
            'item_unit' => $s['item_unit'],
            'item_cost' => $s['item_cost']
        ];
    }, $baseSides);
    $isSidesFallback = true;
}

echo json_encode([
    'recipe' => $recipeOverrides,
    'is_recipe_fallback' => $isRecipeFallback,
    'sides' => $sideOverrides,
    'is_sides_fallback' => $isSidesFallback
]);
