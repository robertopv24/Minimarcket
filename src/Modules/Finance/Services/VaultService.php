<?php

namespace Minimarcket\Modules\Finance\Services;

use Minimarcket\Modules\Finance\Repositories\VaultRepository;

/**
 * Class VaultService
 * 
 * Servicio de lógica de negocio para la bóveda de efectivo.
 */
class VaultService
{
    protected VaultRepository $repository;

    public function __construct(VaultRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getBalance(): array
    {
        return $this->repository->getBalance();
    }

    public function registerMovement(string $type, string $origin, float $amount, string $currency, string $description, int $userId, ?int $refId = null, bool $useTransaction = true): string
    {
        return $this->repository->registerMovement($type, $origin, $amount, $currency, $description, $userId, $refId);
    }

    public function transferFromSession(int $sessionId, float $amountUsd, float $amountVes, int $userId): bool
    {
        return $this->repository->transferFromSession($sessionId, $amountUsd, $amountVes, $userId);
    }

    public function getAllMovements(): array
    {
        return $this->repository->all();
    }
}
