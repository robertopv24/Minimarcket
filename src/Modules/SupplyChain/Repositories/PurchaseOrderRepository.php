<?php

namespace Minimarcket\Modules\SupplyChain\Repositories;

use Minimarcket\Core\Database\BaseRepository;
use PDO;

class PurchaseOrderRepository extends BaseRepository
{
    protected string $table = 'purchase_orders';

    public function createOrder(array $data): int
    {
        return (int) $this->create($data);
    }

    public function addOrderItem(array $data): bool
    {
        $cols = implode(", ", array_keys($data));
        $vals = implode(", ", array_fill(0, count($data), "?"));

        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare("INSERT INTO purchase_order_items ($cols) VALUES ($vals)");
        return $stmt->execute(array_values($data));
    }

    public function updateOrderTotal(int $orderId, float $total): bool
    {
        return $this->update($orderId, ['total_amount' => $total]);
    }

    public function clearOrderItems(int $orderId): bool
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare("DELETE FROM purchase_order_items WHERE purchase_order_id = ?");
        return $stmt->execute([$orderId]);
    }

    public function getOrderItems(int $orderId): array
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM purchase_order_items WHERE purchase_order_id = ?");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchOrders(string $query = ''): array
    {
        $sql = "SELECT po.*, s.name as supplier_name 
                FROM purchase_orders po
                LEFT JOIN suppliers s ON po.supplier_id = s.id";

        $params = [];
        if (!empty($query)) {
            $sql .= " WHERE s.name LIKE ? OR po.id LIKE ?";
            $params = ["%$query%", "%$query%"];
        }

        $sql .= " ORDER BY po.order_date DESC";

        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrderStatus(int $id): ?string
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare("SELECT status FROM purchase_orders WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchColumn() ?: null;
    }

    // Transaction helpers needed for Service
    public function beginTransaction(): void
    {
        $this->connection->getConnection()->beginTransaction();
    }
    public function commit(): void
    {
        $this->connection->getConnection()->commit();
    }
    public function rollBack(): void
    {
        $this->connection->getConnection()->rollBack();
    }
    public function getOrderIdByItemId($itemId): ?int
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare("SELECT purchase_order_id FROM purchase_order_items WHERE id = ?");
        $stmt->execute([$itemId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ? (int) $res['purchase_order_id'] : null;
    }

    public function deleteOrderItem($itemId): bool
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare("DELETE FROM purchase_order_items WHERE id = ?");
        return $stmt->execute([$itemId]);
    }

    public function inTransaction(): bool
    {
        return $this->connection->getConnection()->inTransaction();
    }
}
