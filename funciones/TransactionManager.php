<?php

use Minimarcket\Modules\Finance\Services\TransactionService;

/**
 * @deprecated This class is a legacy proxy. Use Minimarcket\Modules\Finance\Services\TransactionService instead.
 */
class TransactionManager
{
    private $service;

    public function __construct($db = null)
    {
        global $app;
        if (isset($app)) {
            $this->service = $app->getContainer()->get(TransactionService::class);
        } else {
            throw new \Exception("Application not bootstrapped. Cannot instantiate TransactionManager.");
        }
    }

    public function processOrderPayments($orderId, $payments, $userId, $sessionId)
    {
        return $this->service->processOrderPayments($orderId, $payments, $userId, $sessionId);
    }

    public function registerOrderChange($orderId, $amountNominal, $currency, $methodId, $userId, $sessionId)
    {
        return $this->service->registerOrderChange($orderId, $amountNominal, $currency, $methodId, $userId, $sessionId);
    }

    public function registerPurchasePayment($purchaseId, $amount, $currency, $methodId, $userId)
    {
        return $this->service->registerPurchasePayment($purchaseId, $amount, $currency, $methodId, $userId);
    }

    public function registerTransaction($type, $amount, $description, $userId, $referenceType = 'manual', $referenceId = 0, $currency = 'USD')
    {
        return $this->service->registerTransaction($type, $amount, $description, $userId, $referenceType, $referenceId, $currency);
    }

    public function getTransactionsByDate($startDate, $endDate)
    {
        return $this->service->getTransactionsByDate($startDate, $endDate);
    }

    public function getPaymentMethods()
    {
        return $this->service->getPaymentMethods();
    }
}