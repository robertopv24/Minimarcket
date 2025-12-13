<?php

namespace Minimarcket\Modules\Sales\Services;

use Minimarcket\Modules\Sales\Repositories\CreditRepository;

/**
 * Class CreditService
 * 
 * Servicio de lógica de negocio para créditos y cuentas por cobrar.
 */
class CreditService
{
    protected CreditRepository $repository;

    public function __construct(CreditRepository $repository)
    {
        $this->repository = $repository;
    }

    public function createClient(string $name, string $docId, string $phone, string $email, string $address, float $limit = 0): string
    {
        return $this->repository->create([
            'name' => $name,
            'document_id' => $docId,
            'phone' => $phone,
            'email' => $email,
            'address' => $address,
            'credit_limit' => $limit
        ]);
    }

    public function searchClients(string $query): array
    {
        return $this->repository->searchClients($query);
    }

    public function getClientById(int $id): ?array
    {
        return $this->repository->find($id);
    }

    public function registerDebt(int $orderId, float $amount, ?int $clientId = null, ?int $userId = null, ?string $dueDate = null, string $notes = '', bool $useTransaction = true): string
    {
        return $this->repository->registerDebt([
            'order_id' => $orderId,
            'amount' => $amount,
            'client_id' => $clientId,
            'user_id' => $userId,
            'due_date' => $dueDate,
            'notes' => $notes
        ]);
    }

    public function payDebt(int $arId, float $amountToPay, int $paymentMethodId, string $paymentRef = '', string $paymentCurrency = 'USD', int $userId = 1, int $sessionId = 1): bool
    {
        // TODO: Registrar transacción financiera
        // Por ahora solo actualizamos la deuda
        return $this->repository->updateDebtPayment($arId, $amountToPay);
    }

    public function getPendingDebtsByClient(int $clientId): array
    {
        return $this->repository->getPendingDebtsByClient($clientId);
    }
}
