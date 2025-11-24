<?php
class TransactionManager {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Procesar Pago Completo (Múltiples métodos + Vuelto)
    public function processOrderPayments($orderId, $payments, $totalOrderAmount, $userId, $sessionId) {
        try {
            // Nota: La transacción de DB ya debería estar iniciada por quien llama a esta función

            $totalPaidUsd = 0;

            // 1. Registrar Ingresos (Lo que el cliente paga)
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

                    // Convertir a USD para calcular el total pagado
                    $amountInUsd = ($payment['currency'] == 'USD')
                                   ? $payment['amount']
                                   : ($payment['amount'] / $payment['rate']);

                    $totalPaidUsd += $amountInUsd;
                }
            }

            // 2. Calcular y Registrar Vuelto (Si pagó de más)
            // Margen de error pequeño por decimales (0.01)
            if ($totalPaidUsd > ($totalOrderAmount + 0.01)) {
                $changeUsd = $totalPaidUsd - $totalOrderAmount;

                // Buscamos el ID de "Efectivo USD" para registrar la salida del vuelto
                // ASUMIMOS que el vuelto siempre es en Divisas Efectivo por defecto
                // Si quieres dar vuelto en Bolívares, habría que agregarlo en el formulario
                $cashMethodId = $this->getMethodIdByName('Efectivo USD');

                if ($cashMethodId) {
                    $this->logTransaction(
                        $sessionId,
                        'expense',
                        $changeUsd,
                        'USD',
                        $cashMethodId,
                        'order',
                        $orderId,
                        'Vuelto Venta #' . $orderId,
                        $userId
                    );
                }
            }

            return true;

          } catch (Exception $e) {
              // ESTO TE MOSTRARÁ EL ERROR EN PANTALLA
              die("ERROR SQL DETALLADO: " . $e->getMessage());
              return false;
          }
    }

    private function logTransaction($sessionId, $type, $amount, $currency, $methodId, $refType, $refId, $desc, $userId) {
        // Tasa usada para esta transacción específica
        // Si es USD, tasa 1. Si es VES, usamos la del sistema actual.
        // Para mayor precisión, podrías pasar la tasa exacta del momento.
        $rate = ($currency == 'VES') ? $GLOBALS['config']->get('exchange_rate') : 1;
        $amountUsdRef = ($currency == 'USD') ? $amount : ($amount / $rate);

        $sql = "INSERT INTO transactions (cash_session_id, type, amount, currency, exchange_rate, amount_usd_ref, payment_method_id, reference_type, reference_id, description, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sessionId, $type, $amount, $currency, $rate, $amountUsdRef, $methodId, $refType, $refId, $desc, $userId]);
    }

    public function getPaymentMethods() {
        $stmt = $this->db->query("SELECT * FROM payment_methods WHERE is_active = 1");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getMethodIdByName($name) {
        $stmt = $this->db->prepare("SELECT id FROM payment_methods WHERE name = ?");
        $stmt->execute([$name]);
        return $stmt->fetchColumn();
    }

    // --- NUEVO: Registrar Gasto (Compra a Proveedor) ---
    public function registerPurchasePayment($purchaseId, $amount, $currency, $methodId, $userId) {
            try {
                // 1. Obtener información del método de pago
                $stmt = $this->db->prepare("SELECT type, name FROM payment_methods WHERE id = ?");
                $stmt->execute([$methodId]);
                $method = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$method) {
                    throw new Exception("Método de pago no válido.");
                }

                $cashSessionId = 0; // Por defecto 0, ya que es un gasto administrativo, no de caja de turno

                // 2. Lógica de Descuento de Dinero
                if ($method['type'] === 'cash') {
                    // CASO 1: PAGO EN EFECTIVO
                    // El dinero debe salir de la CAJA CHICA (Bóveda Central), no de la caja del cajero.

                    // Verificamos que el VaultManager esté disponible
                    if (!isset($GLOBALS['vaultManager'])) {
                        throw new Exception("Error del sistema: Gestor de Bóveda no cargado.");
                    }

                    $vault = $GLOBALS['vaultManager'];
                    $description = "Pago a Proveedor - Compra #$purchaseId";

                    // Registramos el retiro en la Bóveda
                    // Parámetros: (Tipo, Origen, Monto, Moneda, Desc, User, RefID)
                    $res = $vault->registerMovement(
                        'withdrawal',
                        'supplier_payment',
                        $amount,
                        $currency,
                        $description,
                        $userId,
                        $purchaseId
                    );

                    // Si la bóveda devuelve error (ej: saldo insuficiente), detenemos todo.
                    if ($res !== true) {
                        throw new Exception("Error en Caja Chica: " . $res);
                    }

                } else {
                    // CASO 2: PAGO DIGITAL (Zelle, Banco, etc.)
                    // Aquí no descontamos de la bóveda física, pero registramos la transacción
                    // para que quede constancia contable del gasto.
                }

                // 3. Registrar la transacción en el Libro Diario (Tabla transactions)
                // Calculamos la referencia en dólares para reportes unificados
                $rate = ($currency == 'VES') ? $GLOBALS['config']->get('exchange_rate') : 1;
                $amountUsdRef = ($currency == 'USD') ? $amount : ($amount / $rate);

                $sql = "INSERT INTO transactions (
                            cash_session_id, type, amount, currency, exchange_rate, amount_usd_ref,
                            payment_method_id, reference_type, reference_id, description, created_by, created_at
                        ) VALUES (?, 'expense', ?, ?, ?, ?, ?, 'purchase', ?, ?, ?, NOW())";

                $stmt = $this->db->prepare($sql);
                $desc = "Pago de Compra #$purchaseId (" . $method['name'] . ")";

                return $stmt->execute([
                    $cashSessionId,
                    $amount,
                    $currency,
                    $rate,
                    $amountUsdRef,
                    $methodId,
                    $purchaseId,
                    $desc,
                    $userId
                ]);

            } catch (Exception $e) {
                // Registramos el error en el log de PHP y devolvemos false
                error_log("Error registrando pago de compra: " . $e->getMessage());
                // Opcional: Si quieres ver el error en pantalla durante pruebas, usa die($e->getMessage());
                return false;
            }
        }






}
?>
