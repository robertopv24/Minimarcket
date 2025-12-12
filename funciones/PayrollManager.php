<?php
use Minimarcket\Core\Container;
use Minimarcket\Modules\HR\Services\PayrollService;

/**
 * @deprecated This class is a legacy proxy. Use Minimarcket\Modules\HR\Services\PayrollService instead.
 */
class PayrollManager
{
    private $service;

    public function __construct($db = null)
    {
        $container = Container::getInstance();
        try {
            $this->service = $container->get(PayrollService::class);
        } catch (Exception $e) {
            $this->service = new PayrollService($db);
        }
    }

    public function getPayrollStatus($filterRole = null)
    {
        return $this->service->getPayrollStatus($filterRole);
    }
}
