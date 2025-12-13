<?php

use Minimarcket\Modules\Sales\Services\CartService;

/**
 * @deprecated This class is a legacy proxy. Use Minimarcket\Modules\Sales\Services\CartService instead.
 */
class CartManager
{
    private $service;

    public function __construct($db = null)
    {
        global $app;
        if (isset($app)) {
            $this->service = $app->getContainer()->get(CartService::class);
        } else {
            throw new \Exception("Application not bootstrapped. Cannot instantiate CartManager.");
        }
    }

    public function addToCart($user_id, $product_id, $quantity, $modifiers = [], $consumptionType = 'dine_in')
    {
        return $this->service->addToCart($user_id, $product_id, $quantity, $modifiers, $consumptionType);
    }

    public function updateItemModifiers($cartId, $data)
    {
        return $this->service->updateItemModifiers($cartId, $data);
    }

    public function getCart($user_id)
    {
        return $this->service->getCart($user_id);
    }

    public function updateCartQuantity($cartId, $quantity)
    {
        return $this->service->updateCartQuantity($cartId, $quantity);
    }

    public function removeFromCart($cartId)
    {
        return $this->service->removeFromCart($cartId);
    }

    public function emptyCart($user_id)
    {
        return $this->service->emptyCart($user_id);
    }

    public function calculateTotal($cart_items)
    {
        return $this->service->calculateTotal($cart_items);
    }
}