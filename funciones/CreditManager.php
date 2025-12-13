<?php

use Minimarcket\Modules\Sales\Services\CreditService;

/**
 * @deprecated This class is a legacy proxy. Use Minimarcket\Modules\Sales\Services\CreditService instead.
 */
class CreditManager
{
    private $service;

    public function __construct($db = null)
    {
        global $app;
        if (isset($app)) {
            $this->service = $app->getContainer()->get(CreditService::class);
        } else {
            throw new \Exception("Application not bootstrapped. Cannot instantiate CreditManager.");
        }
    }

    public function createClient($name, $docId, $phone, $email, $address, $limit = 0)
    {
        return $this->service->createClient($name, $docId, $phone, $email, $address, $limit);
    }

    public function searchClients($query)
    {
        return $this->service->searchClients($query);
    }

    public function getClientById($id)
    {
        return $this->service->getClientById($id);
    }

    public function registerDebt($orderId, $amount, $clientId = null, $userId = null, $dueDate = null, $notes = '', $useTransaction = true)
    {
        return $this->service->registerDebt($orderId, $amount, $clientId, $userId, $dueDate, $notes, $useTransaction);
    }

    public function payDebt($arId, $amountToPay, $paymentMethodId, $paymentRef = '', $paymentCurrency = 'USD', $userId = 1, $sessionId = 1)
    {
        return $this->service->payDebt($arId, $amountToPay, $paymentMethodId, $paymentRef, $paymentCurrency, $userId, $sessionId);
    }

    public function getPendingDebtsByClient($clientId)
    {
        return $this->service->getPendingDebtsByClient($clientId);
    }
}
