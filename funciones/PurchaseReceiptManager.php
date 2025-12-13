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
        global $app;
        if (isset($app)) {
            $this->service = $app->getContainer()->get(PurchaseReceiptService::class);
        } else {
            // Manual fallback instantiation
            $connection = new \Minimarcket\Core\Database\ConnectionManager();

            $receiptRepo = new \Minimarcket\Modules\SupplyChain\Repositories\PurchaseReceiptRepository($connection);
            $orderRepo = new \Minimarcket\Modules\SupplyChain\Repositories\PurchaseOrderRepository($connection);

            // RawMaterialService (needs RawMaterialRepo)
            $rawRepo = new \Minimarcket\Modules\Inventory\Repositories\RawMaterialRepository($connection);
            $rawService = new \Minimarcket\Modules\Inventory\Services\RawMaterialService($rawRepo);

            // ProductService (needs ProductRepo and RawMaterialRepo)
            $prodRepo = new \Minimarcket\Modules\Inventory\Repositories\ProductRepository($connection);
            $prodService = new \Minimarcket\Modules\Inventory\Services\ProductService($prodRepo, $rawRepo);

            $configService = new \Minimarcket\Core\Config\ConfigService($connection->getConnection());

            $this->service = new PurchaseReceiptService($receiptRepo, $orderRepo, $prodService, $configService, $rawService);
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