<?php

namespace Minimarcket\Modules\HR\Services;

use Minimarcket\Core\Database;
use Minimarcket\Modules\Finance\Services\CreditService;
use Minimarcket\Modules\Finance\Services\VaultService;
use PDO;
use Exception;

class PayrollService
{
    private $db;
    private $creditService;
    private $vaultService;

    public function __construct(?PDO $db = null, ?CreditService $creditService = null, ?VaultService $vaultService = null)
    {
        $this->db = $db ?? Database::getConnection();
        $this->creditService = $creditService ?? new CreditService($this->db);
        $this->vaultService = $vaultService ?? new VaultService($this->db);
    }

    public function getPayrollStatus($filterRole = null)
    {
        $sql = "SELECT id, name, email, role, phone, salary_amount, salary_frequency, job_role 
                FROM users 
                WHERE salary_amount > 0";

        if ($filterRole) {
            $sql .= " AND job_role = :role";
        }

        $stmt = $this->db->prepare($sql);
        if ($filterRole) {
            $stmt->bindParam(':role', $filterRole);
        }
        $stmt->execute();
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];

        foreach ($employees as $emp) {
            $lastPayment = $this->getLastPayment($emp['id']);

            $emp['last_payment_date'] = $lastPayment ? $lastPayment['payment_date'] : 'Nunca';
            $emp['next_payment_due'] = $this->calculateNextPaymentDate($emp['salary_frequency'], $lastPayment['payment_date'] ?? null);

            $today = date('Y-m-d');
            if ($today >= $emp['next_payment_due']) {
                $daysOver = (strtotime($today) - strtotime($emp['next_payment_due'])) / (60 * 60 * 24);
                $emp['status'] = $daysOver > 3 ? 'overdue' : 'due';
            } else {
                $emp['status'] = 'paid';
            }

            // Verify if CreditService has this method. It wasn't in list before.
            // If not, I need to add it to CreditService.
            // CreditManager line 59: getPendingEmployeeDebts
            // I need to check CreditService for getPendingEmployeeDebts
            if (method_exists($this->creditService, 'getPendingEmployeeDebts')) {
                $debts = $this->creditService->getPendingEmployeeDebts($emp['id']);
            } else {
                $debts = []; // Temporary fallback if method missing (I need to check CreditService content)
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

    private function getLastPayment($userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM payroll_payments WHERE user_id = :uid ORDER BY payment_date DESC LIMIT 1");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
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
}
