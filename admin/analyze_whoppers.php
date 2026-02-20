<?php
define('CLI_MODE', true);
require_once __DIR__ . '/../templates/autoload.php';

echo "ANALISIS DE RECETAS WHOPPER\n";
echo "===========================\n";

// 1. Buscar productos con "Whopper" en el nombre (case insensitive)
$stmt = $db->prepare("SELECT id, name FROM products WHERE name LIKE ? ORDER BY name");
$term = '%WHOPPER%';
$stmt->execute([$term]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($products)) {
    echo "No se encontraron productos con 'Whopper' en el nombre.\n";
    exit;
}

$mappedComponents = [];
$templateRecipe = []; // Intentaremos capturar una receta "completa" como ejemplo

// Pre-cargar mapas de nombres para reportar bonito
$rawMap = [];
foreach ($rawMaterialManager->getAllMaterials() as $r) {
    // Ajuste según estructura real: raw_materials tiene 'name' y 'unit'
    $rawMap[$r['id']] = $r['name'] . " (" . $r['unit'] . ")";
}

$manufacturedMap = [];
foreach ($productionManager->getAllManufactured() as $m) {
    $manufacturedMap[$m['id']] = $m['name'] . " (" . $m['unit'] . ")";
}
// También products puede ser componente
$productMap = [];
$allProds = $productManager->getAllProducts(); // Asumiendo que existe o similar
foreach ($allProds as $p) {
    if (is_array($p)) { // getAllProducts puede retornar array de arrays
        $productMap[$p['id']] = $p['name'];
    }
}


foreach ($products as $prod) {
    $id = $prod['id'];
    $name = $prod['name'];

    echo "\n[ID: $id] $name\n";

    // Obtener componentes
    $stmtComp = $db->prepare("SELECT * FROM product_components WHERE product_id = ?");
    $stmtComp->execute([$id]);
    $components = $stmtComp->fetchAll(PDO::FETCH_ASSOC);

    if (empty($components)) {
        echo "   >> SIN RECETA (FALTANTE)\n";
    } else {
        foreach ($components as $comp) {
            $type = $comp['component_type'];
            $compId = $comp['component_id'];
            $qty = $comp['quantity'];

            $compName = "Desconocido [$type ID:$compId]";

            if ($type === 'raw' && isset($rawMap[$compId])) {
                $compName = $rawMap[$compId];
            } elseif ($type === 'manufactured' && isset($manufacturedMap[$compId])) {
                $compName = "[M] " . $manufacturedMap[$compId];
            } elseif ($type === 'product' && isset($productMap[$compId])) {
                $compName = "[P] " . $productMap[$compId];
            }

            echo "   - $qty x $compName\n";
        }
    }
}
