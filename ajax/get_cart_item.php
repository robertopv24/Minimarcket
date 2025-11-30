<?php
require_once '../templates/autoload.php';
header('Content-Type: application/json');

if (!isset($_GET['cart_id'])) {
    echo json_encode(['error' => 'Falta ID de carrito']);
    exit;
}

$cartId = $_GET['cart_id'];

// 1. Obtener producto del carrito
$stmt = $db->prepare("SELECT c.*, p.name, p.product_type, p.id as pid FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ?");
$stmt->execute([$cartId]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) { echo json_encode(['error' => 'Producto no encontrado']); exit; }

$response = [
    'product_name' => $item['name'],
    'product_type' => $item['product_type'],
    'consumption_type' => $item['consumption_type'], // Necesario para el modal
    'sub_items' => []
];

// 2. Lógica de Desglose (Explosión de Combos)
if ($item['product_type'] === 'compound') {
    $components = $productManager->getProductComponents($item['pid']);
    $idx = 0;
    foreach ($components as $comp) {
        if ($comp['component_type'] == 'product') {
            $qty = intval($comp['quantity']);
            for ($i = 0; $i < $qty; $i++) {
                $subProd = $productManager->getProductById($comp['component_id']);
                // Pasamos la DB para consultar extras específicos
                $response['sub_items'][] = buildSubItemStructure($db, $productManager, $subProd, $idx);
                $idx++;
            }
        }
    }
} else {
    // Producto Simple / Preparado
    $response['sub_items'][] = buildSubItemStructure($db, $productManager, $item, 0);
}

// 3. Obtener Modificaciones Guardadas
$stmtM = $db->prepare("SELECT * FROM cart_item_modifiers WHERE cart_id = ?");
$stmtM->execute([$cartId]);
$response['saved_mods'] = $stmtM->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($response);

// --- FUNCIÓN CONSTRUCTORA MEJORADA ---
function buildSubItemStructure($db, $pm, $product, $index) {
    // A. Ingredientes Quitables (Receta Base)
    $productId = $product['id'] ?? $product['pid'];
    $comps = $pm->getProductComponents($productId);
    $removables = [];

    foreach ($comps as $c) {
        if ($c['component_type'] == 'raw') {
            $n = strtolower($c['item_name']);
            if (strpos($n, 'caja')===false && strpos($n, 'papel')===false && strpos($n, 'aceite')===false) {
                $removables[] = ['id' => $c['component_id'], 'name' => $c['item_name']];
            }
        }
    }

    // B. EXTRAS VÁLIDOS (Consulta Específica por Producto)
    // Buscamos solo lo que esté en la tabla 'product_valid_extras' para este ID
    $extras = [];
    $sqlExtras = "SELECT rm.id, rm.name,
                         COALESCE(pve.price_override, 1.00) as price
                  FROM product_valid_extras pve
                  JOIN raw_materials rm ON pve.raw_material_id = rm.id
                  WHERE pve.product_id = ?";

    $stmtEx = $db->prepare($sqlExtras);
    $stmtEx->execute([$productId]);
    $rawExtras = $stmtEx->fetchAll(PDO::FETCH_ASSOC);

    // Si la tabla está vacía para este producto, fallback básico (opcional)
    // O mostramos vacío para obligar a configurar. Aquí mostramos vacío si no hay configuración.
    foreach($rawExtras as $e) {
        $extras[] = [
            'id' => $e['id'],
            'name' => $e['name'],
            'price' => floatval($e['price'])
        ];
    }

    return [
        'index' => $index,
        'name' => $product['name'],
        'removables' => $removables,
        'available_extras' => $extras // <--- AHORA VIAJA AQUÍ, ESPECÍFICO POR ÍTEM
    ];
}
?>
