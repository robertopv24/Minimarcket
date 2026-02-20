<?php
require_once 'templates/autoload.php';

// Simulate an admin session
session_start();
$_SESSION['user_id'] = 1; // Assuming ID 1 exists and is admin

echo "--- Iniciando Verificación de Overrides ---\n";

// 1. Crear un producto de prueba (Componente)
$pId = 1; // Usaremos un ID existente para la prueba o uno conocido
$p = $productManager->getProductById($pId);
echo "Producto Original: " . $p['name'] . "\n";
echo "Costo Original: " . $productManager->calculateProductCost($pId) . "\n";

// 2. Crear un Combo de prueba
// (Para esta prueba manual, buscaremos un producto tipo 'compound' existente)
$combos = $db->query("SELECT id FROM products WHERE product_type = 'compound' LIMIT 1")->fetchAll();
if (empty($combos)) {
    echo "No se encontró un combo para la prueba.\n";
    exit;
}
$comboId = $combos[0]['id'];
echo "Combo de prueba ID: $comboId\n";

// 3. Obtener componentes del combo
$components = $productManager->getProductComponents($comboId);
$targetComp = null;
foreach($components as $c) {
    if ($c['component_type'] == 'product') {
        $targetComp = $c;
        break;
    }
}

if (!$targetComp) {
    echo "El combo no tiene componentes tipo 'product'. Agregando uno...\n";
    $productManager->addComponent($comboId, 'product', $pId, 1);
    $components = $productManager->getProductComponents($comboId);
    foreach($components as $c) {
        if ($c['component_type'] == 'product') $targetComp = $c;
    }
}

$rowId = $targetComp['id'];
echo "Row ID del componente en el combo: $rowId\n";

// 4. Aplicar un Override (Ingrediente: Materia Prima ID 1, Cantidad 500)
// Buscamos una materia prima real
$raw = $db->query("SELECT id FROM raw_materials LIMIT 1")->fetch();
$rawId = $raw['id'];

echo "Aplicando override con Materia Prima ID: $rawId\n";
$overrides = [
    ['type' => 'raw', 'id' => $rawId, 'qty' => 10.0] 
];

$res = $productManager->updateComponentOverrides($rowId, $overrides);
echo "Resultado de guardado: " . ($res ? "ÉXITO" : "FALLO") . "\n";

// 5. Verificar Costo del Combo
$newCost = $productManager->calculateProductCost($comboId);
echo "Nuevo Costo del Combo: $newCost\n";

// 6. Verificar Recuperación AJAX
$savedOverrides = $productManager->getComponentOverrides($rowId);
echo "Overrides guardados detectados: " . count($savedOverrides) . "\n";

echo "--- Verificación Finalizada ---\n";
