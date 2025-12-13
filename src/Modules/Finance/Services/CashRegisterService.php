<?php

namespace Minimarcket\Modules\Finance\Services;

use Minimarcket\Modules\Finance\Repositories\CashRegisterRepository;

/**
 * Class CashRegisterService
 * 
 * Servicio de lógica de negocio para sesiones de caja registradora.
 */
class CashRegisterService
{
    protected CashRegisterRepository $repository;

    public function __construct(CashRegisterRepository $repository)
    {
        $this->repository = $repository;
    }

    public function hasOpenSession(int $userId): bool
    {
        return $this->repository->hasOpenSession($userId);
    }

    public function getStatus(int $userId): ?array
    {
        return $this->repository->getStatus($userId);
    }

    public function openRegister(int $userId, float $initialUsd, float $initialVes): string
    {
        // Verificar que no haya sesión abierta
        if ($this->hasOpenSession($userId)) {
            throw new \Exception("El usuario ya tiene una sesión de caja abierta.");
        }

        return $this->repository->openSession($userId, $initialUsd, $initialVes);
    }

    public function getSessionReport(int $userId): ?array
    {
        return $this->repository->getSessionReport($userId);
    }

    public function closeRegister(int $userId, float $countedUsd, float $countedVes): bool
    {
        return $this->repository->closeSession($userId, $countedUsd, $countedVes);
    }

    public function searchSessions(string $query = ''): array
    {
        return $this->repository->searchSessions($query);
    }
}
