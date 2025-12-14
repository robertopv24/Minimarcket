<?php

namespace Minimarcket\Modules\HR\Services;

use Minimarcket\Modules\HR\Repositories\PayrollRepository;
use Minimarcket\Modules\HR\Repositories\EmployeeRepository;
use Minimarcket\Modules\Finance\Services\CreditService;
use Minimarcket\Modules\Finance\Services\VaultService;
use Exception;

class PayrollService
{
    private PayrollRepository $payrollRepository;
    private EmployeeRepository $employeeRepository;
    private ?CreditService $creditService;
    private ?VaultService $vaultService;

    public function __construct(
        PayrollRepository $payrollRepository,
        EmployeeRepository $employeeRepository,
        ?CreditService $creditService = null,
        ?VaultService $vaultService = null
    ) {
        $this->payrollRepository = $payrollRepository;
        $this->employeeRepository = $employeeRepository;
        $this->creditService = $creditService;
        $this->vaultService = $vaultService;
    }

    public function getPayrollStatus($filterRole = null)
    {
        // 1. Obtener Empleados
        $employees = $this->employeeRepository->getEmployees($filterRole);
        $results = [];

        foreach ($employees as $emp) {
            // 2. Obtener último pago
            $lastPayment = $this->payrollRepository->getLastPayment($emp['id']);

            $emp['last_payment_date'] = $lastPayment ? $lastPayment['payment_date'] : 'Nunca';
            $emp['next_payment_due'] = $this->calculateNextPaymentDate($emp['salary_frequency'], $lastPayment['payment_date'] ?? null);

            $today = date('Y-m-d');
            if ($today >= $emp['next_payment_due']) {
                $daysOver = (strtotime($today) - strtotime($emp['next_payment_due'])) / (60 * 60 * 24);
                $emp['status'] = $daysOver > 3 ? 'overdue' : 'due';
            } else {
                $emp['status'] = 'paid';
            }

            // 3. Obtener Deudas (Adelantos)
            $debts = [];
            if ($this->creditService) {
                $debts = $this->creditService->getPendingEmployeeDebts($emp['id']);
            }

            $totalDebt = 0;
            foreach ($debts as $d) {
                $totalDebt += ($d['amount'] - $d['paid_amount']);
            }
            $emp['pending_debt'] = $totalDebt;
            $emp['net_salary'] = max(0, $emp['salary_amount'] - $totalDebt);

            $results[] = $emp;
        }

        return $results;
    }

    public function registerPayment($userId, $amount, $notes, $creatorId)
    {
        try {
            $this->payrollRepository->beginTransaction();

            // Verificar fondos si VaultService está disponible? (Opcional, legacy no lo mostraba explícito pero es buena práctica)
            // Por ahora solo registramos

            $success = $this->payrollRepository->logPayment([
                'user_id' => $userId,
                'amount' => $amount,
                'payment_date' => date('Y-m-d'),
                'notes' => $notes,
                'created_by' => $creatorId
            ]);

            if (!$success)
                throw new Exception("Error registering payment.");

            $this->payrollRepository->commit();
            return true;

        } catch (Exception $e) {
            if ($this->payrollRepository->inTransaction())
                $this->payrollRepository->rollBack();
            return false;
        }
    }

    private function calculateNextPaymentDate($freq, $lastDate)
    {
        if (!$lastDate) {
            return date('Y-m-d');
        }

        switch ($freq) {
            case 'weekly':
                return date('Y-m-d', strtotime($lastDate . ' + 7 days'));
            case 'biweekly':
                return date('Y-m-d', strtotime($lastDate . ' + 15 days'));
            case 'monthly':
                return date('Y-m-d', strtotime($lastDate . ' + 1 month'));
            default:
                return date('Y-m-d', strtotime($lastDate . ' + 30 days'));
        }
    }

    public function getHistory(int $limit = 10): array
    {
        return $this->payrollRepository->getHistory($limit);
    }
}
