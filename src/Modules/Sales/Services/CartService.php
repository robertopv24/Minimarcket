<?php

namespace Minimarcket\Modules\Sales\Services;

use Minimarcket\Modules\Sales\Repositories\CartRepository;

/**
 * Class CartService
 * 
 * Servicio de lógica de negocio para el carrito de compras.
 */
class CartService
{
    protected CartRepository $repository;

    public function __construct(CartRepository $repository)
    {
        $this->repository = $repository;
    }

    public function addToCart(int $user_id, int $product_id, float $quantity, array $modifiers = [], string $consumptionType = 'dine_in'): string
    {
        return $this->repository->create([
            'user_id' => $user_id,
            'product_id' => $product_id,
            'quantity' => $quantity,
            // 'modifiers' => json_encode($modifiers), // Columna no existe en DB
            'consumption_type' => $consumptionType
        ]);
    }

    public function updateItemModifiers(int $cartId, array $data): bool
    {
        return $this->repository->updateModifiers($cartId, $data);
    }

    public function getCart(int $user_id): array
    {
        return $this->repository->getByUserId($user_id);
    }

    public function getItem(int $cartId): ?array
    {
        return $this->repository->find($cartId);
    }

    public function getModifiers(int $cartId): array
    {
        return $this->repository->getModifiers($cartId);
    }

    public function updateCartQuantity(int $cartId, float $quantity): bool
    {
        return $this->repository->updateQuantity($cartId, $quantity);
    }

    public function removeFromCart(int $cartId): bool
    {
        return $this->repository->delete($cartId);
    }

    public function emptyCart(int $user_id): int
    {
        return $this->repository->emptyByUserId($user_id);
    }

    public function calculateTotal(array $cart_items): float
    {
        $total = 0.0;
        foreach ($cart_items as $item) {
            $total += ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
        }
        return $total;
    }
}
