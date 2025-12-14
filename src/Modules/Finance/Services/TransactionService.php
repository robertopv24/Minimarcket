<?php

namespace Minimarcket\Modules\Finance\Services;

use Minimarcket\Modules\Finance\Repositories\TransactionRepository;

/**
 * Class TransactionService
 * 
 * Servicio de lógica de negocio para transacciones financieras.
 */
class TransactionService
{
    protected TransactionRepository $repository;

    public function __construct(TransactionRepository $repository)
    {
        $this->repository = $repository;
    }

    public function processOrderPayments(int $orderId, array $payments, int $userId, int $sessionId): bool
    {
        return $this->repository->registerOrderPayments($orderId, $payments, $userId, $sessionId);
    }

    public function registerOrderChange(int $orderId, float $amountNominal, string $currency, int $methodId, int $userId, int $sessionId): string
    {
        return $this->repository->create([
            'type' => 'income',
            'amount' => $amountNominal,
            'currency' => $currency,
            'payment_method_id' => $methodId,
            'description' => "Cambio de orden #{$orderId}",
            'user_id' => $userId,
            'session_id' => $sessionId,
            'reference_type' => 'order_change',
            'reference_id' => $orderId
        ]);
    }

    public function registerPurchasePayment(int $purchaseId, float $amount, string $currency, int $methodId, int $userId): string
    {
        return $this->repository->create([
            'type' => 'expense',
            'amount' => $amount,
            'currency' => $currency,
            'payment_method_id' => $methodId,
            'description' => "Pago de compra #{$purchaseId}",
            'user_id' => $userId,
            'reference_type' => 'purchase',
            'reference_id' => $purchaseId
        ]);
    }

    public function registerTransaction(string $type, float $amount, string $description, int $userId, string $referenceType = 'manual', int $referenceId = 0, string $currency = 'USD'): string
    {
        return $this->repository->create([
            'type' => $type,
            'amount' => $amount,
            'currency' => $currency,
            'description' => $description,
            'user_id' => $userId,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId
        ]);
    }

    public function getTransactionsByDate(string $startDate, string $endDate): array
    {
        return $this->repository->getByDateRange($startDate, $endDate);
    }

    public function getPaymentMethods(): array
    {
        return $this->repository->getPaymentMethods();
    }

    public function getTransactionByReference(string $type, int $id): ?array
    {
        return $this->repository->getTransactionByReference($type, $id);
    }
}
