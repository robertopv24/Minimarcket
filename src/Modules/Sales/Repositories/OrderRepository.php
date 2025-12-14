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

    // --- REPORTING METHODS (SaaS Enforced via BaseRepository logic or manual tenant filter if using raw SQL) ---

    // NOTE: BaseRepository::newQuery() enforces tenant_id. Using QueryBuilder is safest.
    // However, QueryBuilder might lack SUM/DATE functions.
    // If we use raw SQL, we MUST inject tenant_id manually.

    private function getTenantId(): int
    {
        return \Minimarcket\Core\Tenant\TenantContext::getTenantId();
    }

    public function getTotalSalesByDateRange(string $startDate, string $endDate): float
    {
        $pdo = $this->connection->getConnection();
        $sql = "SELECT SUM(total_price) as total FROM orders WHERE tenant_id = ? AND status = 'paid' AND created_at BETWEEN ? AND ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$this->getTenantId(), $startDate, $endDate]);
        return (float) $stmt->fetchColumn();
    }

    public function countOrdersByStatus(string $status): int
    {
        $query = $this->newQuery();
        // newQuery enforces tenant_id automatically
        // But QueryBuilder doesn't have count method yet? 
        // Let's use raw SQL for now to be safe and fast.

        $pdo = $this->connection->getConnection();
        $sql = "SELECT COUNT(*) FROM orders WHERE tenant_id = ? AND status = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$this->getTenantId(), $status]);
        return (int) $stmt->fetchColumn();
    }

    public function getRecentOrders(int $limit = 5): array
    {
        // Use QueryBuilder as it supports orderBy and get (and implicit tenant_id)
        $query = $this->newQuery();
        return $query->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }
}
