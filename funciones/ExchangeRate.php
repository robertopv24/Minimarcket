<?php
require_once __DIR__ . '/Config.php';

use Minimarcket\Core\Container;
use Minimarcket\Modules\Finance\Services\ExchangeRateService;

/**
 * @deprecated This class is a legacy proxy. Use Minimarcket\Modules\Finance\Services\ExchangeRateService instead.
 */
class ExchangeRate
{
    private $service;

    public function __construct()
    {
        // Original constructor called Database::getInstance inside.
        // We do it via container
        $container = Container::getInstance();
        try {
            $this->service = $container->get(ExchangeRateService::class);
        } catch (Exception $e) {
            $this->service = new ExchangeRateService();
        }
    }

    public function getLatestRate()
    {
        return $this->service->getLatestRate();
    }

    public function addRate($newRate)
    {
        return $this->service->addRate($newRate);
    }

    public function updateRate($newRate)
    {
        return $this->service->updateRate($newRate);
    }

    public function getRateHistory($limit = 10)
    {
        return $this->service->getRateHistory($limit);
    }
}
?>