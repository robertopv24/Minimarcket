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
    public function addToCart($user_id, $product_id, $quantity, $modifiers = [], $consumptionType = 'dine_in', $parentCartId = null, $priceOverride = null, $skipAutoCompanions = false)
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
            if ($parentCartId === null && !$skipAutoCompanions) {
                $companions = $productManager->getCompanions($product_id);
                foreach ($companions as $comp) {
                    $compQty = $quantity * floatval($comp['quantity']);
                    $compPrice = $comp['price_override']; 

                    $this->addToCart($user_id, $comp['companion_id'], $compQty, [], $consumptionType, $cartId, $compPrice);

                    $newCompanionCartId = $this->db->lastInsertId();

                    $stmtLink = $this->db->prepare("INSERT INTO cart_item_modifiers (cart_id, modifier_type, sub_item_index, component_id, component_type, note) VALUES (?, 'companion_origin', -1, ?, 'setup', 'Linked to ProductCompanion')");
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
                $stmt = $this->db->prepare("INSERT INTO cart_item_modifiers (cart_id, modifier_type, sub_item_index, note) VALUES (?, 'info', -1, ?)");
                $stmt->execute([$cartId, $data['general_note']]);
            }

            // C. Guardar Configuración por Ítem
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $subItem) {
                    $idx = $subItem['index'];
                    $isTakeaway = ($subItem['consumption'] === 'takeaway') ? 1 : 0;
                    $itemNote = $subItem['note'] ?? null;

                    $stmtState = $this->db->prepare("INSERT INTO cart_item_modifiers (cart_id, modifier_type, sub_item_index, is_takeaway, note) VALUES (?, 'info', ?, ?, ?)");
                    $stmtState->execute([$cartId, $idx, $isTakeaway, $itemNote]);

                    if (!empty($subItem['remove'])) {
                        $stmtRem = $this->db->prepare("INSERT INTO cart_item_modifiers (cart_id, modifier_type, sub_item_index, component_id, component_type) VALUES (?, 'remove', ?, ?, 'raw')");
                        foreach ($subItem['remove'] as $rawId) {
                            $stmtRem->execute([$cartId, $idx, $rawId]);
                        }
                    }

                    if (!empty($subItem['add'])) {
                        $stmtAdd = $this->db->prepare("INSERT INTO cart_item_modifiers (cart_id, modifier_type, sub_item_index, component_id, component_type, quantity_adjustment, price_adjustment) VALUES (?, 'add', ?, ?, ?, ?, ?)");
                        foreach ($subItem['add'] as $extra) {
                            $type = $extra['type'] ?? 'raw';
                            $qty = $extra['qty'] ?? 1.00;
                            $stmtAdd->execute([$cartId, $idx, $extra['id'], $type, $qty, $extra['price']]);
                        }
                    }

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

        $inventoryDemand = []; 
        $stockStatus = [];     

        foreach ($items as $item) {
            $qty = floatval($item['quantity']);
            $pid = $item['product_id'];

            if (!isset($inventoryDemand['product_' . $pid]))
                $inventoryDemand['product_' . $pid] = 0;
            $inventoryDemand['product_' . $pid] += $qty;

            $stmtM = $this->db->prepare("SELECT component_type, component_id, quantity_adjustment FROM cart_item_modifiers WHERE cart_id = ? AND modifier_type IN ('add', 'side')");
            $stmtM->execute([$item['id']]);
            foreach ($stmtM->fetchAll(PDO::FETCH_ASSOC) as $m) {
                $mType = $m['component_type'] ?: 'raw';
                $key = $mType . '_' . $m['component_id'];
                if (!isset($inventoryDemand[$key]))
                    $inventoryDemand[$key] = 0;
                $inventoryDemand[$key] += (floatval($m['quantity_adjustment'] ?: 1) * $qty);
            }
        }

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

        foreach ($items as &$item) {
            $item['has_stock'] = true;
            $item['stock_error'] = "";
            $pid = $item['product_id'];

            if (!$stockStatus['product_' . $pid]['has_stock']) {
                $item['has_stock'] = false;
                $item['stock_error'] = "Stock insuficiente de " . $stockStatus['product_' . $pid]['name'];
            }

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
        } 

        foreach ($items as &$item) {
            $basePrice = floatval($item['price_usd']);
            $extraPrice = 0;

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

            $groupedMods = [];
            $generalNote = "";

            foreach ($rawModifiers as $mod) {
                $idx = $mod['sub_item_index'];
                if ($idx == -1 && $mod['modifier_type'] == 'info') {
                    $generalNote = $mod['note'];
                    continue;
                }
                if (!isset($groupedMods[$idx])) {
                    $groupedMods[$idx] = ['is_takeaway' => 0, 'desc' => []];
                }
                if ($mod['modifier_type'] == 'info') {
                    $groupedMods[$idx]['is_takeaway'] = intval($mod['is_takeaway']);
                } elseif ($mod['modifier_type'] == 'add') {
                    $extraPrice += floatval($mod['price_adjustment']);
                    $groupedMods[$idx]['desc'][] = "+ " . $mod['item_name'];
                } elseif ($mod['modifier_type'] == 'side') {
                    $extraPrice += floatval($mod['price_adjustment']);
                    $groupedMods[$idx]['desc'][] = "🔘 " . $mod['item_name'];
                } elseif ($mod['modifier_type'] == 'remove') {
                    $groupedMods[$idx]['desc'][] = "SIN " . $mod['item_name'];
                }
            }

            $itemIsComplete = true; 
            $incompleteIndices = [];

            if ($item['product_type'] == 'compound') {
                $pMan = new ProductManager($this->db);
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
                            if ($logic === 'standard') {
                                $effectiveMax = (!empty($ovSides)) ? min($max, count($ovSides)) : $max;
                                if ($sidesCount < $effectiveMax) {
                                    $itemIsComplete = false;
                                    $incompleteIndices[] = $currentSubIdx;
                                }
                            } else {
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
                        if ($sidesCount < 1) {
                            $itemIsComplete = false;
                            $incompleteIndices[] = 0;
                        }
                    }
                }
            }
            $item['is_complete'] = $itemIsComplete;
            $item['incomplete_indices'] = $incompleteIndices;

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
            $item['modifiers_grouped'] = $groupedMods;
            $item['modifiers_desc'] = $visualDesc;
            $item['unit_price_final'] = $basePrice + $extraPrice;
            $item['total_price'] = $item['unit_price_final'] * $item['quantity'];
        }
        return $items;
    }

    public function updateCartQuantity($cartId, $quantity)
    {
        if ($quantity <= 0)
            return $this->removeFromCart($cartId);

        $stmtOld = $this->db->prepare("SELECT quantity FROM cart WHERE id = ?");
        $stmtOld->execute([$cartId]);
        $oldQty = floatval($stmtOld->fetchColumn() ?: 1);
        $ratio = $quantity / $oldQty;

        $this->db->prepare("UPDATE {$this->table_name} SET quantity = ? WHERE id = ?")->execute([$quantity, $cartId]);

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
        $stmtComp = $this->db->prepare("SELECT id FROM cart WHERE parent_cart_id = ?");
        $stmtComp->execute([$cartId]);
        $companions = $stmtComp->fetchAll(PDO::FETCH_ASSOC);
        foreach ($companions as $comp) {
            $this->removeFromCart($comp['id']);
        }
        $this->db->prepare("DELETE FROM cart_item_modifiers WHERE cart_id = ?")->execute([$cartId]);
        return $this->db->prepare("DELETE FROM {$this->table_name} WHERE id = ?")->execute([$cartId]);
    }

    public function emptyCart($user_id)
    {
        $inTransaction = $this->db->inTransaction();
        try {
            if (!$inTransaction) $this->db->beginTransaction();
            $this->db->prepare("DELETE cim FROM cart_item_modifiers cim INNER JOIN cart c ON cim.cart_id = c.id WHERE c.user_id = ?")->execute([$user_id]);
            $this->db->prepare("DELETE FROM {$this->table_name} WHERE user_id = ?")->execute([$user_id]);
            if (!$inTransaction) $this->db->commit();
            return true;
        } catch (Exception $e) {
            if (!$inTransaction) $this->db->rollBack();
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

    public function loadOrderIntoCart($user_id, $orderId)
    {
        $orderManager = new OrderManager($this->db);
        $items = $orderManager->getOrderItems($orderId);
        $orderData = $orderManager->getOrderById($orderId);

        if ($orderData) {
            // Restaurar Cliente
            if (!empty($orderData['client_id'])) {
                $_SESSION['pos_client_id'] = $orderData['client_id'];
                $stmt = $this->db->prepare("SELECT name FROM clients WHERE id = ?");
                $stmt->execute([$orderData['client_id']]);
                $_SESSION['pos_client_name'] = $stmt->fetchColumn() ?: 'Cliente';
                unset($_SESSION['pos_employee_id']);
                unset($_SESSION['pos_employee_name']);
            } 
            // Restaurar Empleado
            elseif (!empty($orderData['employee_id'])) {
                $_SESSION['pos_employee_id'] = $orderData['employee_id'];
                $stmt = $this->db->prepare("SELECT name FROM users WHERE id = ?");
                $stmt->execute([$orderData['employee_id']]);
                $_SESSION['pos_employee_name'] = $stmt->fetchColumn() ?: 'Empleado';
                unset($_SESSION['pos_client_id']);
                unset($_SESSION['pos_client_name']);
            }
        }

        foreach ($items as $item) {
            $modData = ['items' => []];
            $rawMods = $orderManager->getItemModifiers($item['id']);
            foreach ($rawMods as $m) {
                $idx = $m['sub_item_index'];
                if ($idx == -1) {
                    if ($m['modifier_type'] == 'info') $modData['general_note'] = $m['note'];
                    continue;
                }
                if (!isset($modData['items'][$idx])) {
                    $modData['items'][$idx] = [
                        'index' => $idx,
                        'consumption' => ($m['is_takeaway'] == 1) ? 'takeaway' : 'dine_in',
                        'note' => $m['note'] ?? null,
                        'remove' => [], 'add' => [], 'sides' => []
                    ];
                }
                if ($m['modifier_type'] == 'add') {
                    $modData['items'][$idx]['add'][] = ['id' => $m['component_id'], 'type' => $m['component_type'], 'qty' => $m['quantity_adjustment'], 'price' => $m['price_adjustment_usd']];
                } elseif ($m['modifier_type'] == 'side') {
                    $modData['items'][$idx]['sides'][] = ['id' => $m['component_id'], 'type' => $m['component_type'], 'qty' => $m['quantity_adjustment'], 'price' => $m['price_adjustment_usd']];
                } elseif ($m['modifier_type'] == 'remove') {
                    $modData['items'][$idx]['remove'][] = $m['component_id'];
                }
            }
            $this->addToCart($user_id, $item['product_id'], $item['quantity'], $modData, $item['consumption_type'], null, null, true);
        }
        return true;
    }

    public function syncDeliveryItem($userId, $tier, $config)
    {
        $stmtD = $this->db->prepare("SELECT id FROM products WHERE name = 'Servicio Delivery' LIMIT 1");
        $stmtD->execute();
        $dId = $stmtD->fetchColumn();

        if (!$dId) {
            $stmtCat = $this->db->prepare("SELECT id FROM categories WHERE name = 'DOMICILIO' LIMIT 1");
            $stmtCat->execute();
            $catId = $stmtCat->fetchColumn();
            if (!$catId) {
                $this->db->prepare("INSERT INTO categories (name, kitchen_station, icon, description) VALUES ('DOMICILIO', 'none', 'fa-truck', 'Gastos de envío')")->execute();
                $catId = $this->db->lastInsertId();
            }
            $this->db->prepare("INSERT INTO products (name, description, price_usd, price_ves, product_type, category_id, stock, is_visible, kitchen_station, created_at) 
                         VALUES ('Servicio Delivery', 'Servicio de entrega a domicilio', 0, 0, 'simple', ?, 9999, 0, '', NOW())")->execute([$catId]);
            $dId = $this->db->lastInsertId();
        }

        $base = floatval($config->get('delivery_base_cost', 0));
        $fee = ($tier === 'C') ? ($base * 2) : ($tier === 'B' ? $base : 0);

        if ($fee > 0) {
            $stmtCheck = $this->db->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
            $stmtCheck->execute([$userId, $dId]);
            $cartId = $stmtCheck->fetchColumn();

            if ($cartId) {
                $this->db->prepare("UPDATE cart SET price_override = ?, quantity = 1, consumption_type = 'delivery' WHERE id = ?")->execute([$fee, $cartId]);
            } else {
                $this->db->prepare("INSERT INTO cart (user_id, product_id, quantity, consumption_type, price_override) VALUES (?, ?, 1, 'delivery', ?)")
                     ->execute([$userId, $dId, $fee]);
            }
        } else {
            $this->db->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?")->execute([$userId, $dId]);
        }
        return true;
    }
}
?>