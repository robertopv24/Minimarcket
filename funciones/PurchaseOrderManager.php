<?php
// PurchaseOrderManager.php

class PurchaseOrderManager
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function createPurchaseOrder($supplierId, $orderDate, $expectedDeliveryDate, $items, $exchangeRate)
    {
        try {
            $this->db->beginTransaction();

            // Insertamos incluyendo la tasa de cambio histórica (exchange_rate)
            $stmt = $this->db->prepare("INSERT INTO purchase_orders (supplier_id, order_date, expected_delivery_date, total_amount, exchange_rate, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            // Nota: Inicializamos total_amount en 0, luego se calcula con los ítems
            $stmt->execute([$supplierId, $orderDate, $expectedDeliveryDate, 0, $exchangeRate]);

            $purchaseOrderId = $this->db->lastInsertId();
            $totalAmount = 0;

            foreach ($items as $item) {
                $itemType = $item['item_type'] ?? 'product'; // Tipo: product, raw_material, supply
                $itemId = $item['item_id'] ?? $item['product_id'] ?? 0; // ID del ítem
                $quantity = $item['quantity'];
                $unitPrice = $item['unit_price'];

                // Insertar ítem con tipo
                $stmt = $this->db->prepare("INSERT INTO purchase_order_items 
                    (purchase_order_id, item_type, item_id, product_id, quantity, unit_price) 
                    VALUES (?, ?, ?, ?, ?, ?)");

                // Mantener product_id para compatibilidad
                $productId = ($itemType === 'product') ? $itemId : null;
                $stmt->execute([$purchaseOrderId, $itemType, $itemId, $productId, $quantity, $unitPrice]);

                $totalAmount += $quantity * $unitPrice;

                // NO actualizamos stock aquí. El stock se actualiza SÓLO al recibir la mercancía (PurchaseReceiptManager)
                // $this->updateStockByType($itemType, $itemId, $quantity, $unitPrice);
            }

            // Actualizamos el total calculado
            $stmt = $this->db->prepare("UPDATE purchase_orders SET total_amount = ? WHERE id = ?");
            $stmt->execute([$totalAmount, $purchaseOrderId]);

            $this->db->commit();
            return $purchaseOrderId;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error al crear la orden de compra: " . $e->getMessage());
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
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error al actualizar la orden de compra: " . $e->getMessage());
            return false;
        }
    }

    public function deletePurchaseOrder($id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM purchase_orders WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error al eliminar la orden de compra: " . $e->getMessage());
            return false;
        }
    }

    public function getPurchaseOrderById($id)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM purchase_orders WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener la orden de compra por ID: " . $e->getMessage());
            return false;
        }
    }

    public function searchPurchaseOrders($query = '')
    {
        try {
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
        } catch (PDOException $e) {
            error_log("Error search purchase orders: " . $e->getMessage());
            return [];
        }
    }

    public function getAllPurchaseOrders()
    {
        return $this->searchPurchaseOrders();
    }

    public function addItemToPurchaseOrder($purchaseOrderId, $productId, $quantity, $unitPrice)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO purchase_order_items (purchase_order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([$purchaseOrderId, $productId, $quantity, $unitPrice]);

            if ($result) {
                $this->recalculateOrderTotal($purchaseOrderId); // Actualizar total
            }
            return $result;
        } catch (PDOException $e) {
            error_log("Error al agregar ítem a la orden de compra: " . $e->getMessage());
            return false;
        }
    }

    public function removeItemFromPurchaseOrder($itemId)
    {
        try {
            // Primero obtenemos el ID de la orden para recalcular
            $stmt = $this->db->prepare("SELECT purchase_order_id FROM purchase_order_items WHERE id = ?");
            $stmt->execute([$itemId]);
            $orderId = $stmt->fetchColumn();

            $stmt = $this->db->prepare("DELETE FROM purchase_order_items WHERE id = ?");
            $result = $stmt->execute([$itemId]);

            if ($result && $orderId) {
                $this->recalculateOrderTotal($orderId); // Actualizar total
            }
            return $result;
        } catch (PDOException $e) {
            error_log("Error al eliminar ítem de la orden de compra: " . $e->getMessage());
            return false;
        }
    }

    private function recalculateOrderTotal($purchaseOrderId)
    {
        try {
            $stmt = $this->db->prepare("SELECT SUM(quantity * unit_price) FROM purchase_order_items WHERE purchase_order_id = ?");
            $stmt->execute([$purchaseOrderId]);
            $newTotal = $stmt->fetchColumn() ?: 0;

            $stmt = $this->db->prepare("UPDATE purchase_orders SET total_amount = ? WHERE id = ?");
            $stmt->execute([$newTotal, $purchaseOrderId]);
        } catch (PDOException $e) {
            error_log("Error al recalcular total de orden #$purchaseOrderId: " . $e->getMessage());
        }
    }

    // PurchaseOrderManager.php

    public function getPurchaseOrderItems($purchaseOrderId)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM purchase_order_items WHERE purchase_order_id = ?");
            $stmt->execute([$purchaseOrderId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener los ítems de la orden de compra: " . $e->getMessage());
            return [];
        }
    }




}
?>