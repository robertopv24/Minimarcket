<?php

class PayrollManager
{
    private $db;
    private $creditManager;
    private $vaultManager;

    public function __construct($db)
    {
        $this->db = $db;
        $this->creditManager = new CreditManager($db);
        $this->vaultManager = new VaultManager($db);
    }

    /**
     * Obtener listado de empleados con información de nómina y estado de pago calculado.
     * Retorna array de usuarios enriquecido con:
     * - last_payment_date
     * - next_payment_due
     * - status ('paid', 'due', 'overdue')
     */
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
            // Buscar último pago
            $lastPayment = $this->getLastPayment($emp['id']);

            $emp['last_payment_date'] = $lastPayment ? $lastPayment['payment_date'] : 'Nunca';
            $emp['next_payment_due'] = $this->calculateNextPaymentDate($emp['salary_frequency'], $lastPayment['payment_date'] ?? null); // Fecha vencida si null

            // Calcular estado
            $today = date('Y-m-d');
            if ($today >= $emp['next_payment_due']) {
                $daysOver = (strtotime($today) - strtotime($emp['next_payment_due'])) / (60 * 60 * 24);
                $emp['status'] = $daysOver > 3 ? 'overdue' : 'due'; // 3 días de gracia antes de ser "atrasado"
            } else {
                $emp['status'] = 'paid';
            }

