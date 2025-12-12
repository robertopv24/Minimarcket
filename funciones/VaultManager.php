<?php

use Minimarcket\Core\Container;
use Minimarcket\Modules\Finance\Services\VaultService;

/**
 * @deprecated This class is a legacy proxy. Use Minimarcket\Modules\Finance\Services\VaultService instead.
 */
class VaultManager
{
    private $service;

    public function __construct($db = null)
    {
        $container = Container::getInstance();
        try {
            $this->service = $container->get(VaultService::class);
        } catch (Exception $e) {
            $this->service = new VaultService($db);
        }
    }

    public function getBalance()
    {
        return $this->service->getBalance();
    }

    public function registerMovement($type, $origin, $amount, $currency, $description, $userId, $refId = null, $useTransaction = true)
    {
        return $this->service->registerMovement($type, $origin, $amount, $currency, $description, $userId, $refId, $useTransaction);
    }

    public function transferFromSession($sessionId, $amountUsd, $amountVes, $userId)
    {
        return $this->service->transferFromSession($sessionId, $amountUsd, $amountVes, $userId);
    }

    public function getAllMovements()
    {
        return $this->service->getAllMovements();
    }
}