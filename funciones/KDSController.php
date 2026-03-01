<?php
// funciones/KDSController.php
require_once __DIR__ . '/../templates/autoload.php';

function getKDSData($stationFilter = 'all')
{
    global $orderManager, $productManager, $db, $config;

    $orders = $orderManager->getKDSOrders($stationFilter);
    $useShortCodes = ($config->get('kds_use_short_codes', '0') == '1');

    $processedOrders = [];

    foreach ($orders as $orden) {
        $items = $orderManager->getOrderItems($orden['id']);
        $stationItems = [];

        $hasKitchen = false;
        $hasPizza = false;

        foreach ($items as $item) {
            $pInfo = $productManager->getProductById($item['product_id']);
            if (!$pInfo || empty($pInfo['kitchen_station']))
                continue;

            $pStation = strtolower($pInfo['category_station'] ?? $pInfo['kitchen_station']);
            $isCompound = ($item['product_type'] == 'compound');
            $subItems = [];

            if ($isCompound) {
                $comps = $productManager->getProductComponents($item['product_id']);
                foreach ($comps as $c) {
                    $compData = null;
                    if ($c['component_type'] == 'product') {
                        $compData = $productManager->getProductById($c['component_id']);
                    } elseif ($c['component_type'] == 'manufactured') {
                        $stmtM = $db->prepare("SELECT kitchen_station, name, short_code FROM manufactured_products WHERE id = ?");
                        $stmtM->execute([$c['component_id']]);
                        $compData = $stmtM->fetch(PDO::FETCH_ASSOC);
                    }
                    if ($compData) {
                        for ($k = 0; $k < $c['quantity']; $k++) {
                            $st = strtolower($compData['category_station'] ?? $compData['kitchen_station'] ?? '');
                            $subItems[] = [
                                'name' => ($useShortCodes && !empty($compData['short_code'])) ? $compData['short_code'] : ($compData['name'] ?? 'ITEM'),
                                'station' => $st
                            ];
                            if ($st === 'kitchen')
                                $hasKitchen = true;
                            if ($st === 'pizza')
                                $hasPizza = true;
                        }
                    }
                }
            } else {
                if ($pStation === 'kitchen')
                    $hasKitchen = true;
                if ($pStation === 'pizza')
                    $hasPizza = true;
            }

            // --- NUEVA LÓGICA DE FILTRADO Y DETALLE (CORREGIDA V2) ---
            $productItemsBuffer = [];
            $mods = $orderManager->getItemModifiers($item['id']);
            $groupedMods = [];
            foreach ($mods as $m)
                $groupedMods[$m['sub_item_index']][] = $m;

            $generalNote = $groupedMods[-1][0]['note'] ?? "";

            if ($isCompound) {
                // Para productos compuestos, evaluamos cada componente individualmente respetando su índice original
                foreach ($subItems as $idx => $comp) {
                    // Filtrar por estación si el filtro no es 'all'
                    if ($stationFilter !== 'all' && ($comp['station'] ?? '') !== $stationFilter) {
                        continue;
                    }

                    $currentMods = $groupedMods[$idx] ?? [];
                    $isTakeaway = false;
                    $modsList = [];

                    foreach ($currentMods as $m) {
                        if ($m['modifier_type'] == 'info' && ($m['is_takeaway'] == 1 || ($m['ingredient_name'] ?? '') == '[LLEVAR]'))
                            $isTakeaway = true;
                        if ($m['modifier_type'] != 'info') {
                            $mName = ($useShortCodes && !empty($m['short_code'])) ? $m['short_code'] : $m['ingredient_name'];
                            $type = strtolower($m['modifier_type'] ?? '');
                            $prefix = ($type === 'side') ? '** ' : (($type === 'add') ? '++ ' : (($type === 'remove') ? '-- ' : ''));
                            $color = ($type === 'side') ? 'var(--mod-side)' : (($type === 'add') ? 'var(--mod-add)' : (($type === 'remove') ? 'var(--mod-remove)' : ''));

                            $modsList[] = '<span style="color: ' . $color . ';">' . $prefix . strtoupper($mName) . '</span>';
                        }
                    }

                    $itemNote = "";
                    foreach ($currentMods as $m) {
                        if ($m['modifier_type'] == 'info' && !empty($m['note'])) {
                            $itemNote = $m['note'];
                            break;
                        }
                    }

                    $productItemsBuffer[] = [
                        'num' => $idx + 1,
                        'name' => $comp['name'] ?? 'ITEM',
                        'is_main' => false,
                        'is_combo' => true,
                        'is_takeaway' => $isTakeaway,
                        'mods' => $modsList,
                        'note' => $itemNote ?: ((empty($productItemsBuffer)) ? $generalNote : ""),
                        'consumption_type' => $item['consumption_type'] ?? 'dine_in'
                    ];
                }
            } else {
                // Para productos simples, evaluamos la estación principal una sola vez
                if ($stationFilter === 'all' || $pStation === $stationFilter) {
                    for ($i = 0; $i < $item['quantity']; $i++) {
                        $currentMods = $groupedMods[$i] ?? [];
                        $isTakeaway = false;
                        $modsList = [];

                        foreach ($currentMods as $m) {
                            if ($m['modifier_type'] == 'info' && ($m['is_takeaway'] == 1 || ($m['ingredient_name'] ?? '') == '[LLEVAR]'))
                                $isTakeaway = true;
                            if ($m['modifier_type'] != 'info') {
                                $mName = ($useShortCodes && !empty($m['short_code'])) ? $m['short_code'] : $m['ingredient_name'];
                                $type = strtolower($m['modifier_type'] ?? '');
                                $prefix = ($type === 'side') ? '** ' : (($type === 'add') ? '++ ' : (($type === 'remove') ? '-- ' : ''));
                                $color = ($type === 'side') ? 'var(--mod-side)' : (($type === 'add') ? 'var(--mod-add)' : (($type === 'remove') ? 'var(--mod-remove)' : ''));

                                $modsList[] = '<span style="color: ' . $color . ';">' . $prefix . strtoupper($mName) . '</span>';
                            }
                        }

                        $itemNote = "";
                        foreach ($currentMods as $m) {
                            if ($m['modifier_type'] == 'info' && !empty($m['note'])) {
                                $itemNote = $m['note'];
                                break;
                            }
                        }

                        $productItemsBuffer[] = [
                            'num' => $i + 1,
                            'name' => $item['name'],
                            'is_main' => false,
                            'is_combo' => false,
                            'is_takeaway' => $isTakeaway,
                            'mods' => $modsList,
                            'note' => $itemNote ?: ((empty($productItemsBuffer)) ? $generalNote : ""),
                            'consumption_type' => $item['consumption_type'] ?? 'dine_in'
                        ];
                    }
                }
            }

            // Si se generaron ítems para esta estación, añadir cabecera + ítems procesados
            if (!empty($productItemsBuffer)) {
                $stationItems[] = [
                    'qty' => $item['quantity'],
                    'name' => ($useShortCodes && !empty($item['short_code'])) ? $item['short_code'] : $item['name'],
                    'is_main' => true,
                    'is_combo' => $isCompound,
                    'consumption_type' => $item['consumption_type'] ?? 'dine_in'
                ];
                foreach ($productItemsBuffer as $pi) {
                    $stationItems[] = $pi;
                }
            }
        }

        if (!empty($stationItems)) {
            // Lógica de otro station ready (mixto)
            $otherReady = false;
            if ($hasKitchen && $hasPizza) {
                if ($stationFilter === 'kitchen' && $orden['kds_pizza_ready'])
                    $otherReady = true;
                if ($stationFilter === 'pizza' && $orden['kds_kitchen_ready'])
                    $otherReady = true;
            }

            $processedOrders[] = [
                'info' => array_merge($orden, [
                    'simple_flow' => ($config->get('kds_simple_flow', '0') == '1'),
                    'is_other_ready' => $otherReady,
                    'station_type' => $stationFilter
                ]),
                'items' => $stationItems,
                'is_mixed' => ($hasKitchen && $hasPizza)
            ];
        }
    }

    return $processedOrders;
}
?>