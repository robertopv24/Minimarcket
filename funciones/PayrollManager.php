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
        global $app;
        if (isset($app)) {
            $this->service = $app->getContainer()->get(PayrollService::class);
        } else {
            // Manual fallback instantiation with dependencies
            $connection = new \Minimarcket\Core\Database\ConnectionManager();
            $payrollRepo = new \Minimarcket\Modules\HR\Repositories\PayrollRepository($connection);
            $emplRepo = new \Minimarcket\Modules\HR\Repositories\EmployeeRepository($connection);
            $creditService = new \Minimarcket\Modules\Finance\Services\CreditService($connection->getConnection());

            $vaultRepo = new \Minimarcket\Modules\Finance\Repositories\VaultRepository($connection);
            $vaultService = new \Minimarcket\Modules\Finance\Services\VaultService($vaultRepo);

            $this->service = new PayrollService($payrollRepo, $emplRepo, $creditService, $vaultService);
        }
    }

    public function getPayrollStatus($filterRole = null)
    {
        return $this->service->getPayrollStatus($filterRole);
    }
}
