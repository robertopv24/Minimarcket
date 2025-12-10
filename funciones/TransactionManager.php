<?php
class TransactionManager
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // ---------------------------------------------------------
    // 1. GESTIÓN DE VENTAS (POS)
    // ---------------------------------------------------------

    /**
     * Registrar Ingresos de una Venta (Lo que el cliente paga)
     */
    public function processOrderPayments($orderId, $payments, $userId, $sessionId)
    {
        try {
            foreach ($payments as $payment) {
                if ($payment['amount'] > 0) {
                    $this->logTransaction(
                        $sessionId,
                        'income',               // Tipo: Ingreso
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

    /**
     * Registrar Vuelto de una Venta (Salida de dinero)
     */
    public function registerOrderChange($orderId, $amountNominal, $currency, $methodId, $userId, $sessionId)
    {
        try {
            $this->logTransaction(
                $sessionId,
                'expense',              // Tipo: Egreso/Gasto
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

    // ---------------------------------------------------------
    // 2. GESTIÓN DE COMPRAS (PROVEEDORES)
    // ---------------------------------------------------------

    /**
     * Registrar Pago a Proveedor (Gasto Administrativo)
     */
    public function registerPurchasePayment($purchaseId, $amount, $currency, $methodId, $userId)
    {
        try {
            // 1. Validar Método de Pago
            $stmt = $this->db->prepare("SELECT type, name FROM payment_methods WHERE id = ?");
            $stmt->execute([$methodId]);
            $method = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$method)
                throw new Exception("Método de pago no válido.");

            // Las compras no afectan la caja del turno (session_id = 0)
            $cashSessionId = 0;

            // 2. Si es efectivo, descontar de la Bóveda Central (Caja Chica)
            if ($method['type'] === 'cash') {
                if (!isset($GLOBALS['vaultManager'])) {
                    throw new Exception("Error crítico: VaultManager no cargado.");
                }

                $vault = $GLOBALS['vaultManager'];
                $descVault = "Pago a Proveedor - Compra #$purchaseId";

                $res = $vault->registerMovement(
                    'withdrawal',       // Tipo: Retiro
                    'supplier_payment', // Origen: Pago Proveedor
                    $amount,
                    $currency,
                    $descVault,
                    $userId,
                    $purchaseId
                );

                if ($res !== true)
                    throw new Exception("Fondos insuficientes en Bóveda: " . $res);
            }

            // 3. Registrar la transacción contable
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

    /**
     * Registrar Transacción Manual (Ingreso/Egreso genérico)
     */
    public function registerTransaction($type, $amount, $description, $userId, $referenceType = 'manual', $referenceId = 0, $currency = 'USD')
    {
        try {
            // Asumimos Metodo "Efectivo USD" o "Efectivo VES" por defecto para manuales si no se especifica
            // Para simplificar, buscamos 'Efectivo USD' si currency es USD
            $methodName = ($currency === 'USD') ? 'Efectivo USD' : 'Efectivo VES';
            $methodId = $this->getMethodIdByName($methodName);

            if (!$methodId) {
                // Fallback a cualquier metodo activo si no encuentra el especifico
                $methods = $this->getPaymentMethods();
                if (!empty($methods))
                    $methodId = $methods[0]['id'];
                else
                    return false;
            }

            // Las manuales podrían no estar ligadas a sesión de caja si son administrativas,
            // pero si son de caja, deberían. Por ahora session_id = 0 para administrativas/generales.
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

    // ---------------------------------------------------------
    // 3. NÚCLEO (PRIVADO)
    // ---------------------------------------------------------

    /**
     * Función centralizada para insertar en la tabla `transactions`
     * Calcula automáticamente la tasa de cambio y el valor referencia en USD.
     */
    private function logTransaction($sessionId, $type, $amount, $currency, $methodId, $refType, $refId, $desc, $userId)
    {
        // Obtener tasa actual del sistema
        $rate = isset($GLOBALS['config']) ? $GLOBALS['config']->get('exchange_rate') : 1;

        // Calcular valor en USD para reportes unificados
        $amountUsdRef = ($currency == 'USD') ? $amount : ($amount / $rate);

        $sql = "INSERT INTO transactions (
                    cash_session_id, type, amount, currency, exchange_rate, amount_usd_ref,
                    payment_method_id, reference_type, reference_id, description, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $sessionId,
            $type,
            $amount,
            $currency,
            $rate,
            $amountUsdRef,
            $methodId,
            $refType,
            $refId,
            $desc,
            $userId
        ]);
    }

    // ---------------------------------------------------------
    // 4. UTILIDADES
    // ---------------------------------------------------------

    public function getPaymentMethods()
    {
        $stmt = $this->db->query("SELECT * FROM payment_methods WHERE is_active = 1");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMethodIdByName($name)
    {
        $stmt = $this->db->prepare("SELECT id FROM payment_methods WHERE name = ?");
        $stmt->execute([$name]);
        return $stmt->fetchColumn();
    }
}
?>