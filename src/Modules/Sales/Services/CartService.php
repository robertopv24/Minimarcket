<?php

namespace Minimarcket\Modules\Sales\Services;

use Minimarcket\Core\Database;
use Minimarcket\Modules\Inventory\Services\ProductService;
use PDO;
use Exception;

class CartService
{
    private $db;
    private $table_name = 'cart';
    private $productService;

    public function __construct(?PDO $db = null, ?ProductService $productService = null)
    {
        $this->db = $db ?? Database::getConnection();
        // Determine how to get ProductService. In full DI cache it would be injected.
        // For now, if not provided, we instantiate it.
        $this->productService = $productService ?? new ProductService($this->db);
    }

    public function addToCart($user_id, $product_id, $quantity, $modifiers = [], $consumptionType = 'dine_in')
    {
        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
            }

            $product = $this->productService->getProductById($product_id);

            if (!$product)
                throw new Exception("Producto no encontrado.");

            $availableStock = ($product['product_type'] === 'simple')
                ? intval($product['stock'])
                : $this->productService->getVirtualStock($product_id);

            if ($availableStock < $quantity)
                throw new Exception("Stock insuficiente.");

            $stmt = $this->db->prepare("INSERT INTO {$this->table_name} (user_id, product_id, quantity, consumption_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $product_id, $quantity, $consumptionType]);
            $cartId = $this->db->lastInsertId();

            if (!empty($modifiers)) {
                $this->updateItemModifiers($cartId, $modifiers);
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return "Error: " . $e->getMessage();
        }
    }

    public function updateItemModifiers($cartId, $data)
    {
        try {
            if (!$this->db->inTransaction())
                $this->db->beginTransaction();

            $this->db->prepare("DELETE FROM cart_item_modifiers WHERE cart_id = ?")->execute([$cartId]);

            if (!empty($data['general_note'])) {
                $stmt = $this->db->prepare("INSERT INTO cart_item_modifiers (cart_id, modifier_type, sub_item_index, note) VALUES (?, 'info', -1, ?)");
                $stmt->execute([$cartId, $data['general_note']]);
            }

            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $subItem) {
                    $idx = $subItem['index'];
                    $isTakeaway = ($subItem['consumption'] === 'takeaway') ? 1 : 0;

                    $stmtState = $this->db->prepare("INSERT INTO cart_item_modifiers (cart_id, modifier_type, sub_item_index, is_takeaway) VALUES (?, 'info', ?, ?)");
                    $stmtState->execute([$cartId, $idx, $isTakeaway]);

                    if (!empty($subItem['remove'])) {
                        $stmtRem = $this->db->prepare("INSERT INTO cart_item_modifiers (cart_id, modifier_type, sub_item_index, raw_material_id) VALUES (?, 'remove', ?, ?)");
                        foreach ($subItem['remove'] as $rawId) {
                            $stmtRem->execute([$cartId, $idx, $rawId]);
                        }
                    }

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
            if ($this->db->inTransaction())
                $this->db->rollBack();
            return "Error: " . $e->getMessage();
        }
    }

    public function getCart($user_id)
    {
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

            $stmtMod = $this->db->prepare("
                SELECT cim.*, rm.name as material_name
                FROM cart_item_modifiers cim
                LEFT JOIN raw_materials rm ON cim.raw_material_id = rm.id
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
                    $groupedMods[$idx] = [
                        'is_takeaway' => 0,
                        'desc' => []
                    ];
                }

                if ($mod['modifier_type'] == 'info') {
                    $groupedMods[$idx]['is_takeaway'] = intval($mod['is_takeaway']);
                } elseif ($mod['modifier_type'] == 'add') {
                    $extraPrice += floatval($mod['price_adjustment']);
                    $groupedMods[$idx]['desc'][] = "+ " . $mod['material_name'];
                } elseif ($mod['modifier_type'] == 'remove') {
                    $groupedMods[$idx]['desc'][] = "SIN " . $mod['material_name'];
                }
            }

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
        $this->db->prepare("UPDATE {$this->table_name} SET quantity = ? WHERE id = ?")->execute([$quantity, $cartId]);
        return true;
    }

    public function removeFromCart($cartId)
    {
        $this->db->prepare("DELETE FROM cart_item_modifiers WHERE cart_id = ?")->execute([$cartId]);
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

        // Config handling is tricky. In new architecture we might inject ConfigService.
        // For now, let's use global config if available or default.
        $rate = 1;
        global $config;
        if (isset($config) && method_exists($config, 'get')) {
            $rate = $config->get('exchange_rate');
        } elseif (isset($_ENV['EXCHANGE_RATE'])) {
            $rate = $_ENV['EXCHANGE_RATE'];
        }

        return ['total_usd' => $total_usd, 'total_ves' => $total_usd * $rate];
    }
}
