<?php

use Minimarcket\Core\Container;
use Minimarcket\Modules\Finance\Services\CashRegisterService;

/**
 * @deprecated This class is a legacy proxy. Use Minimarcket\Modules\Finance\Services\CashRegisterService instead.
 */
class CashRegisterManager
{
    private $service;

    public function __construct($db = null)
    {
        $container = Container::getInstance();
        try {
            $this->service = $container->get(CashRegisterService::class);
        } catch (Exception $e) {
            $this->service = new CashRegisterService($db);
        }
    }

    public function hasOpenSession($userId)
    {
        return $this->service->hasOpenSession($userId);
    }

    public function getStatus($userId)
    {
        return $this->service->getStatus($userId);
    }

    public function openRegister($userId, $initialUsd, $initialVes)
    {
        return $this->service->openRegister($userId, $initialUsd, $initialVes);
    }

    public function getSessionReport($userId)
    {
        return $this->service->getSessionReport($userId);
    }

    public function closeRegister($userId, $countedUsd, $countedVes)
    {
        return $this->service->closeRegister($userId, $countedUsd, $countedVes);
    }

    public function searchSessions($query = '')
    {
        return $this->service->searchSessions($query);
    }
}