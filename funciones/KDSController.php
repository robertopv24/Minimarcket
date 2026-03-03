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
            $pStation = strtolower($pInfo['category_station'] ?? $pInfo['kitchen_station'] ?? '');
            if (empty($pStation))
                continue;

            $isCompound = ($item['product_type'] == 'compound');
            $subItemsTemplate = [];

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
                            $subItemsTemplate[] = [
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

            // --- NUEVA LÓGICA DE FILTRADO, DETALLE Y AGRUPACIÓN (V5 - GROUPED) ---
            $tempBuffer = []; // Buffer temporal para agrupar
            $mods = $orderManager->getItemModifiers($item['id']);
            $groupedMods = [];
            foreach ($mods as $m)
                $groupedMods[$m['sub_item_index']][] = $m;

            $generalNote = $groupedMods[-1][0]['note'] ?? "";
            $mainItemName = ($useShortCodes && !empty($item['short_code'])) ? $item['short_code'] : $item['name'];

            for ($q = 0; $q < $item['quantity']; $q++) {
                if ($isCompound) {
                    $itemsPerCombo = count($subItemsTemplate);
                    foreach ($subItemsTemplate as $templateIdx => $comp) {
                        $realModIdx = ($q * $itemsPerCombo) + $templateIdx;
                        if ($stationFilter !== 'all' && ($comp['station'] ?? '') !== $stationFilter)
                            continue;

                        $currentMods = $groupedMods[$realModIdx] ?? [];
                        if (empty($currentMods) && $q > 0)
                            $currentMods = $groupedMods[$templateIdx] ?? [];

                        $isTakeaway = false;
                        $typeGroups = ['side' => [], 'add' => [], 'remove' => []];

                        foreach ($currentMods as $m) {
                            if ($m['modifier_type'] == 'info' && ($m['is_takeaway'] == 1 || ($m['ingredient_name'] ?? '') == '[LLEVAR]'))
                                $isTakeaway = true;
                            if ($m['modifier_type'] != 'info') {
                                $mName = ($useShortCodes && !empty($m['short_code'])) ? $m['short_code'] : $m['ingredient_name'];
                                $type = strtolower($m['modifier_type'] ?? '');
                                if (isset($typeGroups[$type])) {
                                    $typeGroups[$type][] = strtoupper($mName);
                                }
                            }
                        }

                        $modsList = [];
                        foreach ($typeGroups as $type => $names) {
                            if (!empty($names)) {
                                $prefix = ($type === 'side') ? '** ' : (($type === 'add') ? '++ ' : (($type === 'remove') ? '-- ' : ''));
                                $color = ($type === 'side') ? 'var(--mod-side)' : (($type === 'add') ? 'var(--mod-add)' : (($type === 'remove') ? 'var(--mod-remove)' : ''));
                                $modsList[] = '<span style="color: ' . $color . ';">' . $prefix . implode(' / ', $names) . '</span>';
                            }
                        }

                        $itemNote = "";
                        foreach ($currentMods as $m) {
                            if ($m['modifier_type'] == 'info' && !empty($m['note'])) {
                                $itemNote = $m['note'];
                                break;
                            }
                        }

                        $displayName = $comp['name'] ?? 'ITEM';
                        if (strtoupper($displayName) === strtoupper($mainItemName))
                            $displayName = "";

                        $finalNote = $itemNote ?: $generalNote;

                        // Generar firma única para agrupación
                        $signature = md5($displayName . serialize($modsList) . $finalNote . ($isTakeaway ? '1' : '0'));

                        if (!isset($tempBuffer[$signature])) {
                            $tempBuffer[$signature] = [
                                'qty' => 0,
                                'name' => $displayName,
                                'is_main' => false,
                                'is_combo' => true,
                                'is_takeaway' => $isTakeaway,
                                'mods' => $modsList,
                                'note' => $finalNote,
                                'consumption_type' => $item['consumption_type'] ?? 'dine_in'
                            ];
                        }
                        $tempBuffer[$signature]['qty']++;
                    }
                } else {
                    if ($stationFilter === 'all' || $pStation === $stationFilter) {
                        $currentMods = $groupedMods[$q] ?? [];
                        if (empty($currentMods) && $q > 0)
                            $currentMods = $groupedMods[0] ?? [];

                        $isTakeaway = false;
                        $typeGroups = ['side' => [], 'add' => [], 'remove' => []];

                        foreach ($currentMods as $m) {
                            if ($m['modifier_type'] == 'info' && ($m['is_takeaway'] == 1 || ($m['ingredient_name'] ?? '') == '[LLEVAR]'))
                                $isTakeaway = true;
                            if ($m['modifier_type'] != 'info') {
                                $mName = ($useShortCodes && !empty($m['short_code'])) ? $m['short_code'] : $m['ingredient_name'];
                                $type = strtolower($m['modifier_type'] ?? '');
                                if (isset($typeGroups[$type])) {
                                    $typeGroups[$type][] = strtoupper($mName);
                                }
                            }
                        }

                        $modsList = [];
                        foreach ($typeGroups as $type => $names) {
                            if (!empty($names)) {
                                $prefix = ($type === 'side') ? '** ' : (($type === 'add') ? '++ ' : (($type === 'remove') ? '-- ' : ''));
                                $color = ($type === 'side') ? 'var(--mod-side)' : (($type === 'add') ? 'var(--mod-add)' : (($type === 'remove') ? 'var(--mod-remove)' : ''));
                                $modsList[] = '<span style="color: ' . $color . ';">' . $prefix . implode(' / ', $names) . '</span>';
                            }
                        }

                        $itemNote = "";
                        foreach ($currentMods as $m) {
                            if ($m['modifier_type'] == 'info' && !empty($m['note'])) {
                                $itemNote = $m['note'];
                                break;
                            }
                        }

                        $finalNote = $itemNote ?: $generalNote;
                        $signature = md5("" . serialize($modsList) . $finalNote . ($isTakeaway ? '1' : '0'));

                        if (!isset($tempBuffer[$signature])) {
                            $tempBuffer[$signature] = [
                                'qty' => 0,
                                'name' => "",
                                'is_main' => false,
                                'is_combo' => false,
                                'is_takeaway' => $isTakeaway,
                                'mods' => $modsList,
                                'note' => $finalNote,
                                'consumption_type' => $item['consumption_type'] ?? 'dine_in'
                            ];
                        }
                        $tempBuffer[$signature]['qty']++;
                    }
                }
            }

            $productItemsBuffer = array_values($tempBuffer);

            // Si se generaron ítems para esta estación, añadir cabecera + ítems procesados
            if (!empty($productItemsBuffer)) {
                $stationItems[] = [
                    'qty' => $item['quantity'],
                    'name' => $mainItemName,
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