<?php

class CartManager
{
    private $db;
    private $table_name = 'cart';

    public function __construct($db)
    {
        $this->db = $db;
    }

    // 1. AGREGAR AL CARRITO (Mejorado para Plan v2)
    public function addToCart($user_id, $product_id, $quantity, $modifiers = [], $consumptionType = 'dine_in', $parentCartId = null, $priceOverride = null)
    {
        $startedTransaction = false;
        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $startedTransaction = true;
            }

            $productManager = new ProductManager($this->db);
            $product = $productManager->getProductById($product_id);

            if (!$product)
                throw new Exception("Producto no encontrado.");

            $availableStock = ($product['product_type'] === 'simple')
                ? intval($product['stock'])
                : $productManager->getVirtualStock($product_id);

            if ($availableStock < $quantity)
                throw new Exception("Stock insuficiente.");

            $stmt = $this->db->prepare("INSERT INTO {$this->table_name} (user_id, product_id, quantity, consumption_type, parent_cart_id, price_override) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $product_id, $quantity, $consumptionType, $parentCartId, $priceOverride]);
            $cartId = $this->db->lastInsertId();

            if (empty($modifiers)) {
                $explodedDefaults = $productManager->getProductExplodedDefaults($product_id);

                if (!empty($explodedDefaults)) {
                    $modifiers['items'] = [];
                    // Agrupar por sub_item_index
                    foreach ($explodedDefaults as $def) {
                        $idx = $def['sub_item_index'];
                        // Nota Global
                        if ($idx == -1) {
                            $modifiers['general_note'] = $def['note'];
                            continue;
                        }

                        if (!isset($modifiers['items'][$idx])) {
                            $modifiers['items'][$idx] = [
                                'index' => $idx,
                                'consumption' => $consumptionType,
                                'remove' => [],
                                'add' => [],
                                'sides' => []
                            ];
                        }

                        if ($def['modifier_type'] == 'info') {
                            $modifiers['items'][$idx]['consumption'] = ($def['is_takeaway'] == 1) ? 'takeaway' : 'dine_in';
                        } elseif ($def['modifier_type'] == 'add') {
                            $modifiers['items'][$idx]['add'][] = [
                                'id' => $def['component_id'],
                                'type' => $def['component_type'],
                                'qty' => $def['quantity_adjustment'],
                                'price' => $def['price_adjustment']
                            ];
                        } elseif ($def['modifier_type'] == 'side') {
                            $modifiers['items'][$idx]['sides'][] = [
                                'id' => $def['component_id'],
                                'type' => $def['component_type'],
                                'qty' => $def['quantity_adjustment'],
                                'price' => $def['price_adjustment']
                            ];
                        } elseif ($def['modifier_type'] == 'remove') {
                            $modifiers['items'][$idx]['remove'][] = $def['component_id'];
                        }
                    }

                    // REPLICACION PARA QTY > 1 (Mismo que antes)
                    $maxIdx = count($modifiers['items']) > 0 ? max(array_keys($modifiers['items'])) : 0;
                    if ($maxIdx == 0 && $quantity > 1) {
                        $baseItem = $modifiers['items'][0];
                        for ($j = 1; $j < $quantity; $j++) {
                            $newItem = $baseItem;
                            $newItem['index'] = $j;
                            $modifiers['items'][$j] = $newItem;
                        }
                    }
                }
            }

            // --- LÓGICA DE ACOMPAÑANTES v2 (Independientes) ---
            // Solo buscamos acompañantes si nosotros NO somos ya un acompañante (evitar bucles infinitos)
            // --- LÓGICA DE ACOMPAÑANTES v2 (Independientes) ---
            // Solo buscamos acompañantes si nosotros NO somos ya un acompañante (evitar bucles infinitos)
            if ($parentCartId === null) {
                // Modificado para pasar false (no calcular precio recursivo) o verificar firma
                $companions = $productManager->getCompanions($product_id);
                foreach ($companions as $comp) {
                    // Calculamos cantidad proporcional (ej: si pido 2 hamburguesas, van 2 cocas)
                    $compQty = $quantity * floatval($comp['quantity']);
                    $compPrice = $comp['price_override']; // Puede ser numérico o NULL

                    // Llamada RECURSIVA para insertar el acompañante como item independiente
                    // IMPORTANTE: Ahora pasamos $comp['id'] (el ID de la fila product_companions) como origin
                    $this->addToCart($user_id, $comp['companion_id'], $compQty, [], $consumptionType, $cartId, $compPrice);

                    // Hack para vincular el cart_item recien creado con su origin_companion_id
                    // Como addToCart retorna true/error y no el ID, necesitamos obtener el último ID insertado en esta conexión
                    // Esto es seguro porque estamos dentro de una transacción.
                    $newCompanionCartId = $this->db->lastInsertId();

                    // Insertamos el modificador oculto 'companion_origin'
                    $stmtLink = $this->db->prepare("INSERT INTO cart_item_modifiers (cart_id, modifier_type, sub_item_index, component_id, component_type, note) VALUES (?, 'companion_origin', -1, ?, 'setup', 'Linked to ProductCompanion #')");
                    $stmtLink->execute([$newCompanionCartId, $comp['id']]);
                }
            }
            // --------------------------------------------------

            if (!empty($modifiers)) {
                $result = $this->updateItemModifiers($cartId, $modifiers);
                if ($result !== true) {
                    throw new Exception($result);
                }
            }

            if ($startedTransaction) {
                $this->db->commit();
            }
            return true;

        } catch (Exception $e) {
            if ($startedTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return "Error: " . $e->getMessage();
        }
    }

