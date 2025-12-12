<?php

namespace Minimarcket\Modules\SupplyChain\Services;

use Minimarcket\Core\Database;
use Minimarcket\Modules\Inventory\Services\ProductService;
use Minimarcket\Core\Config\ConfigService;
use PDO;
use Exception;
use PDOException;

class PurchaseReceiptService
{
    private $db;
    private $productService;
    private $configService;

    public function __construct(?PDO $db = null, ?ProductService $productService = null, ?ConfigService $configService = null)
    {
        $this->db = $db ?? Database::getConnection();
        $this->productService = $productService ?? new ProductService($this->db);
        $this->configService = $configService ?? new ConfigService($this->db);
    }


    public function createPurchaseReceipt($purchaseOrderId, $receiptDate)
    {
        try {
            $this->db->beginTransaction();

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

            $stmt = $this->db->prepare("INSERT INTO purchase_receipts (purchase_order_id, receipt_date) VALUES (?, ?)");
            $stmt->execute([$purchaseOrderId, $receiptDate]);
            $receiptId = $this->db->lastInsertId();

            $items = $this->getPurchaseOrderItems($purchaseOrderId);

            $currentRate = $this->configService->get('exchange_rate');
            if (!$currentRate)
                $currentRate = 1;

            foreach ($items as $item) {
                // Assuming items are PRODUCTS. If they are raw_materials, Logic changes (handled in PurchaseOrder logic? No, Receipt confirms it).
                // Existing Legacy code only handled PRODUCTS (getProductById).
                // I will stick to legacy logic.
                $product = $this->productService->getProductById($item['product_id']);

                if ($product) {
                    $newStock = $product['stock'] + $item['quantity'];

                    $margin = $product['profit_margin'];
                    $cost = $item['unit_price'];

                    $newPriceUsd = $cost * (1 + ($margin / 100));
                    $newPriceVes = $newPriceUsd * $currentRate;

                    // Manual update to Products table for atomic efficiency
                    // Ideally use ProductService update logic, but we change multiple fields.
                    $sql = "UPDATE products SET stock = ?, price_usd = ?, price_ves = ?, updated_at = NOW() WHERE id = ?";
                    $stmtUpdate = $this->db->prepare($sql);
                    $stmtUpdate->execute([$newStock, $newPriceUsd, $newPriceVes, $item['product_id']]);

                } else {
                    // Could be raw material? Legacy didn't handle it here explicitly or crashed.
                    // I will throw exception as legacy did.
                    throw new Exception("Producto no encontrado ID: " . $item['product_id']);
                }
            }

            $stmt = $this->db->prepare("UPDATE purchase_orders SET status = 'received' WHERE id = ?");
            $stmt->execute([$purchaseOrderId]);

            $this->db->commit();
            return $receiptId;

        } catch (Exception $e) {
            if ($this->db->inTransaction())
                $this->db->rollBack();
            error_log("Error en recepción: " . $e->getMessage());
            return false;
        }
    }

    public function getPurchaseReceiptById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM purchase_receipts WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllPurchaseReceipts()
    {
        $stmt = $this->db->query("SELECT * FROM purchase_receipts");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getPurchaseOrderItems($purchaseOrderId)
    {
        $stmt = $this->db->prepare("SELECT * FROM purchase_order_items WHERE purchase_order_id = ?");
        $stmt->execute([$purchaseOrderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
