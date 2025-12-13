<?php

namespace Minimarcket\Modules\Finance\Repositories;

use Minimarcket\Core\Database\BaseRepository;

/**
 * Class VaultRepository
 * 
 * Repositorio para gestión de la bóveda (vault) de efectivo.
 */
class VaultRepository extends BaseRepository
{
    protected string $table = 'vault_movements';

    /**
     * Obtiene el balance actual de la bóveda
     */
    public function getBalance(): array
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->query("
            SELECT 
                SUM(CASE WHEN type = 'in' AND currency = 'USD' THEN amount ELSE 0 END) -
                SUM(CASE WHEN type = 'out' AND currency = 'USD' THEN amount ELSE 0 END) as balance_usd,
                SUM(CASE WHEN type = 'in' AND currency = 'VES' THEN amount ELSE 0 END) -
                SUM(CASE WHEN type = 'out' AND currency = 'VES' THEN amount ELSE 0 END) as balance_ves
            FROM {$this->table}
        ");
        return $stmt->fetch() ?: ['balance_usd' => 0, 'balance_ves' => 0];
    }

    /**
     * Registra un movimiento en la bóveda
     */
    public function registerMovement(string $type, string $origin, float $amount, string $currency, string $description, int $userId, ?int $refId = null): string
    {
        return $this->create([
            'type' => $type,
            'origin' => $origin,
            'amount' => $amount,
            'currency' => $currency,
            'description' => $description,
            'user_id' => $userId,
            'reference_id' => $refId
        ]);
    }

    /**
     * Transfiere fondos desde una sesión de caja
     */
    public function transferFromSession(int $sessionId, float $amountUsd, float $amountVes, int $userId): bool
    {
        if ($amountUsd > 0) {
            $this->registerMovement('in', 'cash_register', $amountUsd, 'USD', "Transferencia desde sesión #{$sessionId}", $userId, $sessionId);
        }

        if ($amountVes > 0) {
            $this->registerMovement('in', 'cash_register', $amountVes, 'VES', "Transferencia desde sesión #{$sessionId}", $userId, $sessionId);
        }

        return true;
    }
}
