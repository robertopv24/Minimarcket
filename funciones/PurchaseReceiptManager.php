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
        try {
            $this->db->beginTransaction();

            // 0. Validar Estado Actual (Evitar Doble Recepción)
            // Usamos FOR UPDATE para bloquear la fila y evitar condiciones de carrera reales
            $stmtCheck = $this->db->prepare("SELECT status FROM purchase_orders WHERE id = ? FOR UPDATE");
            $stmtCheck->execute([$purchaseOrderId]);
            $currentStatus = $stmtCheck->fetchColumn();

            if (!$currentStatus) {
                throw new Exception("Orden de compra #$purchaseOrderId no encontrada.");
            }

            if ($currentStatus === 'received') {
                throw new Exception("Esta orden ya fue recibida anteriormente.");
            }

            if ($currentStatus === 'canceled') {
                throw new Exception("No se puede recibir una orden cancelada.");
            }

            // 1. Registrar la recepción
            $stmt = $this->db->prepare("INSERT INTO purchase_receipts (purchase_order_id, receipt_date) VALUES (?, ?)");
            $stmt->execute([$purchaseOrderId, $receiptDate]);
            $receiptId = $this->db->lastInsertId();

            // 2. Obtener items y Tasa de Cambio Actual
            $items = $this->getPurchaseOrderItems($purchaseOrderId);

            // Necesitamos la tasa actual para recalcular el precio VES
            // Asumimos que $GLOBALS['config'] está disponible por el autoload
            $currentRate = $GLOBALS['config']->get('exchange_rate');
            if (!$currentRate)
                $currentRate = 1;

            foreach ($items as $item) {
                // Determinar tipo e ID
                $itemType = $item['item_type'] ?? 'product';
                // Compatibilidad: si item_id es 0 o null, usar product_id
                $itemId = (!empty($item['item_id'])) ? $item['item_id'] : $item['product_id'];

                if ($itemType === 'product') {
                    // === LÓGICA DE PRODUCTOS (REVENTA) ===
                    $product = $this->productManager->getProductById($itemId);
                    if ($product) {
                        // A. Actualizar Stock
                        $newStock = $product['stock'] + $item['quantity'];

                        // B. Calcular Nuevo Precio de Venta (USD)
                        $margin = $product['profit_margin'];
                        $cost = $item['unit_price'];

                        // Fórmula: Costo + (Costo * %Margen)
                        $newPriceUsd = $cost * (1 + ($margin / 100));

                        // C. Calcular Nuevo Precio (VES) automáticamente
                        $newPriceVes = $newPriceUsd * $currentRate;

                        // D. Actualizar Product
                        $sql = "UPDATE products SET stock = ?, price_usd = ?, price_ves = ?, updated_at = NOW() WHERE id = ?";
                        $stmtUpdate = $this->db->prepare($sql);
                        $stmtUpdate->execute([$newStock, $newPriceUsd, $newPriceVes, $itemId]);
                    } else {
                        // Si falla buscando por ID, intentar buscar por product_id si difieren (legacy fallback)
                        error_log("Producto ID $itemId no encontrado en recepción.");
                    }

                } elseif ($itemType === 'raw_material') {
                    // === LÓGICA DE MATERIAS PRIMAS (INGREDIENTES/INSUMOS) ===
                    $stmt = $this->db->prepare("SELECT stock_quantity, cost_per_unit FROM raw_materials WHERE id = ?");
                    $stmt->execute([$itemId]);
                    $current = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($current) {
                        $oldStock = floatval($current['stock_quantity']);
                        $oldCost = floatval($current['cost_per_unit']);
                        $quantity = floatval($item['quantity']);
                        $unitPrice = floatval($item['unit_price']);

                        $newStock = $oldStock + $quantity;

                        // Costo Promedio Ponderado
                        if ($newStock > 0) {
                            $newCost = (($oldStock * $oldCost) + ($quantity * $unitPrice)) / $newStock;
                        } else {
                            $newCost = $unitPrice;
                        }

                        $stmtUpd = $this->db->prepare("UPDATE raw_materials SET stock_quantity = ?, cost_per_unit = ? WHERE id = ?");
                        $stmtUpd->execute([$newStock, $newCost, $itemId]);
                    } else {
                        throw new Exception("Materia Prima ID $itemId no encontrada.");
                    }
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
            throw new Exception("Error DB: " . $e->getMessage());
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error lógico en recepción: " . $e->getMessage());
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