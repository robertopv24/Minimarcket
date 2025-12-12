<?php

namespace Minimarcket\Modules\Finance\Services;

use Minimarcket\Core\Database;
use PDO;
use Exception;

class TransactionService
{
    private $db;
    private $vaultService;

    public function __construct(?PDO $db = null, ?VaultService $vaultService = null)
    {
        $this->db = $db ?? Database::getConnection();
        $this->vaultService = $vaultService ?? new VaultService($this->db);
    }

    public function processOrderPayments($orderId, $payments, $userId, $sessionId)
    {
        try {
            foreach ($payments as $payment) {
                if ($payment['amount'] > 0) {
                    $this->logTransaction(
                        $sessionId,
                        'income',
                        $payment['amount'],
                        $payment['currency'],
                        $payment['method_id'],
                        'order',
                        $orderId,
                        'Cobro Venta #' . $orderId,
                        $userId
                    );
                }
            }
            return true;
        } catch (Exception $e) {
            error_log("Error en processOrderPayments: " . $e->getMessage());
            return false;
        }
    }

    public function registerOrderChange($orderId, $amountNominal, $currency, $methodId, $userId, $sessionId)
    {
        try {
            $this->logTransaction(
                $sessionId,
                'expense',
                $amountNominal,
                $currency,
                $methodId,
                'order',
                $orderId,
                'Vuelto Venta #' . $orderId,
                $userId
            );
            return true;
        } catch (Exception $e) {
            error_log("Error en registerOrderChange: " . $e->getMessage());
            return false;
        }
    }

    public function registerPurchasePayment($purchaseId, $amount, $currency, $methodId, $userId)
    {
        try {
            $stmt = $this->db->prepare("SELECT type, name FROM payment_methods WHERE id = ?");
            $stmt->execute([$methodId]);
            $method = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$method)
                throw new Exception("Método de pago no válido.");

            $cashSessionId = 0;

            if ($method['type'] === 'cash') {
                $descVault = "Pago a Proveedor - Compra #$purchaseId";
                $res = $this->vaultService->registerMovement(
                    'withdrawal',
                    'supplier_payment',
                    $amount,
                    $currency,
                    $descVault,
                    $userId,
                    $purchaseId
                );

                if ($res !== true)
                    throw new Exception("Fondos insuficientes en Bóveda: " . $res);
            }

            $this->logTransaction(
                $cashSessionId,
                'expense',
                $amount,
                $currency,
                $methodId,
                'purchase',
                $purchaseId,
                "Pago de Compra #$purchaseId (" . $method['name'] . ")",
                $userId
            );

            return true;

        } catch (Exception $e) {
            error_log("Error en registerPurchasePayment: " . $e->getMessage());
            return false;
        }
    }

    public function registerTransaction($type, $amount, $description, $userId, $referenceType = 'manual', $referenceId = 0, $currency = 'USD')
    {
        try {
            $methodName = ($currency === 'USD') ? 'Efectivo USD' : 'Efectivo VES';
            $methodId = $this->getMethodIdByName($methodName);

            if (!$methodId) {
                $methods = $this->getPaymentMethods();
                if (!empty($methods))
                    $methodId = $methods[0]['id'];
                else
                    return false;
            }

            $sessionId = 0;

            $this->logTransaction(
                $sessionId,
                $type,
                $amount,
                $currency,
                $methodId,
                $referenceType,
                $referenceId,
                $description,
                $userId
            );

            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Error en registerTransaction: " . $e->getMessage());
            return false;
        }
    }

    public function getTransactionsByDate($startDate, $endDate)
    {
        $sql = "SELECT t.*, pm.name as method_name, u.name as user_name 
                FROM transactions t
                LEFT JOIN payment_methods pm ON t.payment_method_id = pm.id
                LEFT JOIN users u ON t.created_by = u.id
                WHERE DATE(t.created_at) BETWEEN ? AND ?
                ORDER BY t.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function logTransaction($sessionId, $type, $amount, $currency, $methodId, $refType, $refId, $desc, $userId)
    {
        $rate = 1;
        // Use Global Config Fallback
        global $config;
        if (isset($config) && method_exists($config, 'get')) {
            $rate = $config->get('exchange_rate');
        } elseif (isset($_ENV['EXCHANGE_RATE'])) {
            $rate = $_ENV['EXCHANGE_RATE'];
        }

        $exchangeRate = $rate;

        // Logic: If currency is VES, divide by rate to get USD ref. If USD, it is the amount.
        $amountUsdRef = ($currency === 'VES') ? ($amount / $exchangeRate) : $amount;

        $sql = "INSERT INTO transactions (cash_session_id, type, amount, currency, exchange_rate, amount_usd_ref, payment_method_id, reference_type, reference_id, description, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sessionId, $type, $amount, $currency, $exchangeRate, $amountUsdRef, $methodId, $refType, $refId, $desc, $userId]);
    }

    private function getMethodIdByName($name)
    {
        $stmt = $this->db->prepare("SELECT id FROM payment_methods WHERE name LIKE ?");
        $stmt->execute(["%$name%"]);
        return $stmt->fetchColumn();
    }

    public function getTransactionByReference($refType, $refId)
    {
        $stmt = $this->db->prepare("SELECT t.*, pm.name as method_name, pm.type as method_type
                                    FROM transactions t
                                    JOIN payment_methods pm ON t.payment_method_id = pm.id
                                    WHERE t.reference_type = ? AND t.reference_id = ?
                                    LIMIT 1");
        $stmt->execute([$refType, $refId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getPaymentMethods()
    {
        $stmt = $this->db->query("SELECT * FROM payment_methods WHERE is_active = 1");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
