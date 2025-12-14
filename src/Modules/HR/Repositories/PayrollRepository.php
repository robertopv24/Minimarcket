<?php

namespace Minimarcket\Modules\HR\Repositories;

use Minimarcket\Core\Database\BaseRepository;
use PDO;

class PayrollRepository extends BaseRepository
{
    protected string $table = 'payroll_payments';

    public function getLastPayment(int $userId): ?array
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY payment_date DESC LIMIT 1");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function logPayment(array $data): bool
    {
        return (bool) $this->create($data);
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

    public function getHistory(int $limit): array
    {
        $pdo = $this->connection->getConnection();
        // Join with users to get employee name if needed, or just return raw
        // The service seems to expect 'employee_name', so let's join.
        $stmt = $pdo->prepare("
            SELECT pp.*, u.name as employee_name 
            FROM {$this->table} pp
            JOIN users u ON pp.user_id = u.id
            ORDER BY pp.payment_date DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
