<?php
// PurchaseReceiptManager.php

class PurchaseReceiptManager {
    private $db;
    private $productManager;

    public function __construct($db, $productManager) {
        $this->db = $db;
        $this->productManager = $productManager;
    }


    public function createPurchaseReceipt($purchaseOrderId, $receiptDate) {
            try {
                $this->db->beginTransaction();

                // 1. Registrar la recepción
                $stmt = $this->db->prepare("INSERT INTO purchase_receipts (purchase_order_id, receipt_date) VALUES (?, ?)");
                $stmt->execute([$purchaseOrderId, $receiptDate]);
                $receiptId = $this->db->lastInsertId();

                // 2. Obtener items y Tasa de Cambio Actual
                $items = $this->getPurchaseOrderItems($purchaseOrderId);

                // Necesitamos la tasa actual para recalcular el precio VES
                // Asumimos que $GLOBALS['config'] está disponible por el autoload
                $currentRate = $GLOBALS['config']->get('exchange_rate');
                if(!$currentRate) $currentRate = 1;

                foreach ($items as $item) {
                    $product = $this->productManager->getProductById($item['product_id']);

                    if ($product) {
                        // A. Actualizar Stock
                        $newStock = $product['stock'] + $item['quantity'];

                        // B. Calcular Nuevo Precio de Venta (USD)
                        // CORRECCIÓN: Usamos $product['profit_margin'], no $item
                        $margin = $product['profit_margin'];
                        $cost = $item['unit_price'];

                        // Fórmula: Costo + (Costo * %Margen)
                        $newPriceUsd = $cost * (1 + ($margin / 100));

                        // C. Calcular Nuevo Precio (VES) automáticamente
                        $newPriceVes = $newPriceUsd * $currentRate;

                        // D. Actualizar todo en el Producto (Stock, USD y VES)
                        // Usamos una consulta directa para ser eficientes y atómicos
                        $sql = "UPDATE products SET stock = ?, price_usd = ?, price_ves = ?, updated_at = NOW() WHERE id = ?";
                        $stmtUpdate = $this->db->prepare($sql);
                        $stmtUpdate->execute([$newStock, $newPriceUsd, $newPriceVes, $item['product_id']]);

                    } else {
                        throw new Exception("Producto no encontrado ID: " . $item['product_id']);
                    }
                }

                // 3. Cerrar la Orden de Compra
                $stmt = $this->db->prepare("UPDATE purchase_orders SET status = 'received' WHERE id = ?");
                $stmt->execute([$purchaseOrderId]);

                $this->db->commit();
                return $receiptId;

            } catch (PDOException $e) {
                $this->db->rollBack();
                error_log("Error DB en recepción: " . $e->getMessage());
                return false;
            } catch (Exception $e) {
                $this->db->rollBack();
                error_log("Error lógico en recepción: " . $e->getMessage());
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
