<?php

namespace Minimarcket\Modules\Sales\Repositories;

use Minimarcket\Core\Database\BaseRepository;

/**
 * Class CreditRepository
 * 
 * Repositorio para gestión de créditos y cuentas por cobrar.
 */
class CreditRepository extends BaseRepository
{
    protected string $table = 'credit_clients';

    /**
     * Busca clientes de crédito
     */
    public function searchClients(string $query): array
    {
        return $this->newQuery()
            ->where('name', 'LIKE', "%{$query}%")
            ->get();
    }

    /**
     * Obtiene las deudas pendientes de un cliente
     */
    public function getPendingDebtsByClient(int $clientId): array
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM accounts_receivable 
            WHERE client_id = ? AND status = 'pending' 
            ORDER BY due_date ASC
        ");
        $stmt->execute([$clientId]);
        return $stmt->fetchAll();
    }

    /**
     * Registra una deuda en cuentas por cobrar
     */
    public function registerDebt(array $data): string
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO accounts_receivable 
            (order_id, amount, amount_paid, client_id, user_id, due_date, notes, status) 
            VALUES (?, ?, 0, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $data['order_id'],
            $data['amount'],
            $data['client_id'] ?? null,
            $data['user_id'] ?? null,
            $data['due_date'] ?? null,
            $data['notes'] ?? ''
        ]);
        return $pdo->lastInsertId();
    }

    /**
     * Actualiza el pago de una deuda
     */
    public function updateDebtPayment(int $arId, float $amountPaid): bool
    {
        $pdo = $this->connection->getConnection();

        // Obtener deuda actual
        $stmt = $pdo->prepare("SELECT amount, amount_paid FROM accounts_receivable WHERE id = ?");
        $stmt->execute([$arId]);
        $debt = $stmt->fetch();

        if (!$debt) {
            return false;
        }

        $newAmountPaid = $debt['amount_paid'] + $amountPaid;
        $status = ($newAmountPaid >= $debt['amount']) ? 'paid' : 'pending';

        $updateStmt = $pdo->prepare("
            UPDATE accounts_receivable 
            SET amount_paid = ?, status = ?, paid_at = NOW() 
            WHERE id = ?
        ");
        return $updateStmt->execute([$newAmountPaid, $status, $arId]);
    }
}
