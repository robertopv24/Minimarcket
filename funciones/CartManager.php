<?php

class CartManager {
    private $db;
    private $table_name = 'cart';

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * 1. AGREGAR AL CARRITO (Soporte Relacional)
     * Crea una nueva línea en 'cart' y guarda modificadores si los hay.
     * Siempre crea fila nueva para permitir personalización individual.
     */
    public function addToCart($user_id, $product_id, $quantity, $modifiers = [], $consumptionType = 'takeaway') {
        try {
            $this->db->beginTransaction();

            // A. Validar Stock del Producto Base
            $productManager = new ProductManager($this->db);
            $product = $productManager->getProductById($product_id);

            if (!$product) {
                throw new Exception("Producto no encontrado.");
            }

            // Validación de Stock Inteligente (Físico vs Virtual)
            $availableStock = 0;
            if ($product['product_type'] === 'simple') {
                $availableStock = intval($product['stock']);
            } else {
                $availableStock = $productManager->getVirtualStock($product_id);
            }

            if ($availableStock < $quantity) {
                throw new Exception("Stock insuficiente. Disponible: " . $availableStock);
            }

            // B. Insertar en tabla CART
            // IMPORTANTE: Guardamos consumption_type ('dine_in' o 'takeaway')
            $stmt = $this->db->prepare("INSERT INTO {$this->table_name} (user_id, product_id, quantity, consumption_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $product_id, $quantity, $consumptionType]);
            $cartId = $this->db->lastInsertId();

            // C. Insertar Modificadores en Tabla Relacional (Si vienen en el array)
            if (!empty($modifiers)) {
                $this->insertModifiers($cartId, $modifiers);
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            return "Error: " . $e->getMessage();
        }
    }

    /**
     * 2. ACTUALIZAR MODIFICADORES (Edición desde el Carrito)
     * Borra los antiguos y pone los nuevos.
     */
    public function updateItemModifiers($cartId, $modifiers) {
        try {
            $this->db->beginTransaction();

            // Borrar anteriores
            $this->db->prepare("DELETE FROM cart_item_modifiers WHERE cart_id = ?")->execute([$cartId]);

            // Insertar nuevos
            if (!empty($modifiers)) {
                $this->insertModifiers($cartId, $modifiers);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return "Error: " . $e->getMessage();
        }
    }

    // Auxiliar privado para insertar en tabla relacional cart_item_modifiers
    private function insertModifiers($cartId, $modifiers) {
        // Remociones (Precio 0)
        if (!empty($modifiers['remove'])) {
            $stmt = $this->db->prepare("INSERT INTO cart_item_modifiers (cart_id, modifier_type, raw_material_id, price_adjustment) VALUES (?, 'remove', ?, 0.00)");
            foreach ($modifiers['remove'] as $rawId) {
                $stmt->execute([$cartId, $rawId]);
            }
        }
        // Adiciones (Con Precio y Cantidad)
        if (!empty($modifiers['add'])) {
            $stmt = $this->db->prepare("INSERT INTO cart_item_modifiers (cart_id, modifier_type, raw_material_id, quantity_adjustment, price_adjustment) VALUES (?, 'add', ?, ?, ?)");

            foreach ($modifiers['add'] as $extra) {
                // Soporta formato simple ID o array ['id'=>1, 'price'=>1.00]
                $rawId = is_array($extra) ? $extra['id'] : $extra;
                $price = is_array($extra) ? ($extra['price'] ?? 1.00) : 1.00;
                $qtyAdj = 0.050; // Cantidad estándar de un extra (50g)

                $stmt->execute([$cartId, $rawId, $qtyAdj, $price]);
            }
        }
    }

    /**
     * 3. ACTUALIZAR TIPO DE CONSUMO (Para llevar / Local)
     */
    public function updateConsumptionType($cartId, $type) {
        $stmt = $this->db->prepare("UPDATE {$this->table_name} SET consumption_type = ? WHERE id = ?");
        return $stmt->execute([$type, $cartId]);
    }

    /**
     * 4. OBTENER CARRITO CON DETALLES
     * Recupera el producto y sus modificadores, calculando el precio final.
     */
    public function getCart($user_id) {
        // Obtener items principales
        $stmt = $this->db->prepare("
            SELECT c.*, p.name, p.price_usd, p.price_ves, p.image_url, p.product_type
            FROM {$this->table_name} c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enriquecer con modificadores y calcular
        foreach ($items as &$item) {
            $basePrice = floatval($item['price_usd']);
            $extraPrice = 0;

            // Buscar modificadores en la tabla relacional
            $stmtMod = $this->db->prepare("
                SELECT cim.*, rm.name as material_name
                FROM cart_item_modifiers cim
                JOIN raw_materials rm ON cim.raw_material_id = rm.id
                WHERE cim.cart_id = ?
            ");
            $stmtMod->execute([$item['id']]);
            $modifiers = $stmtMod->fetchAll(PDO::FETCH_ASSOC);

            // Calcular costo de extras y formatear texto para la vista
            $modDescription = [];
            foreach ($modifiers as $mod) {
                if ($mod['modifier_type'] == 'add') {
                    $extraPrice += floatval($mod['price_adjustment']);
                    $modDescription[] = "+ " . $mod['material_name'] . " ($" . number_format($mod['price_adjustment'], 2) . ")";
                } elseif ($mod['modifier_type'] == 'remove') {
                    $modDescription[] = "SIN " . $mod['material_name'];
                }
            }

            $item['modifiers_list'] = $modifiers; // Data cruda
            $item['modifiers_desc'] = $modDescription; // Texto para HTML

            // Precios Finales
            $item['unit_price_final'] = $basePrice + $extraPrice;
            $item['total_price'] = $item['unit_price_final'] * $item['quantity'];
        }

        return $items;
    }

    /**
     * 5. ACTUALIZAR CANTIDAD
     * ¡OJO! Usa 'cart_id' (ID único de la fila), no 'product_id'.
     */
    public function updateCartQuantity($cartId, $quantity) {
        if ($quantity <= 0) {
            return $this->removeFromCart($cartId);
        }
        try {
            // Validar stock del producto asociado
            $stmt = $this->db->prepare("SELECT p.id, p.stock, p.product_type FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ?");
            $stmt->execute([$cartId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) return "Producto no encontrado.";

            // Validación Híbrida
            $productManager = new ProductManager($this->db);
            $availableStock = ($product['product_type'] === 'simple')
                ? intval($product['stock'])
                : $productManager->getVirtualStock($product['id']);

            if ($availableStock < $quantity) {
                return "Error: Stock insuficiente (Máx: $availableStock).";
            }

            $update = $this->db->prepare("UPDATE {$this->table_name} SET quantity = ? WHERE id = ?");
            $update->execute([$quantity, $cartId]);
            return true;

        } catch (PDOException $e) {
            return "Error al actualizar cantidad.";
        }
    }

    /**
     * 6. ELIMINAR ITEM
     * Usa 'cart_id'. Borra modificadores primero.
     */
    public function removeFromCart($cartId) {
        $this->db->prepare("DELETE FROM cart_item_modifiers WHERE cart_id = ?")->execute([$cartId]);
        $stmt = $this->db->prepare("DELETE FROM {$this->table_name} WHERE id = ?");
        return $stmt->execute([$cartId]);
    }

    /**
     * 7. VACIAR CARRITO
     */
    public function emptyCart($user_id) {
        // Borrar modificadores de todos los items del usuario
        $sql = "DELETE cim FROM cart_item_modifiers cim
                INNER JOIN cart c ON cim.cart_id = c.id
                WHERE c.user_id = ?";
        $this->db->prepare($sql)->execute([$user_id]);

        // Borrar items
        $stmt = $this->db->prepare("DELETE FROM {$this->table_name} WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    }

    /**
     * 8. CALCULAR TOTALES
     */
    public function calculateTotal($cart_items) {
        $total_usd = 0;
        foreach ($cart_items as $item) {
            $total_usd += $item['total_price'];
        }

        $rate = isset($GLOBALS['config']) ? $GLOBALS['config']->get('exchange_rate') : 1;
        $total_ves = $total_usd * $rate;

        return ['total_usd' => $total_usd, 'total_ves' => $total_ves];
    }
}
?>
