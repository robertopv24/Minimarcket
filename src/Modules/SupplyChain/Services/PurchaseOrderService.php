<?php

namespace Minimarcket\Modules\SupplyChain\Services;

use Minimarcket\Core\Database;
use Minimarcket\Modules\Inventory\Services\ProductService;
use PDO;
use Exception;

class PurchaseOrderService
{
    private $db;
    private $rawMaterialService;
    private $productService;

    public function __construct(?PDO $db = null, ?RawMaterialService $rawMaterialService = null, ?ProductService $productService = null)
    {
        $this->db = $db ?? Database::getConnection();
        $this->rawMaterialService = $rawMaterialService ?? new RawMaterialService($this->db);
        $this->productService = $productService ?? new ProductService($this->db);
    }

    public function createPurchaseOrder($supplierId, $orderDate, $expectedDeliveryDate, $items, $exchangeRate)
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("INSERT INTO purchase_orders (supplier_id, order_date, expected_delivery_date, total_amount, exchange_rate, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$supplierId, $orderDate, $expectedDeliveryDate, 0, $exchangeRate]);

            $purchaseOrderId = $this->db->lastInsertId();
            $totalAmount = 0;

            foreach ($items as $item) {
                $itemType = $item['item_type'] ?? 'product'; // product, raw_material, supply
                $itemId = $item['item_id'] ?? $item['product_id'] ?? 0;
                $quantity = $item['quantity'];
                $unitPrice = $item['unit_price'];

                $stmt = $this->db->prepare("INSERT INTO purchase_order_items (purchase_order_id, item_type, item_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?, ?, ?)");

                $productId = ($itemType === 'product') ? $itemId : null;
                $stmt->execute([$purchaseOrderId, $itemType, $itemId, $productId, $quantity, $unitPrice]);

                $totalAmount += $quantity * $unitPrice;

                // Update Stock
                if ($itemType === 'product') {
                    // Update Product Stock
                    // Note: ProductService uses updateProductStock($id, $stock), which SETS absolute stock.
                    // We need to ADD stock. ProductService doesn't expose addStock yet.
                    // So we do it manually or extend. For parity with legacy manager, we do manual update here or fetch-add-set.
                    // Let's do SQL update for efficiency and parity with legacy manager.
                    $stmtUpd = $this->db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                    $stmtUpd->execute([$quantity, $itemId]);

                } elseif ($itemType === 'raw_material') {
                    // Delegate to RawMaterialService which handles weighted cost
                    $this->rawMaterialService->addStock($itemId, $quantity, $unitPrice);
                }
            }

            $stmt = $this->db->prepare("UPDATE purchase_orders SET total_amount = ? WHERE id = ?");
            $stmt->execute([$totalAmount, $purchaseOrderId]);

            $this->db->commit();
            return $purchaseOrderId;
        } catch (Exception $e) {
            if ($this->db->inTransaction())
                $this->db->rollBack();
            error_log("Error createPurchaseOrder: " . $e->getMessage());
            return false;
        }
    }

    public function updatePurchaseOrder($id, $supplierId, $orderDate, $expectedDeliveryDate, $items)
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("UPDATE purchase_orders SET supplier_id = ?, order_date = ?, expected_delivery_date = ? WHERE id = ?");
            $stmt->execute([$supplierId, $orderDate, $expectedDeliveryDate, $id]);

            $stmt = $this->db->prepare("DELETE FROM purchase_order_items WHERE purchase_order_id = ?");
            $stmt->execute([$id]);

            $totalAmount = 0;
            foreach ($items as $item) {
                $stmt = $this->db->prepare("INSERT INTO purchase_order_items (purchase_order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$id, $item['product_id'], $item['quantity'], $item['unit_price']]);
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }

            $stmt = $this->db->prepare("UPDATE purchase_orders SET total_amount = ? WHERE id = ?");
            $stmt->execute([$totalAmount, $id]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction())
                $this->db->rollBack();
            return false;
        }
    }

    public function deletePurchaseOrder($id)
    {
        $stmt = $this->db->prepare("DELETE FROM purchase_orders WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getPurchaseOrderById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM purchase_orders WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function searchPurchaseOrders($query = '')
    {
        $sql = "SELECT po.*, s.name as supplier_name 
                FROM purchase_orders po
                LEFT JOIN suppliers s ON po.supplier_id = s.id";

        $params = [];
        if (!empty($query)) {
            $sql .= " WHERE s.name LIKE ? OR po.id LIKE ?";
            $params = ["%$query%", "%$query%"];
        }

        $sql .= " ORDER BY po.order_date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllPurchaseOrders()
    {
        return $this->searchPurchaseOrders();
    }

    public function addItemToPurchaseOrder($purchaseOrderId, $productId, $quantity, $unitPrice)
    {
        $stmt = $this->db->prepare("INSERT INTO purchase_order_items (purchase_order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$purchaseOrderId, $productId, $quantity, $unitPrice]);

        if ($result) {
            $this->recalculateOrderTotal($purchaseOrderId);
        }
        return $result;
    }

    public function removeItemFromPurchaseOrder($itemId)
    {
        $stmt = $this->db->prepare("SELECT purchase_order_id FROM purchase_order_items WHERE id = ?");
        $stmt->execute([$itemId]);
        $orderId = $stmt->fetchColumn();

        $stmt = $this->db->prepare("DELETE FROM purchase_order_items WHERE id = ?");
        $result = $stmt->execute([$itemId]);

        if ($result && $orderId) {
            $this->recalculateOrderTotal($orderId);
        }
        return $result;
    }

    public function getPurchaseOrderItems($purchaseOrderId)
    {
        $stmt = $this->db->prepare("SELECT * FROM purchase_order_items WHERE purchase_order_id = ?");
        $stmt->execute([$purchaseOrderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function recalculateOrderTotal($purchaseOrderId)
    {
        $stmt = $this->db->prepare("SELECT SUM(quantity * unit_price) FROM purchase_order_items WHERE purchase_order_id = ?");
        $stmt->execute([$purchaseOrderId]);
        $newTotal = $stmt->fetchColumn() ?: 0;

        $stmt = $this->db->prepare("UPDATE purchase_orders SET total_amount = ? WHERE id = ?");
        $stmt->execute([$newTotal, $purchaseOrderId]);
    }
}