            // --- DEUDAS PENDIENTES ---
            $debts = $this->creditManager->getPendingEmployeeDebts($emp['id']);
            $totalDebt = 0;
            foreach ($debts as $d) {
                // Sumar monto restante
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
            // Si nunca se le ha pagado, se asume que se debe pagar HOY (o cuando se registró)
            // Para simplificar: HOY
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

    /**
     * Registrar un pago de nómina
     */
    public function registerPayment($userId, $amount, $paymentDate, $periodStart, $periodEnd, $methodId, $notes, $adminId)
    {
        try {
            $this->db->beginTransaction();

            // 1. Detectar Deudas y Calcular Neto
            $debts = $this->creditManager->getPendingEmployeeDebts($userId);
            $deductionTotal = 0;
            $debtsToPay = [];

            // Asignamos deuda hasta cubrir el salario o hasta cubrir la deuda
            $remainingSalary = $amount;

            foreach ($debts as $d) {
                if ($remainingSalary <= 0)
                    break;

                $debtPending = $d['amount'] - $d['paid_amount'];
                $payThis = min($remainingSalary, $debtPending);

                $deductionTotal += $payThis;
                $remainingSalary -= $payThis;
                $debtsToPay[] = ['id' => $d['id'], 'amount' => $payThis];
            }

            $netToPay = $amount - $deductionTotal;

            // 1b. Insertar el pago en payroll_payments
            // Usamos netToPay como el monto "real" pagado de caja? NO, el salario es amount.
            // Pero en caja sale netToPay. 
            // Guardamos: amount (Bruto), deductions_amount (Deuda).

            $stmt = $this->db->prepare("INSERT INTO payroll_payments 
                (user_id, amount, deductions_amount, payment_date, period_start, period_end, payment_method_id, notes, created_by) 
                VALUES (:uid, :amount, :deduc, :pdate, :pstart, :pend, :method, :notes, :admin)");

            $stmt->execute([
                ':uid' => $userId,
                ':amount' => $amount, // Bruto
                ':deduc' => $deductionTotal,
                ':pdate' => $paymentDate,
                ':pstart' => $periodStart,
                ':pend' => $periodEnd,
                ':method' => $methodId,
                ':notes' => $notes . ($deductionTotal > 0 ? " (Deducción Deuda: $$deductionTotal)" : ""),
                ':admin' => $adminId
            ]);

            $payrollId = $this->db->lastInsertId();

            // 1c. Marcar Deudas como Pagadas
            foreach ($debtsToPay as $dp) {
                $this->creditManager->payDebt($dp['id'], $dp['amount'], 'payroll_deduction', $payrollId);
            }

            // 2. Crear Transacción financiera (Egreso / Expense) -> POR EL NETO
            // Necesitamos una sesion de caja activa para el admin
            $stmtSession = $this->db->prepare("SELECT id FROM cash_sessions WHERE user_id = :uid AND status = 'open' LIMIT 1");
            $stmtSession->execute([':uid' => $adminId]);
            $session = $stmtSession->fetch(PDO::FETCH_ASSOC);

            // Si no hay sesion abierta, buscamos la ultima cerrada o usamos 0 (sistema)
            // Para mantener consistencia, creamos una transacción global si no hay sesión, 
            // pero el constraint FK podría fallar si se requiere una valida.
            // Asumiremos que el admin debe tener caja abierta o usamos una logica de 'Caja Central'.
            // Por ahora, usaremos la ultima sesion del usuario o 0 si lo permite. 
            // Vimos en db.sql que ID es INT(11) NOT NULL, no dice F.K. explicita en el dump, pero asumamos integridad.

            $sessionId = $session ? $session['id'] : 0;
            // OJO: Si transactions require FK valida a cash_sessions, esto fallará si no hay id 0.
            // Consultaremos la ultima sesion valida si no hay abierta.
            if ($sessionId === 0) {
                $stmtLast = $this->db->prepare("SELECT id FROM cash_sessions ORDER BY id DESC LIMIT 1");
                $stmtLast->execute();
                $last = $stmtLast->fetch(PDO::FETCH_ASSOC);
                $sessionId = $last ? $last['id'] : 1;
            }

            // Buscamos el job_role del usuario y el método de pago
            $user = $this->getUserJobRole($userId);
            $payFreq = ucfirst($user['salary_frequency'] ?? 'Monthly');

            // Obtener información del método de pago
            $stmtMethod = $this->db->prepare("SELECT name, type, currency FROM payment_methods WHERE id = ?");
            $stmtMethod->execute([$methodId]);
            $paymentMethod = $stmtMethod->fetch(PDO::FETCH_ASSOC);

            if (!$paymentMethod) {
                throw new Exception("Método de pago no válido");
            }

            // Obtener tasa de cambio actual
            $exchangeRate = 1;
            if (isset($GLOBALS['config'])) {
                $exchangeRate = $GLOBALS['config']->get('exchange_rate');
            } else {
                $stmtRate = $this->db->query("SELECT config_value FROM global_config WHERE config_key = 'exchange_rate'");
                $rateData = $stmtRate->fetch(PDO::FETCH_ASSOC);
                $exchangeRate = $rateData ? floatval($rateData['config_value']) : 1;
            }

            // Convertir el monto según la moneda del método de pago
            $paymentCurrency = $paymentMethod['currency'];
            $paymentAmount = $netToPay; // El salario está en USD

            if ($paymentCurrency === 'VES') {
                // Convertir USD a VES
                $paymentAmount = $netToPay * $exchangeRate;
            }

            // Calcular amount_usd_ref
            $amountUsdRef = ($paymentCurrency === 'USD') ? $paymentAmount : ($paymentAmount / $exchangeRate);

            $desc = "Pago Nómina: " . ($user['name'] ?? 'Empleado') . " ($payFreq)";
            if ($deductionTotal > 0) {
                $desc .= " [Neto: $$netToPay | Desc: $$deductionTotal]";
            }

            // Insertar Transacción en la moneda correcta
            if ($netToPay > 0) {
                $stmtTrans = $this->db->prepare("INSERT INTO transactions 
                    (cash_session_id, type, amount, currency, exchange_rate, amount_usd_ref, payment_method_id, reference_type, reference_id, description, created_by) 
                    VALUES (:sid, 'expense', :amount, :currency, :rate, :amount_usd, :pm, 'adjustment', :pid, :desc, :admin)");

                $stmtTrans->execute([
                    ':sid' => $sessionId,
                    ':amount' => $paymentAmount,
                    ':currency' => $paymentCurrency,
                    ':rate' => $exchangeRate,
                    ':amount_usd' => $amountUsdRef,
                    ':pm' => $methodId,
                    ':pid' => $payrollId,
                    ':desc' => $desc,
                    ':admin' => $adminId
                ]);
                $transId = $this->db->lastInsertId();

                // Actualizar transaction_id en payroll_payments
                $stmtUpdate = $this->db->prepare("UPDATE payroll_payments SET transaction_id = :tid WHERE id = :pid");
                $stmtUpdate->execute([':tid' => $transId, ':pid' => $payrollId]);
            } else {
                // Si el neto es 0 (Todo deuda), no hay movimiento de caja.
            }

            // 4. Actualizar Bóveda SOLO si el pago es en EFECTIVO
            // Los pagos digitales/bancarios se reflejan en el balance calculado de transactions
            if ($paymentMethod['type'] === 'cash' && $netToPay > 0) {
                $vaultDesc = "Pago Nómina: " . ($user['name'] ?? 'Empleado') . " (ID: $payrollId)";

                $vaultResult = $this->vaultManager->registerMovement(
                    'withdrawal',
                    'owner_withdrawal',
                    $paymentAmount, // Monto en la moneda del método
                    $paymentCurrency,
                    $vaultDesc,
                    $adminId,
                    $payrollId,
                    false // No usar transacción propia
                );

                if ($vaultResult !== true) {
                    throw new Exception("Error al actualizar bóveda: $vaultResult");
                }
            }

            $this->db->commit();
            return $payrollId;

        } catch (Exception $e) {
            $this->db->rollBack();
            return "Error: " . $e->getMessage();
        }
    }

    private function getUserJobRole($id)
    {
        $stmt = $this->db->prepare("SELECT name, salary_frequency, job_role FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getHistory($limit = 50)
    {
        $sql = "SELECT p.*, u.name as employee_name, u.job_role 
                FROM payroll_payments p 
                JOIN users u ON p.user_id = u.id 
                ORDER BY p.payment_date DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
