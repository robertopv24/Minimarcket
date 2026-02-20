<?php
require_once '../templates/autoload.php';
header('Content-Type: application/json');

if (!isset($_GET['cart_id'])) {
    echo json_encode(['error' => 'Falta ID de carrito']);
    exit;
}

$cartId = $_GET['cart_id'];

// 1. Obtener producto del carrito
$stmt = $db->prepare("SELECT c.*, p.name, p.product_type, p.id as pid, p.max_sides, p.contour_logic_type FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ?");
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
        $rowId = $comp['id']; // ID de product_components
        if ($comp['component_type'] == 'product') {
            $qty = intval($comp['quantity']);
            for ($i = 0; $i < $qty; $i++) {
                $subProd = $productManager->getProductById($comp['component_id']);
                $response['sub_items'][] = buildSubItemStructure($db, $productManager, $subProd, $idx, 'product', $rowId);
                $idx++;
            }
        } elseif ($comp['component_type'] == 'manufactured') {
            $qty = intval($comp['quantity']);
            $stmtMan = $db->prepare("SELECT * FROM manufactured_products WHERE id = ?");
            $stmtMan->execute([$comp['component_id']]);
            $manProd = $stmtMan->fetch(PDO::FETCH_ASSOC);

            if ($manProd) {
                $manProd['product_type'] = 'manufactured';
                $manProd['max_sides'] = 0;

                for ($i = 0; $i < $qty; $i++) {
                    $response['sub_items'][] = buildSubItemStructure($db, $productManager, $manProd, $idx, 'manufactured', $rowId);
                    $idx++;
                }
            }
        }
    }
} else {
    // Producto Simple / Preparado (No tiene RowId de componente porque es el principal)
    $response['sub_items'][] = buildSubItemStructure($db, $productManager, $item, 0);
}

// 3. Obtener Modificaciones Guardadas
$stmtM = $db->prepare("SELECT * FROM cart_item_modifiers WHERE cart_id = ?");
$stmtM->execute([$cartId]);
$response['saved_mods'] = $stmtM->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($response);

// --- FUNCIÓN CONSTRUCTORA MEJORADA ---
function buildSubItemStructure($db, $pm, $product, $index, $forceType = 'product', $componentRowId = null)
{
    // El ID real
    $id = isset($product['pid']) ? $product['pid'] : $product['id'];
    $removables = [];
    $extras = [];
    $sides = [];

    // A. RECETA (Ingredientes Quitables)
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
        // 1. CHEQUEAR OVERRIDES DE ADMIN (Si estamos dentro de un combo)
        $overrideRecipe = ($componentRowId) ? $pm->getComponentOverrides($componentRowId) : [];
        
        if (!empty($overrideRecipe)) {
            // USAR RECETA PERSONALIZADA POR ADMIN
            foreach ($overrideRecipe as $or) {
                // Solo permitimos quitar ingredientes que sean 'raw' o 'manufactured' en el modal del cliente
                if ($or['component_type'] !== 'product') {
                    $removables[] = ['id' => $or['component_id'], 'name' => $or['item_name']];
                }
            }
        } else {
            // FALLBACK: Receta Base Standar
            $comps = $pm->getProductComponents($id);
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
                    $removables[] = ['id' => $c['component_id'], 'name' => $c['item_name']];
                }
            }
        }
    }

    // B. EXTRAS VÁLIDOS
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
        // 1. CHEQUEAR OVERRIDES DE ADMIN
        $overrideSides = ($componentRowId) ? $pm->getComponentSideOverrides($componentRowId) : [];
        
        if (!empty($overrideSides)) {
            $sides = $overrideSides;
        } else {
            // FALLBACK: Contornos Base
            $sides = $pm->getValidSides($id);
        }
    }

    // D. AGREGAR STOCK A CONTORNOS
    foreach ($sides as &$side) {
        $sideId = $side['component_id'];
        $sideType = $side['component_type'];
        $stock = 0;

        if ($sideType === 'raw') {
            $stmtStock = $db->prepare("SELECT stock_quantity FROM raw_materials WHERE id = ?");
            $stmtStock->execute([$sideId]);
            $stock = floatval($stmtStock->fetchColumn());
        } elseif ($sideType === 'manufactured') {
            $stmtStock = $db->prepare("SELECT stock FROM manufactured_products WHERE id = ?");
            $stmtStock->execute([$sideId]);
            $stock = floatval($stmtStock->fetchColumn());
        } elseif ($sideType === 'product') {
            $stock = $pm->getVirtualStock($sideId);
        }
        $side['stock'] = $stock;
    }

    // E. Lógica Max Sides (Restaurada)
    $maxSides = intval($product['max_sides'] ?? 0);
    if ($maxSides === 0 && count($sides) > 0) {
        $maxSides = 99;
    }

    return [

        'index' => $index,
        'name' => $product['name'],
        'max_sides' => $maxSides,
        'contour_logic_type' => $product['contour_logic_type'] ?? 'standard',
        'removables' => $removables,
        'available_extras' => $extras,
        'available_sides' => $sides,
        'component_type' => $forceType,
        'component_id' => $id,
        'is_customized_by_admin' => ($componentRowId && (!empty($overrideRecipe) || !empty($overrideSides)))
    ];
}
?>