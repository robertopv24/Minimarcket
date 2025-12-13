<?php

namespace Minimarcket\Modules\Sales\Repositories;

use Minimarcket\Core\Database\BaseRepository;

/**
 * Class OrderRepository
 * 
 * Repositorio para gestión de órdenes de venta.
 */
class OrderRepository extends BaseRepository
{
    protected string $table = 'orders';

    /**
     * Busca órdenes por búsqueda y filtro
     */
    public function searchAndFilter(string $search = '', string $filter = ''): array
    {
        $query = $this->newQuery();

        if (!empty($search)) {
            $query->where('id', 'LIKE', "%{$search}%");
        }

        if (!empty($filter)) {
            $query->where('status', '=', $filter);
        }

        return $query->orderBy('created_at', 'DESC')->get();
    }

    /**
     * Actualiza el estado de una orden
     */
    public function updateStatus(int $id, string $status, ?string $trackingNumber = null): bool
    {
        $data = ['status' => $status];

        if ($trackingNumber !== null) {
            $data['tracking_number'] = $trackingNumber;
        }

        return $this->update($id, $data);
    }

    /**
     * Obtiene los items de una orden
     */
    public function getOrderItems(int $orderId): array
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene los modificadores de un item
     */
    public function getItemModifiers(int $orderItemId): array
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM order_item_modifiers WHERE order_item_id = ?");
        $stmt->execute([$orderItemId]);
        return $stmt->fetchAll();
    }
    /**
     * Agrega un item a la orden y retorna su ID
     */
    public function addItem(array $data): int
    {
        $cols = implode(", ", array_keys($data));
        $vals = implode(", ", array_fill(0, count($data), "?"));

        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare("INSERT INTO order_items ($cols) VALUES ($vals)");
        $stmt->execute(array_values($data));
        return (int) $pdo->lastInsertId();
    }

    /**
     * Inicia una transacción
     */
    public function beginTransaction(): void
    {
        $this->connection->getConnection()->beginTransaction();
    }

    /**
     * Confirma una transacción
     */
    public function commit(): void
    {
        $this->connection->getConnection()->commit();
    }

    /**
     * Revierte una transacción
     */
    public function rollBack(): void
    {
        $this->connection->getConnection()->rollBack();
    }
}
