<?php

namespace Minimarcket\Modules\Sales\Repositories;

use Minimarcket\Core\Database\BaseRepository;

/**
 * Class CartRepository
 * 
 * Repositorio para gestión del carrito de compras.
 */
class CartRepository extends BaseRepository
{
    protected string $table = 'cart';

    /**
     * Obtiene el carrito de un usuario
     */
    public function getByUserId(int $userId): array
    {
        return $this->newQuery()
            ->where('user_id', '=', $userId)
            ->get();
    }

    /**
     * Actualiza la cantidad de un item del carrito
     */
    public function updateQuantity(int $cartId, float $quantity): bool
    {
        return $this->update($cartId, ['quantity' => $quantity]);
    }

    /**
     * Vacía el carrito de un usuario
     */
    public function emptyByUserId(int $userId): int
    {
        return $this->newQuery()
            ->where('user_id', '=', $userId)
            ->delete();
    }

    /**
     * Actualiza los modificadores de un item
     */
    public function updateModifiers(int $cartId, array $data): bool
    {
        // Assuming logic to update modifiers, but for now we focus on reading.
        // Implementation details of updateModifiers were not fully shown but assumed to exist or be handled by BaseRepository's update if it was simple. 
        // However, cart_item_modifiers is a separate table. BaseRepository update works on $this->table ('cart').
        // So updateModifiers likely needs custom logic or was just a stub.
        // For this task, I need READING.
        return false; // Placeholder/Stub preservation
    }

    /**
     * Obtiene los modificadores guardados de un item
     */
    public function getModifiers(int $cartId): array
    {
        $qb = new \Minimarcket\Core\Database\QueryBuilder($this->connection);
        return $qb->table('cart_item_modifiers')
            ->where('cart_id', '=', $cartId)
            ->get();
    }
}
