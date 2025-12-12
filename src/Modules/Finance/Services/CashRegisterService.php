<?php

namespace Minimarcket\Modules\Finance\Services;

use Minimarcket\Core\Database;
use PDO;
use Exception;

class CashRegisterService
{
    private $db;
    private $vaultService;

    public function __construct(?PDO $db = null, ?VaultService $vaultService = null)
    {
        $this->db = $db ?? Database::getConnection();
        $this->vaultService = $vaultService ?? new VaultService($this->db);
    }

    public function hasOpenSession($userId)
    {
        $stmt = $this->db->prepare("SELECT id FROM cash_sessions WHERE user_id = ? AND status = 'open'");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }

    public function getStatus($userId)
    {
        $sessionId = $this->hasOpenSession($userId);
        if (!$sessionId) {
            return ['status' => 'closed'];
        }
        $stmt = $this->db->prepare("SELECT * FROM cash_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($session) {
            return $session;
        }
        return ['status' => 'closed'];
    }

    public function openRegister($userId, $initialUsd, $initialVes)
    {
        if ($this->hasOpenSession($userId)) {
            return ["status" => false, "message" => "Ya tienes una caja abierta."];
        }

        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO cash_sessions (user_id, opening_balance_usd, opening_balance_ves, status, opened_at) VALUES (?, ?, ?, 'open', NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $initialUsd, $initialVes]);
            $sessionId = $this->db->lastInsertId();

            if ($initialUsd > 0)
                $this->logInitialBalance($sessionId, $initialUsd, 'USD', $userId);
            if ($initialVes > 0)
                $this->logInitialBalance($sessionId, $initialVes, 'VES', $userId);

            $this->db->commit();
            return ["status" => true, "session_id" => $sessionId];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ["status" => false, "message" => "Error al abrir caja: " . $e->getMessage()];
        }
    }

    private function logInitialBalance($sessionId, $amount, $currency, $userId)
    {
        $methodName = "Efectivo " . $currency;
        $stmt = $this->db->prepare("SELECT id FROM payment_methods WHERE name = ?");
        $stmt->execute([$methodName]);
        $methodId = $stmt->fetchColumn();

        $rate = 1;
        // Use Global Config Fallback
        global $config;
        if (isset($config) && method_exists($config, 'get')) {
            $rate = $config->get('exchange_rate');
        } elseif (isset($_ENV['EXCHANGE_RATE'])) {
            $rate = $_ENV['EXCHANGE_RATE'];
        }

        $amountUsdRef = ($currency == 'USD') ? $amount : ($amount / $rate);

        if ($methodId) {
            $sql = "INSERT INTO transactions (cash_session_id, type, amount, currency, exchange_rate, amount_usd_ref, payment_method_id, reference_type, description, created_by, created_at)
                    VALUES (?, 'income', ?, ?, ?, ?, ?, 'adjustment', 'Fondo Inicial de Caja', ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$sessionId, $amount, $currency, $rate, $amountUsdRef, $methodId, $userId]);
        }
    }

    public function getSessionReport($userId)
    {
        $sessionId = $this->hasOpenSession($userId);
        if (!$sessionId)
            return null;

        $report = [
            'id' => $sessionId,
            'expected_usd' => 0,
            'expected_ves' => 0,
            'movements' => []
        ];

        $stmt = $this->db->prepare("SELECT * FROM cash_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        $report['expected_usd'] = floatval($session['opening_balance_usd']);
        $report['expected_ves'] = floatval($session['opening_balance_ves']);
        $report['opened_at'] = $session['opened_at'];

        $sql = "SELECT t.*, pm.type as method_type, pm.name as method_name
                        FROM transactions t
                        JOIN payment_methods pm ON t.payment_method_id = pm.id
                        WHERE t.cash_session_id = ?
                        ORDER BY t.created_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sessionId]);
        $trans = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($trans as $t) {
            // Logic: Debt payments by CASH DO count.
            // Adjustments NOT counted (handled in opening).
            if ($t['method_type'] === 'cash') {
                if ($t['reference_type'] !== 'adjustment') {
                    if ($t['currency'] === 'USD') {
                        $report['expected_usd'] += ($t['type'] === 'income' ? $t['amount'] : -$t['amount']);
                    } elseif ($t['currency'] === 'VES') {
                        $report['expected_ves'] += ($t['type'] === 'income' ? $t['amount'] : -$t['amount']);
                    }
                }
            }
            $report['movements'][] = $t;
        }

        return $report;
    }

    public function closeRegister($userId, $countedUsd, $countedVes)
    {
        $report = $this->getSessionReport($userId);
        if (!$report)
            return ["status" => false, "message" => "No hay sesión abierta."];

        try {
            $sql = "UPDATE cash_sessions SET
                    closing_balance_usd = ?,
                    closing_balance_ves = ?,
                    calculated_usd = ?,
                    calculated_ves = ?,
                    status = 'closed',
                    closed_at = NOW()
                    WHERE id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $countedUsd,
                $countedVes,
                $report['expected_usd'],
                $report['expected_ves'],
                $report['id']
            ]);

            $this->vaultService->transferFromSession($report['id'], $countedUsd, $countedVes, $userId);

            return ["status" => true];

        } catch (Exception $e) {
            return ["status" => false, "message" => "Error al cerrar: " . $e->getMessage()];
        }
    }

    public function searchSessions($query = '')
    {
        $sql = "SELECT cs.*, u.name as cashier_name,
                (cs.closing_balance_usd - cs.calculated_usd) as diff_usd,
                (cs.closing_balance_ves - cs.calculated_ves) as diff_ves
                FROM cash_sessions cs
                JOIN users u ON cs.user_id = u.id
                WHERE cs.status = 'closed'";

        $params = [];
        if (!empty($query)) {
            $sql .= " AND u.name LIKE ?";
            $params[] = "%$query%";
        }

        $sql .= " ORDER BY cs.closed_at DESC LIMIT 50";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
