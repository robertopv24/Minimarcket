<?php
// PurchaseReceiptManager.php

class PurchaseReceiptManager
{
    private $db;
    private $productManager;

    public function __construct($db, $productManager)
    {
        $this->db = $db;
        $this->productManager = $productManager;
    }


    public function createPurchaseReceipt($purchaseOrderId, $receiptDate)
    {
        // (Existing logic remains the same, but I'll add a check to ensured we used the transaction)
        return $this->handleReceipt($purchaseOrderId, $receiptDate, false);
    }

    /**
     * REVERSIÓN DE RECEPCIÓN (NUEVO)
     * Resta el stock sumado anteriormente.
     */
    public function revertPurchaseReceipt($purchaseOrderId)
    {
        return $this->handleReceipt($purchaseOrderId, null, true);
    }

    private function handleReceipt($purchaseOrderId, $receiptDate, $isReversion = false)
    {
        try {
            $this->db->beginTransaction();

            $stmtCheck = $this->db->prepare("SELECT status FROM purchase_orders WHERE id = ? FOR UPDATE");
            $stmtCheck->execute([$purchaseOrderId]);
            $currentStatus = $stmtCheck->fetchColumn();

            if (!$currentStatus) {
                throw new Exception("Orden de compra #$purchaseOrderId no encontrada.");
            }

            if (!$isReversion) {
                if ($currentStatus === 'received') throw new Exception("Esta orden ya fue recibida.");
                if ($currentStatus === 'canceled') throw new Exception("No se puede recibir una orden cancelada.");
            } else {
                if ($currentStatus !== 'received') throw new Exception("Solo se puede revertir una orden ya recibida.");
            }

            if (!$isReversion) {
                // Registrar la recepción
                $stmt = $this->db->prepare("INSERT INTO purchase_receipts (purchase_order_id, receipt_date) VALUES (?, ?)");
                $stmt->execute([$purchaseOrderId, $receiptDate]);
            } else {
                // Eliminar registro de recepción
                $stmt = $this->db->prepare("DELETE FROM purchase_receipts WHERE purchase_order_id = ?");
                $stmt->execute([$purchaseOrderId]);
            }

            $items = $this->getPurchaseOrderItems($purchaseOrderId);

            foreach ($items as $item) {
                $itemType = $item['item_type'] ?? 'product';
                $itemId = (!empty($item['item_id'])) ? $item['item_id'] : $item['product_id'];
                $qty = floatval($item['quantity']);

                if ($itemType === 'product') {
                    $product = $this->productManager->getProductById($itemId);
                    if ($product) {
                        $newStock = $isReversion ? ($product['stock'] - $qty) : ($product['stock'] + $qty);
                        
                        // En reversión no tocamos precios de venta, solo stock
                        if (!$isReversion) {
                            $margin = $product['profit_margin'];
                            $cost = $item['unit_price'];

                            // NUEVO: Usar la tasa histórica de la ORDEN de compra si existe, 
                            // de lo contrario usar la global como fallback.
                            $stmtRate = $this->db->prepare("SELECT exchange_rate FROM purchase_orders WHERE id = ?");
                            $stmtRate->execute([$purchaseOrderId]);
                            $orderRate = $stmtRate->fetchColumn() ?: ($GLOBALS['config']->get('exchange_rate') ?: 1);

                            $newPriceUsd = $cost * (1 + ($margin / 100));
                            $newPriceVes = $newPriceUsd * $orderRate;
                            
                            $sql = "UPDATE products SET stock = ?, price_usd = ?, price_ves = ?, updated_at = NOW() WHERE id = ?";
                            $stmtUpdate = $this->db->prepare($sql);
                            $stmtUpdate->execute([$newStock, $newPriceUsd, $newPriceVes, $itemId]);
                        } else {
                            $sql = "UPDATE products SET stock = ?, updated_at = NOW() WHERE id = ?";
                            $stmtUpdate = $this->db->prepare($sql);
                            $stmtUpdate->execute([$newStock, $itemId]);
                        }
                    }
                } elseif ($itemType === 'raw_material') {
                    $stmt = $this->db->prepare("SELECT stock_quantity, cost_per_unit FROM raw_materials WHERE id = ?");
                    $stmt->execute([$itemId]);
                    $current = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($current) {
                        $oldStock = floatval($current['stock_quantity']);
                        $newStock = $isReversion ? ($oldStock - $qty) : ($oldStock + $qty);
                        
                        // Nota: El costo promedio ponderado no se revierte fácilmente sin historial de capas,
                        // por ahora ajustamos solo stock físico por seguridad.
                        $stmtUpd = $this->db->prepare("UPDATE raw_materials SET stock_quantity = ? WHERE id = ?");
                        $stmtUpd->execute([$newStock, $itemId]);
                    }
                }
            }

            // Actualizar Estado de Orden
            $newStatus = $isReversion ? 'pending' : 'received';
            $stmt = $this->db->prepare("UPDATE purchase_orders SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $purchaseOrderId]);

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en " . ($isReversion ? "reversión" : "recepción") . ": " . $e->getMessage());
            throw $e;
        }
    }

    public function getPurchaseReceiptById($id)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM purchase_receipts WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener la recepción por ID: " . $e->getMessage());
            return false;
        }
    }

    public function getAllPurchaseReceipts()
    {
        try {
            $stmt = $this->db->query("SELECT * FROM purchase_receipts");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener todas las recepciones: " . $e->getMessage());
            return false;
        }
    }

    private function getPurchaseOrderItems($purchaseOrderId)
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