<?php
use Minimarcket\Core\Container;
use Minimarcket\Modules\SupplyChain\Services\PurchaseReceiptService;

/**
 * @deprecated This class is a legacy proxy. Use Minimarcket\Modules\SupplyChain\Services\PurchaseReceiptService instead.
 */
class PurchaseReceiptManager
{
    private $service;

    public function __construct($db = null, $productManager = null)
    {
        // $productManager ignored in proxy as service handles its own dependencies
        $container = Container::getInstance();
        try {
            $this->service = $container->get(PurchaseReceiptService::class);
        } catch (Exception $e) {
            $this->service = new PurchaseReceiptService($db);
        }
    }

    public function createPurchaseReceipt($purchaseOrderId, $receiptDate)
    {
        return $this->service->createPurchaseReceipt($purchaseOrderId, $receiptDate);
    }

    public function getPurchaseReceiptById($id)
    {
        return $this->service->getPurchaseReceiptById($id);
    }

    public function getAllPurchaseReceipts()
    {
        return $this->service->getAllPurchaseReceipts();
    }
}