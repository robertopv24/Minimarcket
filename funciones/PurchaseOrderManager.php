<?php

use Minimarcket\Core\Container;
use Minimarcket\Modules\SupplyChain\Services\PurchaseOrderService;

/**
 * @deprecated This class is a legacy proxy. Use Minimarcket\Modules\SupplyChain\Services\PurchaseOrderService instead.
 */
class PurchaseOrderManager
{
    private $service;

    public function __construct($db = null)
    {
        $container = Container::getInstance();
        try {
            $this->service = $container->get(PurchaseOrderService::class);
        } catch (Exception $e) {
            $this->service = new PurchaseOrderService($db);
        }
    }

    public function createPurchaseOrder($supplierId, $orderDate, $expectedDeliveryDate, $items, $exchangeRate)
    {
        return $this->service->createPurchaseOrder($supplierId, $orderDate, $expectedDeliveryDate, $items, $exchangeRate);
    }

    public function updatePurchaseOrder($id, $supplierId, $orderDate, $expectedDeliveryDate, $items)
    {
        return $this->service->updatePurchaseOrder($id, $supplierId, $orderDate, $expectedDeliveryDate, $items);
    }

    public function deletePurchaseOrder($id)
    {
        return $this->service->deletePurchaseOrder($id);
    }

    public function getPurchaseOrderById($id)
    {
        return $this->service->getPurchaseOrderById($id);
    }

    public function searchPurchaseOrders($query = '')
    {
        return $this->service->searchPurchaseOrders($query);
    }

    public function getAllPurchaseOrders()
    {
        return $this->service->getAllPurchaseOrders();
    }

    public function addItemToPurchaseOrder($purchaseOrderId, $productId, $quantity, $unitPrice)
    {
        return $this->service->addItemToPurchaseOrder($purchaseOrderId, $productId, $quantity, $unitPrice);
    }

    public function removeItemFromPurchaseOrder($itemId)
    {
        return $this->service->removeItemFromPurchaseOrder($itemId);
    }

    public function getPurchaseOrderItems($purchaseOrderId)
    {
        return $this->service->getPurchaseOrderItems($purchaseOrderId);
    }
}