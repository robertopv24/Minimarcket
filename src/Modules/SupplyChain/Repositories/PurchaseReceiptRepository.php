<?php

namespace Minimarcket\Modules\SupplyChain\Repositories;

use Minimarcket\Core\Database\BaseRepository;
use PDO;

class PurchaseReceiptRepository extends BaseRepository
{
    protected string $table = 'purchase_receipts';

    public function getReceiptsWithDetails(): array
    {
        // Podríamos unirlos con POs y Suppliers si se requiriera listado detallado
        // Por ahora devuelve tabla base como en legacy
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->query("SELECT * FROM purchase_receipts ORDER BY receipt_date DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Transaction helpers
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
    public function inTransaction(): bool
    {
        return $this->connection->getConnection()->inTransaction();
    }

    // Explicit helpers for locking/checking logic
    public function getOrderForUpdate(int $orderId): ?string
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare("SELECT status FROM purchase_orders WHERE id = ? FOR UPDATE");
        $stmt->execute([$orderId]);
        return $stmt->fetchColumn() ?: null;
    }

    public function updateOrderStatus(int $orderId, string $status): bool
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare("UPDATE purchase_orders SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $orderId]);
    }
}
