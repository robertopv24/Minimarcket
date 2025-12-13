<?php

namespace Minimarcket\Modules\Finance\Repositories;

use Minimarcket\Core\Database\BaseRepository;

/**
 * Class CashRegisterRepository
 * 
 * Repositorio para gestión de sesiones de caja registradora.
 */
class CashRegisterRepository extends BaseRepository
{
    protected string $table = 'cash_register_sessions';

    /**
     * Verifica si un usuario tiene una sesión abierta
     */
    public function hasOpenSession(int $userId): bool
    {
        $result = $this->newQuery()
            ->where('user_id', '=', $userId)
            ->where('status', '=', 'open')
            ->first();

        return $result !== null;
    }

    /**
     * Obtiene el estado de la sesión de un usuario
     */
    public function getStatus(int $userId): ?array
    {
        return $this->newQuery()
            ->where('user_id', '=', $userId)
            ->where('status', '=', 'open')
            ->first();
    }

    /**
     * Abre una nueva sesión de caja
     */
    public function openSession(int $userId, float $initialUsd, float $initialVes): string
    {
        return $this->create([
            'user_id' => $userId,
            'initial_usd' => $initialUsd,
            'initial_ves' => $initialVes,
            'status' => 'open',
            'opened_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Cierra una sesión de caja
     */
    public function closeSession(int $userId, float $countedUsd, float $countedVes): bool
    {
        $session = $this->getStatus($userId);

        if (!$session) {
            return false;
        }

        return $this->update($session['id'], [
            'counted_usd' => $countedUsd,
            'counted_ves' => $countedVes,
            'status' => 'closed',
            'closed_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Obtiene el reporte de una sesión
     */
    public function getSessionReport(int $userId): ?array
    {
        $session = $this->getStatus($userId);

        if (!$session) {
            return null;
        }

        // TODO: Agregar cálculos de ventas, transacciones, etc.
        return $session;
    }

    /**
     * Busca sesiones por query
     */
    public function searchSessions(string $query = ''): array
    {
        if (empty($query)) {
            return $this->newQuery()
                ->orderBy('opened_at', 'DESC')
                ->limit(50)
                ->get();
        }

        return $this->newQuery()
            ->where('id', 'LIKE', "%{$query}%")
            ->orderBy('opened_at', 'DESC')
            ->get();
    }
}
