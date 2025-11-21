<?php
// PurchaseReceiptManager.php

class PurchaseReceiptManager {
    private $db;
    private $productManager;

    public function __construct($db, $productManager) {
        $this->db = $db;
        $this->productManager = $productManager;
    }


    // PurchaseReceiptManager.php

    public function updateProductPrice($productId, $unitPrice) {
        try {
            $stmt = $this->db->prepare("UPDATE products SET price_usd = ? WHERE id = ?");
            return $stmt->execute([$unitPrice, $productId]);
        } catch (PDOException $e) {
            error_log("Error al actualizar el precio del producto: " . $e->getMessage());
            return false;
        }
    }






    public function createPurchaseReceipt($purchaseOrderId, $receiptDate) {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("INSERT INTO purchase_receipts (purchase_order_id, receipt_date) VALUES (?, ?)");
            $stmt->execute([$purchaseOrderId, $receiptDate]);

            $receiptId = $this->db->lastInsertId();

            // Actualizar el stock y el precio de los productos
            $items = $this->getPurchaseOrderItems($purchaseOrderId);
            foreach ($items as $item) {
                $product = $this->productManager->getProductById($item['product_id']);
                if ($product) {
                    $newStock = $product['stock'] + $item['quantity'];
                    $this->productManager->updateProductStock($item['product_id'], $newStock);
                    $this->updateProductPrice($item['product_id'], $item['unit_price']); // Actualizar el precio
                } else {
                    throw new Exception("Producto no encontrado ID: " . $item['product_id']);
                }
            }

            // Actualizar el estado de la orden de compra a 'received'
            $stmt = $this->db->prepare("UPDATE purchase_orders SET status = 'received' WHERE id = ?");
            $stmt->execute([$purchaseOrderId]);

            $this->db->commit();
            return $receiptId;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error al crear la recepción de mercancía: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error al actualizar el stock: " . $e->getMessage());
            return false;
        }
    }

    public function getPurchaseReceiptById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM purchase_receipts WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener la recepción por ID: " . $e->getMessage());
            return false;
        }
    }

    public function getAllPurchaseReceipts() {
        try {
            $stmt = $this->db->query("SELECT * FROM purchase_receipts");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener todas las recepciones: " . $e->getMessage());
            return false;
        }
    }

    private function getPurchaseOrderItems($purchaseOrderId) {
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
