<?php
require_once 'templates/autoload.php';

echo "--- Verificación Final: Overrides Avanzados ---\n";

// 1. Simular componente de combo
$rowId = 177; // Usando el ID del error para la prueba si existe, o uno válido

// 2. Probar Fallback de AJAX
$_GET['row_id'] = $rowId;
ob_start();
include 'ajax/get_component_overrides.php';
$output = ob_get_clean();
$data = json_decode($output, true);

echo "AJAX Fallback:\n";
echo "Receta Fallback: " . ($data['is_recipe_fallback'] ? "SÍ" : "NO") . "\n";
echo "Contornos Fallback: " . ($data['is_sides_fallback'] ? "SÍ" : "NO") . "\n";
echo "Items Receta: " . count($data['recipe']) . "\n";
echo "Items Contornos: " . count($data['sides']) . "\n";

// 3. Probar Guardado de Contornos
$sides = [
    ['type' => 'raw', 'id' => 1, 'qty' => 1, 'is_default' => 1]
];
$res = $productManager->updateComponentSideOverrides($rowId, $sides);
echo "Guardado Contornos: " . ($res ? "ÉXITO" : "FALLO") . "\n";

// 4. Verificar recuperación tras guardado
$savedSides = $productManager->getComponentSideOverrides($rowId);
echo "Contornos guardados: " . count($savedSides) . "\n";

echo "--- Fin Verificación ---\n";
