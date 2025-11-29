<?php
require_once '../templates/autoload.php';
header('Content-Type: application/json');

if (!isset($_GET['cart_id'])) exit(json_encode(['error' => 'Falta ID de carrito']));

$cartId = $_GET['cart_id'];

// 1. Obtener producto del carrito
$stmt = $db->prepare("SELECT c.*, p.name, p.product_type, p.id as pid FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ?");
$stmt->execute([$cartId]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) exit(json_encode(['error' => 'Producto no encontrado']));

$response = [
    'product_name' => $item['name'],
    'is_combo' => ($item['product_type'] === 'compound'),
    'sub_items' => []
];

// 2. Lógica para Desglosar (Explosionar) el Producto
if ($response['is_combo']) {
    // Si es Combo: Buscamos qué productos lleva dentro
    $components = $productManager->getProductComponents($item['pid']);

    $idx = 0;
    foreach ($components as $comp) {
        if ($comp['component_type'] == 'product') {
            $qty = intval($comp['quantity']);
            // Repetimos el sub-producto tantas veces como diga la cantidad (ej: 2 Hamburguesas = 2 filas)
            for ($i = 0; $i < $qty; $i++) {
                $subProd = $productManager->getProductById($comp['component_id']);
                $response['sub_items'][] = buildSubItemStructure($productManager, $subProd, $idx);
                $idx++;
            }
        }
    }
} else {
    // Si es Simple/Preparado: Es un solo ítem
    $response['sub_items'][] = buildSubItemStructure($productManager, $item, 0);
}

// 3. Obtener Extras Disponibles (Global)
$extras = [];
$mats = $rawMaterialManager->getAllMaterials();
foreach ($mats as $m) {
    // Filtramos lo que sea ingrediente y no empaque
    if ($m['category'] != 'packaging' && $m['is_cooking_supply'] == 0) {
        $price = (preg_match('/queso|carne|pollo|jamon|tocineta|bacon/i', $m['name'])) ? 1.00 : 0.50;
        $extras[] = ['id' => $m['id'], 'name' => $m['name'], 'price' => $price];
    }
}
$response['available_extras'] = $extras;

// 4. Obtener Modificaciones Guardadas (De la tabla relacional)
// Como no tenemos columna "sub_item_index" en la DB, usaremos un truco:
// El frontend enviará todo junto y aquí lo devolvemos junto. El frontend se encarga de repartirlo.
$stmtM = $db->prepare("SELECT * FROM cart_item_modifiers WHERE cart_id = ?");
$stmtM->execute([$cartId]);
$savedMods = $stmtM->fetchAll(PDO::FETCH_ASSOC);

$response['saved_mods'] = $savedMods;

echo json_encode($response);

// --- Función Auxiliar ---
function buildSubItemStructure($pm, $product, $index) {
    // Buscar qué ingredientes se le pueden quitar (Receta base)
    $comps = $pm->getProductComponents($product['id'] ?? $product['pid']);
    $removables = [];
    foreach ($comps as $c) {
        if ($c['component_type'] == 'raw') {
            $n = strtolower($c['item_name']);
            if (strpos($n, 'caja')===false && strpos($n, 'papel')===false && strpos($n, 'aceite')===false) {
                $removables[] = ['id' => $c['component_id'], 'name' => $c['item_name']];
            }
        }
    }
    return [
        'index' => $index,
        'name' => $product['name'],
        'removables' => $removables
    ];
}
?>