    // 2. ACTUALIZAR MODIFICADORES (Lógica Simplificada)
    public function updateItemModifiers($cartId, $data)
    {
        $startedTransaction = false;
        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $startedTransaction = true;
            }

            // A. Limpiar todo lo anterior de este ítem
            $this->db->prepare("DELETE FROM cart_item_modifiers WHERE cart_id = ?")->execute([$cartId]);

            // B. Guardar Nota General (Texto libre opcional)
            if (!empty($data['general_note'])) {
                // Usamos sub_item_index -1 para la nota global
                $stmt = $this->db->prepare("INSERT INTO cart_item_modifiers (cart_id, modifier_type, sub_item_index, note) VALUES (?, 'info', -1, ?)");
                $stmt->execute([$cartId, $data['general_note']]);
            }

            // C. Guardar Configuración por Ítem
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $subItem) {
                    $idx = $subItem['index'];

                    // 1. GUARDAR ESTADO BOOLEANO Y NOTA (La clave de la simplificación)
                    // is_takeaway: 1 = Llevar, 0 = Mesa
                    $isTakeaway = ($subItem['consumption'] === 'takeaway') ? 1 : 0;
                    $itemNote = $subItem['note'] ?? null;

                    // Guardamos una fila 'info' que contiene el estado booleano y la nota
                    $stmtState = $this->db->prepare("INSERT INTO cart_item_modifiers (cart_id, modifier_type, sub_item_index, is_takeaway, note) VALUES (?, 'info', ?, ?, ?)");
                    $stmtState->execute([$cartId, $idx, $isTakeaway, $itemNote]);

                    // 2. Guardar Remociones
                    if (!empty($subItem['remove'])) {
                        $stmtRem = $this->db->prepare("INSERT INTO cart_item_modifiers (cart_id, modifier_type, sub_item_index, component_id, component_type) VALUES (?, 'remove', ?, ?, 'raw')");
                        foreach ($subItem['remove'] as $rawId) {
                            $stmtRem->execute([$cartId, $idx, $rawId]);
                        }
                    }

                    // 3. Guardar Adiciones
                    if (!empty($subItem['add'])) {
                        $stmtAdd = $this->db->prepare("INSERT INTO cart_item_modifiers (cart_id, modifier_type, sub_item_index, component_id, component_type, quantity_adjustment, price_adjustment) VALUES (?, 'add', ?, ?, ?, ?, ?)");
                        foreach ($subItem['add'] as $extra) {
                            $type = $extra['type'] ?? 'raw';
                            $qty = $extra['qty'] ?? 1.00;
                            $stmtAdd->execute([$cartId, $idx, $extra['id'], $type, $qty, $extra['price']]);
                        }
                    }

                    // 4. Guardar Contornos (NUEVO)
                    if (!empty($subItem['sides'])) {
                        $stmtSide = $this->db->prepare("INSERT INTO cart_item_modifiers (cart_id, modifier_type, sub_item_index, component_type, component_id, quantity_adjustment, price_adjustment) VALUES (?, 'side', ?, ?, ?, ?, ?)");
                        foreach ($subItem['sides'] as $side) {
                            $stmtSide->execute([
                                $cartId,
                                $idx,
                                $side['type'],
                                $side['id'],
                                $side['qty'],
                                $side['price'] ?? 0
                            ]);
                        }
                    }

                    // 5. Guardar Acompañantes (OBSOLETO en v2 - Ahora son items independientes)
                    /*
                    if (!empty($subItem['companions'])) {
                        $stmtComp = $this->db->prepare("INSERT INTO cart_item_modifiers (cart_id, modifier_type, sub_item_index, component_type, component_id, quantity_adjustment, price_adjustment) VALUES (?, 'companion', ?, 'product', ?, ?, ?)");
                        foreach ($subItem['companions'] as $comp) {
                            $stmtComp->execute([
                                $cartId,
                                $idx,
                                $comp['id'],
                                $comp['qty'],
                                $comp['price'] ?? 0
                            ]);
                        }
                    }
                    */
                }
            }

            if ($startedTransaction) {
                $this->db->commit();
            }
            return true;
        } catch (Exception $e) {
            if ($startedTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            // Si la transacción fue iniciada por un método padre, relanzamos la excepción
            if (!$startedTransaction) {
                throw $e;
            }
            return "Error: " . $e->getMessage();
        }
    }

    // 3. OBTENER CARRITO (Lectura Limpia)
    public function getCart($user_id)
    {
        $stmt = $this->db->prepare("
            SELECT c.*, COALESCE(p.name, '[PRODUCTO NO ENCONTRADO]') as name, 
                   COALESCE(c.price_override, p.price_usd, 0) as price_usd, 
                   COALESCE(p.price_ves, 0) as price_ves, p.image_url, p.product_type, p.max_sides, p.contour_logic_type
            FROM {$this->table_name} c
            LEFT JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // --- SISTEMA DE VALIDACIÓN DE STOCK ACUMULADO ---
        $inventoryDemand = []; // [type_id => qty_needed]
        $stockStatus = [];     // [type_id => ['has_stock' => bool, 'available' => float, 'name' => string]]

        // PASO 1: Recolectar Demanda Total del Carrito
        foreach ($items as $item) {
            $qty = floatval($item['quantity']);
            $pid = $item['product_id'];

            // A. Demanda del Producto Base
            if (!isset($inventoryDemand['product_' . $pid]))
                $inventoryDemand['product_' . $pid] = 0;
            $inventoryDemand['product_' . $pid] += $qty;

            // B. Demanda de Modificadores
            $stmtM = $this->db->prepare("SELECT component_type, component_id, quantity_adjustment FROM cart_item_modifiers WHERE cart_id = ? AND modifier_type IN ('add', 'side')");
            $stmtM->execute([$item['id']]);
            foreach ($stmtM->fetchAll(PDO::FETCH_ASSOC) as $m) {
                $mType = $m['component_type'] ?: 'raw';
                $key = $mType . '_' . $m['component_id'];
                if (!isset($inventoryDemand[$key]))
                    $inventoryDemand[$key] = 0;
                // Los modificadores se multiplican por la cantidad del item principal
                $inventoryDemand[$key] += (floatval($m['quantity_adjustment'] ?: 1) * $qty);
            }
        }

        // PASO 2: Verificar Disponibilidad (Física o Virtual)
        $pMan = new ProductManager($this->db);
        foreach ($inventoryDemand as $key => $needed) {
            list($type, $id) = explode('_', $key);
            $available = 0;
            $name = "Item";

            if ($type === 'product') {
                $pData = $pMan->getProductById($id);
                $name = $pData['name'] ?? 'Producto';
                if ($pData['product_type'] === 'simple') {
                    $available = floatval($pData['stock']);
                } else {
                    $analysis = $pMan->getVirtualStockAnalysis($id);
                    $available = $analysis['max_produceable'];
                }
            } elseif ($type === 'raw') {
                $s = $this->db->prepare("SELECT name, stock_quantity FROM raw_materials WHERE id = ?");
                $s->execute([$id]);
                $r = $s->fetch(PDO::FETCH_ASSOC);
                $available = floatval($r['stock_quantity'] ?? 0);
                $name = $r['name'] ?? 'Insumo';
            } elseif ($type === 'manufactured') {
                $s = $this->db->prepare("SELECT name, stock FROM manufactured_products WHERE id = ?");
                $s->execute([$id]);
                $r = $s->fetch(PDO::FETCH_ASSOC);
                $available = floatval($r['stock'] ?? 0);
                $name = $r['name'] ?? 'Preparado';
            }

            $stockStatus[$key] = [
                'has_stock' => ($available >= $needed),
                'available' => $available,
                'needed' => $needed,
                'name' => $name
            ];
        }

        // PASO 3: Asignar Diagnóstico a cada Item
        foreach ($items as &$item) {
            $item['has_stock'] = true;
            $item['stock_error'] = "";
            $pid = $item['product_id'];

            // Check base product
            if (!$stockStatus['product_' . $pid]['has_stock']) {
                $item['has_stock'] = false;
                $item['stock_error'] = "Stock insuficiente de " . $stockStatus['product_' . $pid]['name'];
            }

            // Check its modifiers (Re-fetching just for naming/error reporting)
            $stmtM = $this->db->prepare("SELECT cim.*, 
                CASE 
                    WHEN cim.component_type = 'raw' OR cim.component_type IS NULL THEN rm.name
                    WHEN cim.component_type = 'manufactured' THEN mp.name
                    WHEN cim.component_type = 'product' THEN p2.name
                END as item_name
                FROM cart_item_modifiers cim
                LEFT JOIN raw_materials rm ON cim.component_id = rm.id AND (cim.component_type = 'raw' OR cim.component_type IS NULL)
                LEFT JOIN manufactured_products mp ON cim.component_id = mp.id AND cim.component_type = 'manufactured'
                LEFT JOIN products p2 ON cim.component_id = p2.id AND cim.component_type = 'product'
                WHERE cim.cart_id = ? AND cim.modifier_type IN ('add', 'side')");
            $stmtM->execute([$item['id']]);
            $itemMods = $stmtM->fetchAll(PDO::FETCH_ASSOC);

            foreach ($itemMods as $m) {
                $mType = $m['component_type'] ?: 'raw';
                $key = $mType . '_' . $m['component_id'];
                if (!$stockStatus[$key]['has_stock']) {
                    $item['has_stock'] = false;
                    $item['stock_error'] = ($item['stock_error'] ? $item['stock_error'] . ", " : "") . "Agotado: " . $m['item_name'];
                }
            }
        } // DIAGNOSTIC LOOP END

        foreach ($items as &$item) {
            $basePrice = floatval($item['price_usd']);
            $extraPrice = 0;

            // Traer modificadores (Actualizado para incluir component_type e item_name polimórfico)
            $stmtMod = $this->db->prepare("
                SELECT cim.*,
                    CASE 
                        WHEN cim.component_type = 'raw' OR cim.component_type IS NULL THEN rm.name
                        WHEN cim.component_type = 'manufactured' THEN mp.name
                        WHEN cim.component_type = 'product' THEN p2.name
                    END as item_name
                FROM cart_item_modifiers cim
                LEFT JOIN raw_materials rm ON cim.component_id = rm.id AND (cim.component_type = 'raw' OR cim.component_type IS NULL)
                LEFT JOIN manufactured_products mp ON cim.component_id = mp.id AND cim.component_type = 'manufactured'
                LEFT JOIN products p2 ON cim.component_id = p2.id AND cim.component_type = 'product'
                WHERE cim.cart_id = ?
                ORDER BY cim.sub_item_index ASC
            ");
            $stmtMod->execute([$item['id']]);
            $rawModifiers = $stmtMod->fetchAll(PDO::FETCH_ASSOC);

            // Estructura para agrupar visualmente
            $groupedMods = [];
            $generalNote = "";

            foreach ($rawModifiers as $mod) {
                $idx = $mod['sub_item_index'];

                // Nota Global
                if ($idx == -1 && $mod['modifier_type'] == 'info') {
                    $generalNote = $mod['note'];
                    continue;
                }

                if (!isset($groupedMods[$idx])) {
                    $groupedMods[$idx] = [
                        'is_takeaway' => 0, // Default Mesa
                        'desc' => []
                    ];
                }

                if ($mod['modifier_type'] == 'info') {
                    // AQUÍ LEEMOS EL BOOLEANO DIRECTAMENTE
                    $groupedMods[$idx]['is_takeaway'] = intval($mod['is_takeaway']);
                } elseif ($mod['modifier_type'] == 'add') {
                    $extraPrice += floatval($mod['price_adjustment']);
                    $groupedMods[$idx]['desc'][] = "+ " . $mod['item_name'];
                } elseif ($mod['modifier_type'] == 'side') {
                    $extraPrice += floatval($mod['price_adjustment']);
                    $groupedMods[$idx]['desc'][] = "🔘 " . $mod['item_name'];
                } elseif ($mod['modifier_type'] == 'companion') {
                    // Lógica obsoleta para Plan v2, pero mantenemos compatibilidad por ahora si existen en DB
                    $extraPrice += floatval($mod['price_adjustment']);
                    $qtyText = floatval($mod['quantity_adjustment'] ?? 1);
                    $groupedMods[$idx]['desc'][] = "<span class='fw-bold text-dark'>📦 [legacy] {$qtyText}x " . strtoupper($mod['item_name'] ?? '') . "</span>";
                } elseif ($mod['modifier_type'] == 'remove') {
                    $groupedMods[$idx]['desc'][] = "SIN " . $mod['item_name'];
                }
            }

            // --- LÓGICA DE COMPLETITUD (is_complete) ---
            $itemIsComplete = true; // Por defecto
            $incompleteIndices = [];

            if ($item['product_type'] == 'compound') {
                $pMan = new ProductManager($this->db);
                // NOTA: Incluimos todos los tipos de componentes, no solo 'product'
                $components = $this->db->prepare("SELECT pc.id as row_id, pc.quantity, pc.component_type, 
                                                         COALESCE(p.max_sides, 0) as max_sides, 
                                                         COALESCE(p.contour_logic_type, 'standard') as contour_logic_type, 
                                                         p.id as sub_pid 
                                                  FROM product_components pc 
                                                  LEFT JOIN products p ON pc.component_id = p.id AND pc.component_type = 'product'
                                                  WHERE pc.product_id = ?");
                $components->execute([$item['product_id']]);
                $cList = $components->fetchAll(PDO::FETCH_ASSOC);

                $currentSubIdx = 0;
                foreach ($cList as $c) {
                    $q = intval($c['quantity']);
                    $max = intval($c['max_sides']);
                    $logic = $c['contour_logic_type'] ?? 'standard';

                    $ovSides = $pMan->getComponentSideOverrides($c['row_id']);
                    $hasSidesAvailable = ($max > 0 || !empty($ovSides));

                    for ($i = 0; $i < $q; $i++) {
                        if ($hasSidesAvailable) {
                            $sidesCount = 0;
                            if (isset($groupedMods[$currentSubIdx])) {
                                $sidesCount = count(array_filter($rawModifiers, function ($m) use ($currentSubIdx) {
                                    return $m['sub_item_index'] == $currentSubIdx && $m['modifier_type'] == 'side';
                                }));
                            }

                            // VALIDACIÓN SEGÚN LÓGICA
                            if ($logic === 'standard') {
                                // En standard, el max manda
                                $effectiveMax = (!empty($ovSides)) ? min($max, count($ovSides)) : $max;
                                if ($sidesCount < $effectiveMax) {
                                    $itemIsComplete = false;
                                    $incompleteIndices[] = $currentSubIdx;
                                }
                            } else {
                                // En proporcional (proportional), debe haber al menos 1 seleccionado si hay opciones
                                if ($sidesCount < 1) {
                                    $itemIsComplete = false;
                                    $incompleteIndices[] = $currentSubIdx;
                                }
                            }
                        }
                        $currentSubIdx++;
                    }
                }
            } else {
                // Producto Simple
                $max = intval($item['max_sides']);
                $logic = $item['contour_logic_type'] ?? 'standard';
                $sidesCount = count(array_filter($rawModifiers, function ($m) {
                    return $m['sub_item_index'] == 0 && $m['modifier_type'] == 'side';
                }));

                if ($max > 0) {
                    if ($logic === 'standard') {
                        if ($sidesCount < $max) {
                            $itemIsComplete = false;
                            $incompleteIndices[] = 0;
                        }
                    } else {
                        // Proportional
                        if ($sidesCount < 1) {
                            $itemIsComplete = false;
                            $incompleteIndices[] = 0;
                        }
                    }
                }
            }
            $item['is_complete'] = $itemIsComplete;
            $item['incomplete_indices'] = $incompleteIndices;

            // Generar HTML descriptivo para la tabla
            $visualDesc = [];
            foreach ($groupedMods as $idx => $data) {
                $statusTag = ($data['is_takeaway'] == 1)
                    ? '<span class="badge bg-secondary text-white" style="font-size:0.7em">LLEVAR</span>'
                    : '<span class="badge bg-info text-dark" style="font-size:0.7em">MESA</span>';

                $extrasText = empty($data['desc']) ? '' : '<br><span class="small text-muted">' . implode(', ', $data['desc']) . '</span>';

                $visualDesc[] = "<div class='mb-1'><strong>#" . ($idx + 1) . "</strong> $statusTag $extrasText</div>";
            }

            if ($generalNote) {
                $visualDesc[] = "<div class='mt-1 border-top pt-1 text-primary small'>Nota: $generalNote</div>";
            }

            $item['modifiers_grouped'] = $groupedMods; // Datos puros para JS
            $item['modifiers_desc'] = $visualDesc;     // HTML para Tabla
            $item['unit_price_final'] = $basePrice + $extraPrice;
            $item['total_price'] = $item['unit_price_final'] * $item['quantity'];
        }

        return $items;
    }

    // Métodos estándar (Sin cambios)
    public function updateCartQuantity($cartId, $quantity)
    {
        if ($quantity <= 0)
            return $this->removeFromCart($cartId);

        // 1. Obtener la cantidad anterior para calcular el ratio
        $stmtOld = $this->db->prepare("SELECT quantity FROM cart WHERE id = ?");
        $stmtOld->execute([$cartId]);
        $oldQty = floatval($stmtOld->fetchColumn() ?: 1);
        $ratio = $quantity / $oldQty;

        // 2. Actualizar el ítem principal
        $this->db->prepare("UPDATE {$this->table_name} SET quantity = ? WHERE id = ?")->execute([$quantity, $cartId]);

        // 3. Actualizar acompañantes recursivamente
        $stmtComp = $this->db->prepare("SELECT id, quantity FROM cart WHERE parent_cart_id = ?");
        $stmtComp->execute([$cartId]);
        $companions = $stmtComp->fetchAll(PDO::FETCH_ASSOC);

        foreach ($companions as $comp) {
            $newCompQty = floatval($comp['quantity']) * $ratio;
            $this->updateCartQuantity($comp['id'], $newCompQty);
        }

        return true;
    }

    public function removeFromCart($cartId)
    {
        // 1. Borrado en cascada de los acompañantes vinculados
        $stmtComp = $this->db->prepare("SELECT id FROM cart WHERE parent_cart_id = ?");
        $stmtComp->execute([$cartId]);
        $companions = $stmtComp->fetchAll(PDO::FETCH_ASSOC);
        foreach ($companions as $comp) {
            $this->removeFromCart($comp['id']);
        }

        // 2. Borrar modificadores
        $this->db->prepare("DELETE FROM cart_item_modifiers WHERE cart_id = ?")->execute([$cartId]);

        // 3. Borrar el ítem
        return $this->db->prepare("DELETE FROM {$this->table_name} WHERE id = ?")->execute([$cartId]);
    }

    public function emptyCart($user_id)
    {
        $inTransaction = $this->db->inTransaction();
        try {
            if (!$inTransaction)
                $this->db->beginTransaction();

            $this->db->prepare("DELETE cim FROM cart_item_modifiers cim INNER JOIN cart c ON cim.cart_id = c.id WHERE c.user_id = ?")->execute([$user_id]);
            $this->db->prepare("DELETE FROM {$this->table_name} WHERE user_id = ?")->execute([$user_id]);

            if (!$inTransaction)
                $this->db->commit();
            return true;
        } catch (Exception $e) {
            if (!$inTransaction)
                $this->db->rollBack();
            throw $e;
        }
    }

    public function calculateTotal($cart_items)
    {
        $total_usd = 0;
        foreach ($cart_items as $item)
            $total_usd += $item['total_price'];
        $rate = isset($GLOBALS['config']) ? $GLOBALS['config']->get('exchange_rate') : 1;
        return ['total_usd' => $total_usd, 'total_ves' => $total_usd * $rate];
    }
}
?>