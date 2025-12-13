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
        global $app;
        if (isset($app)) {
            $this->service = $app->getContainer()->get(ExchangeRateService::class);
        } else {
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