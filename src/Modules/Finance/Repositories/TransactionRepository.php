<?php

namespace Minimarcket\Modules\Finance\Repositories;

use Minimarcket\Core\Database\BaseRepository;

/**
 * Class TransactionRepository
 * 
 * Repositorio para gestión de transacciones financieras.
 */
class TransactionRepository extends BaseRepository
{
    protected string $table = 'transactions';

    /**
     * Obtiene transacciones por rango de fechas
     */
    public function getByDateRange(string $startDate, string $endDate): array
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM {$this->table} 
            WHERE DATE(created_at) BETWEEN ? AND ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene métodos de pago disponibles
     */
    public function getPaymentMethods(): array
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->query("SELECT * FROM payment_methods ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    /**
     * Registra múltiples pagos de una orden
     */
    public function registerOrderPayments(int $orderId, array $payments, int $userId, int $sessionId): bool
    {
        $pdo = $this->connection->getConnection();

        foreach ($payments as $payment) {
            $stmt = $pdo->prepare("
                INSERT INTO {$this->table} 
                (type, amount, currency, payment_method_id, description, user_id, session_id, reference_type, reference_id) 
                VALUES ('income', ?, ?, ?, ?, ?, ?, 'order', ?)
            ");
            $stmt->execute([
                $payment['amount'],
                $payment['currency'],
                $payment['method_id'],
                "Pago de orden #{$orderId}",
                $userId,
                $sessionId,
                $orderId
            ]);
        }

        return true;
    }

    /**
     * Obtiene una transacción por referencia (type/id)
     */
    public function getTransactionByReference(string $type, int $id): ?array
    {
        $pdo = $this->connection->getConnection();
        // JOIN with payment_methods to get method name if possible
        $stmt = $pdo->prepare("
            SELECT t.*, pm.name as method_name 
            FROM {$this->table} t
            LEFT JOIN payment_methods pm ON t.payment_method_id = pm.id
            WHERE t.reference_type = ? AND t.reference_id = ? 
            LIMIT 1
        ");
        $stmt->execute([$type, $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}
