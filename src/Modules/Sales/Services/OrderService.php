<?php

namespace Minimarcket\Modules\Sales\Services;

use Minimarcket\Core\Database;
use PDO;
use Exception;
use PDOException;

class OrderService
{
    private $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function createOrder($user_id, $items, $shipping_address, $shipping_method = null)
    {
        $inTransaction = $this->db->inTransaction();

        try {
            if (!$inTransaction) {
                $this->db->beginTransaction();
            }

            $total_price = 0;
            foreach ($items as $item) {
                $price = $item['unit_price_final'] ?? $item['price'];
                $total_price += $price * $item['quantity'];
            }

            $stmt = $this->db->prepare("INSERT INTO orders (user_id, total_price, shipping_address, shipping_method, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
            $stmt->execute([$user_id, $total_price, $shipping_address, $shipping_method]);
            $order_id = $this->db->lastInsertId();

            $stmtItem = $this->db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, consumption_type) VALUES (?, ?, ?, ?, ?)");

            $sqlCopyMods = "INSERT INTO order_item_modifiers
                            (order_item_id, modifier_type, raw_material_id, quantity_adjustment, price_adjustment_usd, note, sub_item_index, is_takeaway)
                            SELECT ?, modifier_type, raw_material_id, quantity_adjustment, price_adjustment, note, sub_item_index, is_takeaway
                            FROM cart_item_modifiers
                            WHERE cart_id = ?";

            $stmtCopy = $this->db->prepare($sqlCopyMods);

            foreach ($items as $item) {
                $price = $item['unit_price_final'] ?? $item['price'];
                $cType = $item['consumption_type'] ?? 'dine_in';

                $stmtItem->execute([$order_id, $item['product_id'], $item['quantity'], $price, $cType]);
                $order_item_id = $this->db->lastInsertId();

                if (isset($item['id'])) {
                    $stmtCopy->execute([$order_item_id, $item['id']]);
                }
            }

            if (!$inTransaction) {
                $this->db->commit();
            }
            return $order_id;

        } catch (PDOException $e) {
            if (!$inTransaction) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function deductStockFromSale($orderId)
    {
        // TODO: Move this logic to InventoryModule in Phase 4 via Events
        $sql = "SELECT oi.id as order_item_id, oi.product_id, oi.quantity
                FROM order_items oi
                WHERE oi.order_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            $this->processProductDeduction($item['product_id'], $item['quantity'], $item['order_item_id']);
        }
    }

    private function processProductDeduction($productId, $qty, $orderItemId = null, $targetIndex = null)
    {
        $stmt = $this->db->prepare("SELECT product_type, linked_manufactured_id, stock FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product)
            return;

        $stockDeducted = false;

        if (!empty($product['linked_manufactured_id'])) {
            $this->updateStock('manufactured_products', $product['linked_manufactured_id'], $qty);
            $stockDeducted = true;
        } elseif ($product['product_type'] === 'simple') {
            $this->updateStock('products', $productId, $qty);
            $stockDeducted = true;
        }

        if (!$stockDeducted) {
            $stmtComp = $this->db->prepare("SELECT component_type, component_id, quantity FROM product_components WHERE product_id = ?");
            $stmtComp->execute([$productId]);
            $components = $stmtComp->fetchAll(PDO::FETCH_ASSOC);

            $subItemIndex = 0;

            foreach ($components as $comp) {
                $totalNeeded = $comp['quantity'] * $qty;

                if ($comp['component_type'] == 'product') {
                    $baseOffset = $targetIndex !== null ? $targetIndex : 0;
                    for ($i = 0; $i < $totalNeeded; $i++) {
                        $currentSubLoopIndex = $subItemIndex + $i;
                        $this->processProductDeduction($comp['component_id'], 1, $orderItemId, $currentSubLoopIndex);
                    }
                    $subItemIndex += $totalNeeded;

                } elseif ($comp['component_type'] == 'raw') {
                    $this->updateStock('raw_materials', $comp['component_id'], $totalNeeded);
                } elseif ($comp['component_type'] == 'manufactured') {
                    $this->updateStock('manufactured_products', $comp['component_id'], $totalNeeded);
                }
            }
        }

        if ($orderItemId) {
            $this->processExtras($orderItemId, $targetIndex);
        }
    }

    private function processExtras($orderItemId, $targetIndex)
    {
        $sql = "SELECT raw_material_id, quantity_adjustment 
                FROM order_item_modifiers 
                WHERE order_item_id = ? AND modifier_type = 'add'";

        $params = [$orderItemId];

        if ($targetIndex !== null) {
            $sql .= " AND sub_item_index = ?";
            $params[] = $targetIndex;
        } else {
            $sql .= " AND sub_item_index = 0";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $modifiers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($modifiers as $mod) {
            $qty = floatval($mod['quantity_adjustment']) > 0 ? $mod['quantity_adjustment'] : 0.050;
            $this->updateStock('raw_materials', $mod['raw_material_id'], $qty);
        }
    }

    private function updateStock($table, $id, $qty)
    {
        $field = ($table == 'raw_materials') ? 'stock_quantity' : 'stock';
        $sql = "UPDATE {$table} SET {$field} = {$field} - ? WHERE id = ? AND {$field} >= ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$qty, $id, $qty]);

        if ($stmt->rowCount() === 0) {
            throw new Exception("Stock insuficiente en {$table} (ID: {$id}) para cubrir la demanda.");
        }
    }

    public function updateOrderStatus($id, $status, $tracking_number = null)
    {
        $stmt = $this->db->prepare("UPDATE orders SET status = ?, tracking_number = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $tracking_number, $id]);
    }

    public function getOrderById($id)
    {
        $sql = "SELECT orders.*, users.name AS customer_name FROM orders JOIN users ON orders.user_id = users.id WHERE orders.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getOrderItems($order_id)
    {
        $stmt = $this->db->prepare("
                SELECT oi.*, p.name, p.price_usd, p.product_type
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE order_id = ?
            ");
        $stmt->execute([$order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getItemModifiers($orderItemId)
    {
        $sql = "SELECT m.*, rm.name as ingredient_name
                FROM order_item_modifiers m
                LEFT JOIN raw_materials rm ON m.raw_material_id = rm.id
                WHERE m.order_item_id = ?
                ORDER BY m.sub_item_index ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderItemId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrdersBySearchAndFilter($search = '', $filter = '')
    {
        $sql = "SELECT orders.*, users.name AS customer_name FROM orders JOIN users ON orders.user_id = users.id WHERE 1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (users.name LIKE ? OR orders.id LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($filter)) {
            $sql .= " AND orders.status = ?";
            $params[] = $filter;
        }

        $sql .= " ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
