<?php

namespace Minimarcket\Modules\Finance\Services;

use Minimarcket\Core\Database;
use PDO;
use Exception;

class CreditService
{
    private $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
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
        if (empty($query)) {
            $stmt = $this->db->query("SELECT * FROM clients ORDER BY name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
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

    public function updateClient($id, $name, $docId, $phone, $email, $address, $limit)
    {
        $stmt = $this->db->prepare("UPDATE clients SET name = ?, document_id = ?, phone = ?, email = ?, address = ?, credit_limit = ? WHERE id = ?");
        return $stmt->execute([$name, $docId, $phone, $email, $address, $limit, $id]);
    }

    public function deleteClient($id)
    {
        $client = $this->getClientById($id);
        if ($client['current_debt'] > 0.01) {
            return "No se puede eliminar. Cliente tiene deuda pendiente.";
        }
        $stmt = $this->db->prepare("DELETE FROM clients WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getDebtors()
    {
        $stmt = $this->db->prepare("SELECT * FROM clients WHERE current_debt > 0.01 ORDER BY current_debt DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- CUENTAS POR COBRAR ---
    public function registerDebt($orderId, $amount, $clientId = null, $userId = null, $dueDate = null, $notes = '', $useTransaction = true)
    {
        if ($clientId) {
            $client = $this->getClientById($clientId);
            if (!$client) {
                return "Error: Cliente no encontrado (ID: $clientId)";
            }
            if (($client['current_debt'] + $amount) > $client['credit_limit']) {
                return "Error: Límite de crédito excedido. Actual: {$client['current_debt']} + {$amount} > {$client['credit_limit']}";
            }
        }

        try {
            if ($useTransaction) {
                $this->db->beginTransaction();
            }

            $stmt = $this->db->prepare("INSERT INTO accounts_receivable (order_id, client_id, user_id, amount, due_date, notes) 
                VALUES (:oid, :cid, :uid, :amt, :due, :notes)");
            $stmt->execute([
                ':oid' => $orderId,
                ':cid' => $clientId,
                ':uid' => $userId,
                ':amt' => $amount,
                ':due' => $dueDate,
                ':notes' => $notes
            ]);
            $arId = $this->db->lastInsertId();

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

    public function payDebt($arId, $amountToPay, $paymentMethodId, $paymentRef = '', $paymentCurrency = 'USD', $userId = 1, $sessionId = 1)
    {
        try {
            $this->db->beginTransaction();

            // 1. Obtener la deuda
            $stmt = $this->db->prepare("SELECT * FROM accounts_receivable WHERE id = ? FOR UPDATE");
            $stmt->execute([$arId]);
            $ar = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ar)
                throw new Exception("Deuda no encontrada.");

            if ($ar['status'] === 'paid')
                throw new Exception("Esta deuda ya está pagada.");

            // 2. Calcular nuevo saldo
            // ATENCIÓN: $amountToPay viene en USD (porque el modal de deuda trabaja en USD)
            // SI $paymentCurrency es VES, igual $amountToPay es cuántos Dólares estoy matando de deuda.

            // PERO, para la caja, necesitamos saber cuánto dinero REAL entró.
            // Si pago $10 de deuda en VES. Entran $10 * Tasa VES.

            $rate = 1;
            global $config;
            if (isset($config))
                $rate = $config->get('exchange_rate');

            $paymentAmount = $amountToPay; // Default assumption: Paying in USD
            if ($paymentCurrency === 'VES') {
                $paymentAmount = $amountToPay * $rate;
            }

            // Registrar la transacción de entrada de dinero
            // Recibimos $paymentAmount en $paymentCurrency
            $this->logPaymentTransaction($sessionId, $paymentAmount, $paymentCurrency, $paymentMethodId, $arId, $userId, $rate);

            // Actualizar la deuda (Siempre en USD)
            $newPaid = $ar['paid_amount'] + $amountToPay;
            $status = ($newPaid >= $ar['amount']) ? 'paid' : 'partial';

            $stmtUpd = $this->db->prepare("UPDATE accounts_receivable SET paid_amount = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $stmtUpd->execute([$newPaid, $status, $arId]);

            // Actualizar saldo del cliente
            if ($ar['client_id']) {
                $stmtClient = $this->db->prepare("UPDATE clients SET current_debt = current_debt - ? WHERE id = ?");
                $stmtClient->execute([$amountToPay, $ar['client_id']]);
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            return "Error: " . $e->getMessage();
        }
    }

    private function logPaymentTransaction($sessionId, $amount, $currency, $methodId, $arId, $userId, $rate)
    {
        $amountUsdRef = ($currency === 'VES') ? ($amount / $rate) : $amount;

        $sql = "INSERT INTO transactions (cash_session_id, type, amount, currency, exchange_rate, amount_usd_ref, payment_method_id, reference_type, reference_id, description, created_by, created_at)
                VALUES (?, 'income', ?, ?, ?, ?, ?, 'debt_payment', ?, 'Abono a Deuda', ?, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sessionId, $amount, $currency, $rate, $amountUsdRef, $methodId, $arId, $userId]);
    }

    public function getPendingDebtsByClient($clientId)
    {
        $stmt = $this->db->prepare("SELECT * FROM accounts_receivable WHERE client_id = ? AND status != 'paid' ORDER BY created_at ASC");
        $stmt->execute([$clientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPendingEmployeeDebts($userId)
    {
        // TODO: Determinar cómo se almacenan los adelantos de sueldo.
        // La tabla accounts_receivable usa user_id como "Creador" (Vendedor), no como Deudor.
        // Si filtramos por user_id, obtendríamos las ventas a crédito hechas por el empleado, no sus deudas.
        // Por seguridad, retornamos vacío hasta definir la estructura de "Préstamos a Empleados".
        return [];
    }
}
