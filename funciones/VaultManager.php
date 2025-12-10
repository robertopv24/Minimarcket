<?php
class VaultManager
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Obtener saldo actual
    public function getBalance()
    {
        $stmt = $this->db->query("SELECT * FROM company_vault WHERE id = 1");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Mover dinero (Entrada o Salida)
    public function registerMovement($type, $origin, $amount, $currency, $description, $userId, $refId = null, $useTransaction = true)
    {
        try {
            if ($amount <= 0)
                return false;

            if ($useTransaction) {
                $this->db->beginTransaction();
            }

            // 1. Registrar el movimiento en el historial
            $sql = "INSERT INTO vault_movements (type, origin, amount, currency, description, reference_id, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$type, $origin, $amount, $currency, $description, $refId, $userId]);

            // 2. Actualizar el saldo de la Bóveda (company_vault)
            $field = ($currency == 'USD') ? 'balance_usd' : 'balance_ves';
            $operator = ($type == 'deposit') ? '+' : '-';

            // Validar saldo suficiente si es retiro
            if ($type == 'withdrawal') {
                $current = $this->getBalance();
                if ($current[$field] < $amount) {
                    throw new Exception("Fondos insuficientes en Caja Chica para realizar esta operación.");
                }
            }

            $updateSql = "UPDATE company_vault SET $field = $field $operator ? WHERE id = 1";
            $stmtUpd = $this->db->prepare($updateSql);
            $stmtUpd->execute([$amount]);

            if ($useTransaction) {
                $this->db->commit();
            }
            return true;

        } catch (Exception $e) {
            if ($useTransaction) {
                $this->db->rollBack();
            }
            error_log("Error Vault: " . $e->getMessage());
            return $e->getMessage(); // Retornamos el error
        }
    }

    // Transferir automáticamente desde un Cierre de Caja
    public function transferFromSession($sessionId, $amountUsd, $amountVes, $userId)
    {
        if ($amountUsd > 0) {
            $this->registerMovement('deposit', 'session_close', $amountUsd, 'USD', "Cierre de Caja #$sessionId", $userId, $sessionId);
        }
        if ($amountVes > 0) {
            $this->registerMovement('deposit', 'session_close', $amountVes, 'VES', "Cierre de Caja #$sessionId", $userId, $sessionId);
        }
    }

    // Obtener todos los movimientos (Historial completo)
    public function getAllMovements()
    {
        $stmt = $this->db->query("SELECT * FROM vault_movements ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>