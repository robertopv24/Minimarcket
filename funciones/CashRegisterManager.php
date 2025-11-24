<?php
class CashRegisterManager {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Verificar si el usuario (Admin o User) tiene caja abierta
    public function hasOpenSession($userId) {
        $stmt = $this->db->prepare("SELECT id FROM cash_sessions WHERE user_id = ? AND status = 'open'");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn(); // Retorna el ID si existe, o false
    }

    // Abrir Caja (Iniciar Turno)
    public function openRegister($userId, $initialUsd, $initialVes) {
        if ($this->hasOpenSession($userId)) {
            return ["status" => false, "message" => "Ya tienes una caja abierta."];
        }

        try {
            $this->db->beginTransaction();

            // 1. Crear sesión
            $sql = "INSERT INTO cash_sessions (user_id, opening_balance_usd, opening_balance_ves, status, opened_at) VALUES (?, ?, ?, 'open', NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $initialUsd, $initialVes]);
            $sessionId = $this->db->lastInsertId();

            // 2. Registrar el saldo inicial como transacción (Ajuste de entrada)
            if ($initialUsd > 0) $this->logInitialBalance($sessionId, $initialUsd, 'USD', $userId);
            if ($initialVes > 0) $this->logInitialBalance($sessionId, $initialVes, 'VES', $userId);

            $this->db->commit();
            return ["status" => true, "session_id" => $sessionId];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ["status" => false, "message" => "Error al abrir caja: " . $e->getMessage()];
        }
    }

    // Registrar transacción contable
    private function logInitialBalance($sessionId, $amount, $currency, $userId) {
        // Buscamos el ID del método "Efectivo" correspondiente
        $methodName = "Efectivo " . $currency;
        $stmt = $this->db->prepare("SELECT id FROM payment_methods WHERE name = ?");
        $stmt->execute([$methodName]);
        $methodId = $stmt->fetchColumn();

        if ($methodId) {
            $sql = "INSERT INTO transactions (cash_session_id, type, amount, currency, payment_method_id, reference_type, description, created_by, created_at)
                    VALUES (?, 'income', ?, ?, ?, 'adjustment', 'Fondo Inicial de Caja', ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$sessionId, $amount, $currency, $methodId, $userId]);
        }
    }

    // --- NUEVO: Lógica para Cierre de Caja ---

        // 1. Obtener reporte del turno actual (Cuánto debería haber)
        public function getSessionReport($userId) {
            $sessionId = $this->hasOpenSession($userId);
            if (!$sessionId) return null;

            // Sumar ingresos y egresos por moneda agrupados
            // Calculamos: Fondo Inicial + Ingresos - Egresos

            $report = [
                'id' => $sessionId,
                'expected_usd' => 0,
                'expected_ves' => 0,
                'movements' => []
            ];

            // Obtener detalles de la sesión (Fondo inicial)
            $stmt = $this->db->prepare("SELECT * FROM cash_sessions WHERE id = ?");
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);

            $report['expected_usd'] += $session['opening_balance_usd'];
            $report['expected_ves'] += $session['opening_balance_ves'];
            $report['opened_at'] = $session['opened_at'];

            // Sumar transacciones (Solo las que afectan EFECTIVO, o general según tu modelo)
            // NOTA: Si Zelle va directo a banco, no deberíamos sumarlo al "Cuadre de Efectivo" físico.
            // Aquí separaremos por tipo de método de pago.

            $sql = "SELECT t.*, pm.type as method_type, pm.name as method_name
                    FROM transactions t
                    JOIN payment_methods pm ON t.payment_method_id = pm.id
                    WHERE t.cash_session_id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$sessionId]);
            $trans = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($trans as $t) {
                // Solo sumamos al "Efectivo Esperado" si el método es tipo 'cash'
                if ($t['method_type'] === 'cash') {
                    if ($t['currency'] === 'USD') {
                        $report['expected_usd'] += ($t['type'] === 'income' ? $t['amount'] : -$t['amount']);
                    } elseif ($t['currency'] === 'VES') {
                        $report['expected_ves'] += ($t['type'] === 'income' ? $t['amount'] : -$t['amount']);
                    }
                }
                // Guardamos el movimiento para el historial
                $report['movements'][] = $t;
            }

            return $report;
        }

        // 2. Cerrar la Caja (Guardar lo que contó el cajero)
        public function closeRegister($userId, $countedUsd, $countedVes) {
            $report = $this->getSessionReport($userId);
            if (!$report) return ["status" => false, "message" => "No hay sesión abierta."];

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

                // Usamos $GLOBALS para acceder al VaultManager creado en autoload
                            if (isset($GLOBALS['vaultManager'])) {
                                $GLOBALS['vaultManager']->transferFromSession($report['id'], $countedUsd, $countedVes, $userId);
                            }

                return ["status" => true];

            } catch (Exception $e) {
                return ["status" => false, "message" => "Error al cerrar: " . $e->getMessage()];
            }
        }

        // --- NUEVO: Obtener detalle completo de una sesión cerrada por ID ---
            public function getSessionDetailsById($sessionId) {
                // 1. Datos básicos de la sesión
                $stmt = $this->db->prepare("SELECT cs.*, u.name as cashier_name
                                            FROM cash_sessions cs
                                            JOIN users u ON cs.user_id = u.id
                                            WHERE cs.id = ?");
                $stmt->execute([$sessionId]);
                $session = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$session) return null;

                // 2. Agrupar totales por Método de Pago
                // Esto nos dirá: Zelle=$50, Pago Móvil=200Bs, Efectivo=$20...
                $sql = "SELECT pm.name as method_name, pm.currency, pm.type,
                        SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE -t.amount END) as total
                        FROM transactions t
                        JOIN payment_methods pm ON t.payment_method_id = pm.id
                        WHERE t.cash_session_id = ?
                        GROUP BY pm.id, pm.name, pm.currency, pm.type";

                $stmt = $this->db->prepare($sql);
                $stmt->execute([$sessionId]);
                $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

                return [
                    'info' => $session,
                    'methods' => $methods
                ];
            }



}
?>
