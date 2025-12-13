<?php

use Minimarcket\Modules\Sales\Services\OrderService;

/**
 * @deprecated This class is a legacy proxy. Use Minimarcket\Modules\Sales\Services\OrderService instead.
 */
class OrderManager
{
    private $service;

    public function __construct($db = null)
    {
        global $app;
        if (isset($app)) {
            $this->service = $app->getContainer()->get(OrderService::class);
        } else {
            throw new \Exception("Application not bootstrapped. Cannot instantiate OrderManager.");
        }
    }

    public function createOrder($user_id, $items, $shipping_address, $shipping_method = null)
    {
        return $this->service->createOrder($user_id, $items, $shipping_address, $shipping_method);
    }

    public function deductStockFromSale($orderId)
    {
        return $this->service->deductStockFromSale($orderId);
    }

    public function updateOrderStatus($id, $status, $tracking_number = null)
    {
        return $this->service->updateOrderStatus($id, $status, $tracking_number);
    }

    public function getOrderById($id)
    {
        return $this->service->getOrderById($id);
    }

    public function getOrderItems($order_id)
    {
        return $this->service->getOrderItems($order_id);
    }

    public function getItemModifiers($orderItemId)
    {
        return $this->service->getItemModifiers($orderItemId);
    }

    public function getOrdersBySearchAndFilter($search = '', $filter = '')
    {
        return $this->service->getOrdersBySearchAndFilter($search, $filter);
    }
}