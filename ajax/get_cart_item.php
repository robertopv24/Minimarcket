<?php
require_once '../templates/autoload.php';
header('Content-Type: application/json');

if (!isset($_GET['cart_id'])) {
    echo json_encode(['error' => 'Falta ID de carrito']);
    exit;
}

$cartId = $_GET['cart_id'];

// 1. Obtener producto del carrito
$stmt = $db->prepare("SELECT c.*, p.name, p.product_type, p.id as pid, p.max_sides FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ?");
$stmt->execute([$cartId]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    echo json_encode(['error' => 'Producto no encontrado']);
    exit;
}

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
        // AHORA SOPORTAMOS 'product' Y 'manufactured'
        if ($comp['component_type'] == 'product') {
            $qty = intval($comp['quantity']);
            for ($i = 0; $i < $qty; $i++) {
                $subProd = $productManager->getProductById($comp['component_id']);
                $response['sub_items'][] = buildSubItemStructure($db, $productManager, $subProd, $idx);
                $idx++;
            }
        } elseif ($comp['component_type'] == 'manufactured') {
            // Lógica para Productos de Cocina (Hamburguesas, etc.)
            $qty = intval($comp['quantity']);

            // Obtener el nombre directamente de la tabla (Hack rápido hasta tener método en PM)
            $stmtMan = $db->prepare("SELECT * FROM manufactured_products WHERE id = ?");
            $stmtMan->execute([$comp['component_id']]);
            $manProd = $stmtMan->fetch(PDO::FETCH_ASSOC);

            if ($manProd) {
                // Adaptamos al formato de "producto" para la función constructora
                $manProd['product_type'] = 'manufactured'; // Flag interno
                $manProd['max_sides'] = 0; // Por defecto no tienen contornos definidos en product_valid_sides

                for ($i = 0; $i < $qty; $i++) {
                    $response['sub_items'][] = buildSubItemStructure($db, $productManager, $manProd, $idx, 'manufactured');
                    $idx++;
                }
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
function buildSubItemStructure($db, $pm, $product, $index, $forceType = 'product')
{
    // El ID real
    $id = isset($product['pid']) ? $product['pid'] : $product['id'];
    $removables = [];
    $extras = [];
    $sides = [];

    // A. RECETA BASE (Ingredientes Quitables)
    if ($forceType === 'manufactured') {
        // Buscar en production_recipes
        $sqlRec = "SELECT pr.raw_material_id, rm.name, rm.category 
                   FROM production_recipes pr 
                   JOIN raw_materials rm ON pr.raw_material_id = rm.id 
                   WHERE pr.manufactured_product_id = ?";
        $stmtRec = $db->prepare($sqlRec);
        $stmtRec->execute([$id]);
        $recipes = $stmtRec->fetchAll(PDO::FETCH_ASSOC);

        foreach ($recipes as $r) {
            if ($r['category'] !== 'packaging') {
                $removables[] = ['id' => $r['raw_material_id'], 'name' => $r['name']];
            }
        }
    } else {
        // Es un Producto (Simple/Prepared)
        $comps = $pm->getProductComponents($id);

        // Obtener empaques configurados
        $packagingLinks = $pm->getProductPackaging($id);
        $packagingIds = array_column($packagingLinks, 'raw_material_id');

        foreach ($comps as $c) {
            if ($c['component_type'] == 'raw') {
                $stmtCat = $db->prepare("SELECT category FROM raw_materials WHERE id = ?");
                $stmtCat->execute([$c['component_id']]);
                $category = $stmtCat->fetchColumn();

                if ($category !== 'packaging' && !in_array($c['component_id'], $packagingIds)) {
                    $removables[] = ['id' => $c['component_id'], 'name' => $c['item_name']];
                }
            } elseif ($c['component_type'] == 'manufactured') {
                // MODIFICACIÓN: Mostrar el producto manufacturado como un ítem único "Quitable"
                // en lugar de explotar sus ingredientes (receta interna).
                // Esto cumple el requerimiento: "que en la lista de ingredientes se muestren los productos manufacturados"
                $removables[] = ['id' => $c['component_id'], 'name' => $c['item_name']];
            }
        }
    }

    // B. EXTRAS VÁLIDOS (Solo para Productos reales por ahora)
    if ($forceType === 'product') {
        $sqlExtras = "SELECT pve.component_id as id, pve.component_type as type,
                             CASE 
                                WHEN pve.component_type = 'raw' THEN rm.name
                                WHEN pve.component_type = 'manufactured' THEN mp.name
                             END as name,
                             COALESCE(pve.price_override, 1.00) as price,
                             pve.quantity_required
                      FROM product_valid_extras pve
                      LEFT JOIN raw_materials rm ON pve.component_id = rm.id AND pve.component_type = 'raw'
                      LEFT JOIN manufactured_products mp ON pve.component_id = mp.id AND pve.component_type = 'manufactured'
                      WHERE pve.product_id = ?";
        $stmtEx = $db->prepare($sqlExtras);
        $stmtEx->execute([$id]);
        $rawExtras = $stmtEx->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rawExtras as $e) {
            $extras[] = [
                'id' => $e['id'],
                'type' => $e['type'],
                'name' => $e['name'],
                'price' => floatval($e['price']),
                'qty' => floatval($e['quantity_required'])
            ];
        }

        // C. CONTORNOS
        $sides = $pm->getValidSides($id);
    }

    // FIX MAX SIDES: Si el producto tiene contornos pero el límite es 0 (configuración faltante), permitir ilimitado (99).
    $maxSides = intval($product['max_sides'] ?? 0);
    if ($maxSides === 0 && count($sides) > 0) {
        $maxSides = 99;
    }

    return [
        'index' => $index,
        'name' => $product['name'],
        'max_sides' => $maxSides,
        'removables' => $removables,
        'available_extras' => $extras,
        'available_sides' => $sides,
        'component_type' => $forceType, // Útil para debug 
        'component_id' => $id
    ];
}
?>