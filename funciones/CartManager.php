<?php

class CartManager {
    private $db;
    private $table_name = 'cart';

    public function __construct($db) {
        $this->db = $db;
    }

    // 1. AGREGAR AL CARRITO
    public function addToCart($user_id, $product_id, $quantity, $modifiers = [], $consumptionType = 'dine_in') {
        try {
            $this->db->beginTransaction();

            $productManager = new ProductManager($this->db);
            $product = $productManager->getProductById($product_id);

            if (!$product) throw new Exception("Producto no encontrado.");

            $availableStock = ($product['product_type'] === 'simple')
                ? intval($product['stock'])
                : $productManager->getVirtualStock($product_id);

            if ($availableStock < $quantity) throw new Exception("Stock insuficiente.");

            $stmt = $this->db->prepare("INSERT INTO {$this->table_name} (user_id, product_id, quantity, consumption_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $product_id, $quantity, $consumptionType]);
            $cartId = $this->db->lastInsertId();

            if (!empty($modifiers)) {
                $this->updateItemModifiers($cartId, $modifiers);
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            return "Error: " . $e->getMessage();
        }
    }

    // 2. ACTUALIZAR MODIFICADORES (Lógica Simplificada)
    public function updateItemModifiers($cartId, $data) {
        try {
            if (!$this->db->inTransaction()) $this->db->beginTransaction();

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

                    // 1. GUARDAR ESTADO BOOLEANO (La clave de la simplificación)
                    // is_takeaway: 1 = Llevar, 0 = Mesa
                    $isTakeaway = ($subItem['consumption'] === 'takeaway') ? 1 : 0;

                    // Guardamos una fila 'info' que contiene el estado booleano
                    $stmtState = $this->db->prepare("INSERT INTO cart_item_modifiers (cart_id, modifier_type, sub_item_index, is_takeaway) VALUES (?, 'info', ?, ?)");
                    $stmtState->execute([$cartId, $idx, $isTakeaway]);

                    // 2. Guardar Remociones
                    if (!empty($subItem['remove'])) {
                        $stmtRem = $this->db->prepare("INSERT INTO cart_item_modifiers (cart_id, modifier_type, sub_item_index, raw_material_id) VALUES (?, 'remove', ?, ?)");
                        foreach ($subItem['remove'] as $rawId) {
                            $stmtRem->execute([$cartId, $idx, $rawId]);
                        }
                    }

                    // 3. Guardar Adiciones
                    if (!empty($subItem['add'])) {
                        $stmtAdd = $this->db->prepare("INSERT INTO cart_item_modifiers (cart_id, modifier_type, sub_item_index, raw_material_id, quantity_adjustment, price_adjustment) VALUES (?, 'add', ?, ?, ?, ?)");
                        foreach ($subItem['add'] as $extra) {
                            $stmtAdd->execute([$cartId, $idx, $extra['id'], 0.050, $extra['price']]);
                        }
                    }
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return "Error: " . $e->getMessage();
        }
    }

    // 3. OBTENER CARRITO (Lectura Limpia)
    public function getCart($user_id) {
        $stmt = $this->db->prepare("
            SELECT c.*, p.name, p.price_usd, p.price_ves, p.image_url, p.product_type
            FROM {$this->table_name} c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as &$item) {
            $basePrice = floatval($item['price_usd']);
            $extraPrice = 0;

            // Traer modificadores
            $stmtMod = $this->db->prepare("
                SELECT cim.*, rm.name as material_name
                FROM cart_item_modifiers cim
                LEFT JOIN raw_materials rm ON cim.raw_material_id = rm.id
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
                }
                elseif ($mod['modifier_type'] == 'add') {
                    $extraPrice += floatval($mod['price_adjustment']);
                    $groupedMods[$idx]['desc'][] = "+ " . $mod['material_name'];
                }
                elseif ($mod['modifier_type'] == 'remove') {
                    $groupedMods[$idx]['desc'][] = "SIN " . $mod['material_name'];
                }
            }

            // Generar HTML descriptivo para la tabla
            $visualDesc = [];
            foreach ($groupedMods as $idx => $data) {
                $statusTag = ($data['is_takeaway'] == 1)
                    ? '<span class="badge bg-secondary text-white" style="font-size:0.7em">LLEVAR</span>'
                    : '<span class="badge bg-info text-dark" style="font-size:0.7em">MESA</span>';

                $extrasText = empty($data['desc']) ? '' : '<br><span class="small text-muted">' . implode(', ', $data['desc']) . '</span>';

                $visualDesc[] = "<div class='mb-1'><strong>#".($idx+1)."</strong> $statusTag $extrasText</div>";
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
    public function updateCartQuantity($cartId, $quantity) {
        if ($quantity <= 0) return $this->removeFromCart($cartId);
        $this->db->prepare("UPDATE {$this->table_name} SET quantity = ? WHERE id = ?")->execute([$quantity, $cartId]);
        return true;
    }
    
    public function removeFromCart($cartId) {
        $this->db->prepare("DELETE FROM cart_item_modifiers WHERE cart_id = ?")->execute([$cartId]);
        return $this->db->prepare("DELETE FROM {$this->table_name} WHERE id = ?")->execute([$cartId]);
    }

    public function emptyCart($user_id) {
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

    public function calculateTotal($cart_items) {
        $total_usd = 0;
        foreach ($cart_items as $item) $total_usd += $item['total_price'];
        $rate = isset($GLOBALS['config']) ? $GLOBALS['config']->get('exchange_rate') : 1;
        return ['total_usd' => $total_usd, 'total_ves' => $total_usd * $rate];
    }
}
?>
