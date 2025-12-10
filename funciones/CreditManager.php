<?php

class CreditManager
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // --- CLIENTES ---
    public function createClient($name, $docId, $phone, $email, $address, $limit = 0)
    {
        $stmt = $this->db->prepare("INSERT INTO clients (name, document_id, phone, email, address, credit_limit) 
            VALUES (:name, :doc, :phone, :email, :addr, :limit)");
        $stmt->execute([
            ':name' => $name,
            ':doc' => $docId,
            ':phone' => $phone,
            ':email' => $email,
            ':addr' => $address,
            ':limit' => $limit
        ]);
        return $this->db->lastInsertId();
    }

    public function searchClients($query)
    {
        $stmt = $this->db->prepare("SELECT * FROM clients WHERE name LIKE ? OR document_id LIKE ? LIMIT 10");
        $searchTerm = "%$query%";
        $stmt->execute([$searchTerm, $searchTerm]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getClientById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // --- CUENTAS POR COBRAR ---

    /**
     * Registrar una deuda nueva (Crédito a Cliente o Empleado)
     */
    public function registerDebt($orderId, $amount, $clientId = null, $userId = null, $dueDate = null, $notes = '', $useTransaction = true)
    {
        // Validar límites si es cliente
        if ($clientId) {
            $client = $this->getClientById($clientId);
            if (($client['current_debt'] + $amount) > $client['credit_limit']) {
                return "Error: Límite de crédito excedido. Actual: {$client['current_debt']} + {$amount} > {$client['credit_limit']}";
            }
        }

        try {
            if ($useTransaction) {
                $this->db->beginTransaction();
            }

            // Insertar Cuenta Por Cobrar
            $stmt = $this->db->prepare("INSERT INTO accounts_receivable (order_id, client_id, user_id, amount, due_date, notes) 
                VALUES (:oid, :cid, :uid, :amt, :due, :notes)");
            $stmt->execute([
                ':oid' => $orderId,
                ':cid' => $clientId,
                ':uid' => $userId, // Si es empleado
                ':amt' => $amount,
                ':due' => $dueDate,
                ':notes' => $notes
            ]);
            $arId = $this->db->lastInsertId();

            // Actualizar deuda total del cliente
            if ($clientId) {
                $stmtUpd = $this->db->prepare("UPDATE clients SET current_debt = current_debt + :amt WHERE id = :id");
                $stmtUpd->execute([':amt' => $amount, ':id' => $clientId]);
            }

            if ($useTransaction) {
                $this->db->commit();
            }
            return $arId;

        } catch (Exception $e) {
            if ($useTransaction) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Obtener deudas pendientes de un empleado
     */
    public function getPendingEmployeeDebts($userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM accounts_receivable WHERE user_id = ? AND status IN ('pending','partial')");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener deudas pendientes de un cliente
     */
    public function getPendingClientDebts($clientId)
    {
        $stmt = $this->db->prepare("SELECT * FROM accounts_receivable WHERE client_id = ? AND status IN ('pending','partial')");
        $stmt->execute([$clientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Marcar deuda como pagada (Ej: al descontar de nómina o cliente paga)
     * $method: 'cash', 'transfer', 'payroll_deduction'
     * $paymentMethodId: ID del método de pago (para registrar transacción)
     * $sessionId: ID de sesión de caja activa (opcional, se busca automáticamente)
     */
    public function payDebt($arId, $amountToPay, $method = 'cash', $transactionRef = null, $paymentMethodId = null, $sessionId = null)
    {
        require_once __DIR__ . '/debug_logger.php';

        debugLog("payDebt called", [
            'arId' => $arId,
            'amount' => $amountToPay,
            'method' => $method,
            'paymentMethodId' => $paymentMethodId,
            'sessionId' => $sessionId
        ]);

        $ar = $this->getDebtById($arId);
        if (!$ar) {
            debugLog("payDebt: Debt not found for arId=$arId");
            return false;
        }

        debugLog("payDebt: Found debt", [
            'paid_amount' => $ar['paid_amount'],
            'status' => $ar['status']
        ]);

        $newPaidAmount = $ar['paid_amount'] + $amountToPay;
        $remaining = $ar['amount'] - $newPaidAmount;

        $status = ($remaining <= 0.0001) ? 'paid' : 'partial';
        if ($method == 'payroll_deduction' && $status == 'paid')
            $status = 'deducted';

        debugLog("payDebt: Calculated", [
            'newPaidAmount' => $newPaidAmount,
            'status' => $status
        ]);

        try {
            $this->db->beginTransaction();
            debugLog("payDebt: Transaction started");

            // Actualizar cuenta por cobrar
            $stmt = $this->db->prepare("UPDATE accounts_receivable SET paid_amount = ?, status = ? WHERE id = ?");
            $result = $stmt->execute([$newPaidAmount, $status, $arId]);
            debugLog("payDebt: UPDATE accounts_receivable", [
                'result' => $result ? 'true' : 'false',
                'rowCount' => $stmt->rowCount()
            ]);

            // Si es Cliente, actualizar su saldo global
            if ($ar['client_id']) {
                $stmtUpd = $this->db->prepare("UPDATE clients SET current_debt = current_debt - ? WHERE id = ?");
                $resultUpd = $stmtUpd->execute([$amountToPay, $ar['client_id']]);
                debugLog("payDebt: UPDATE clients", [
                    'result' => $resultUpd ? 'true' : 'false',
                    'rowCount' => $stmtUpd->rowCount()
                ]);
            }

            // NUEVO: Registrar transacción en caja si es pago en efectivo/transferencia
            if (($method === 'cash' || $method === 'transfer') && $paymentMethodId) {
                debugLog("payDebt: Processing cash/transfer transaction");

                // Si no se proporciona sessionId, buscar sesión activa
                if (!$sessionId) {
                    // Buscar cualquier sesión abierta (idealmente del usuario actual)
                    $stmtSess = $this->db->prepare("SELECT id FROM cash_sessions WHERE status = 'open' ORDER BY opened_at DESC LIMIT 1");
                    $stmtSess->execute();
                    $sessionId = $stmtSess->fetchColumn();
                    debugLog("payDebt: Found sessionId=$sessionId");
                }

                if ($sessionId) {
                    // Obtener moneda del método de pago
                    $stmtMethod = $this->db->prepare("SELECT currency FROM payment_methods WHERE id = ?");
                    $stmtMethod->execute([$paymentMethodId]);
                    $currency = $stmtMethod->fetchColumn();
                    debugLog("payDebt: Payment method currency=$currency");

                    // Si es VES, necesitamos tasa de cambio para amount_usd_ref
                    $amountUsdRef = $amountToPay;
                    $exchangeRate = 1.00;

                    if ($currency === 'VES') {
                        $stmtRate = $this->db->prepare("SELECT config_value FROM config WHERE config_key = 'exchange_rate'");
                        $stmtRate->execute();
                        $exchangeRate = floatval($stmtRate->fetchColumn() ?: 1);
                        $amountUsdRef = $amountToPay / $exchangeRate;
                        debugLog("payDebt: VES conversion", [
                            'rate' => $exchangeRate,
                            'amountUsdRef' => $amountUsdRef
                        ]);
                    }

                    $description = "Pago de deuda AR#{$arId}" . ($ar['client_id'] ? " - Cliente ID: {$ar['client_id']}" : "");

                    // Obtener usuario actual para created_by
                    $createdBy = $_SESSION['user_id'] ?? 1; // Default a 1 si no hay sesión

                    // Registrar ingreso en transactions
                    $stmtTx = $this->db->prepare("
                        INSERT INTO transactions (cash_session_id, type, amount, currency, exchange_rate, amount_usd_ref, payment_method_id, reference_type, reference_id, description, created_by, created_at)
                        VALUES (?, 'income', ?, ?, ?, ?, ?, 'debt_payment', ?, ?, ?, NOW())
                    ");

                    $resultTx = $stmtTx->execute([
                        $sessionId,
                        $amountToPay,           // amount en moneda nominal
                        $currency,              // USD o VES
                        $exchangeRate,          // tasa de cambio
                        $amountUsdRef,          // amount en USD
                        $paymentMethodId,
                        $arId,                  // reference_id
                        $description,           // description (antes era notes)
                        $createdBy              // created_by
                    ]);
                    debugLog("payDebt: INSERT transactions result=" . ($resultTx ? 'true' : 'false'));
                } else {
                    debugLog("payDebt: WARNING - No session found, skipping transaction insert");
                }
            }

            $this->db->commit();
            debugLog("payDebt: Transaction committed successfully");
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            debugLog("payDebt ERROR: " . $e->getMessage());
            debugLog("payDebt ERROR trace: " . $e->getTraceAsString());
            return false;
        }
    }

    public function getDebtById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM accounts_receivable WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllPendingDebts()
    {
        // Traer con datos de cliente o usuario
        $sql = "SELECT ar.*, c.name as client_name, u.name as employee_name 
                FROM accounts_receivable ar
                LEFT JOIN clients c ON ar.client_id = c.id
                LEFT JOIN users u ON ar.user_id = u.id
                WHERE ar.status != 'paid' AND ar.status != 'deducted'
                ORDER BY ar.created_at DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
