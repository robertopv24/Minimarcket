<?php
/**
 * AJAX Endpoint for POS Debt Payments
 * Receives: client_id, amount_usd, payment_method_id
 */
ob_start();
session_start();
require_once '../../templates/autoload.php';
require_once '../../funciones/debug_logger.php';

header('Content-Type: application/json');
ob_end_clean(); // Ensure clean output

// 1. Validar Sesión y Permisos
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];
$sessionId = $cashRegisterManager->hasOpenSession($userId);

if (!$sessionId) {
    echo json_encode(['success' => false, 'message' => '⚠️ Debes tener una Cajan abierta para recibir pagos.']);
    exit;
}

// 2. Validar Inputs
$data = json_decode(file_get_contents('php://input'), true);

$clientId = $data['client_id'] ?? null;
$amountUsd = floatval($data['amount_usd'] ?? 0);
$methodId = $data['payment_method_id'] ?? null;

if (!$clientId || $amountUsd <= 0.01 || !$methodId) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos (Cliente, Monto o Método faltante).']);
    exit;
}

try {
    // 3. Obtener Deudas Pendientes (Más antiguas primero - FIFO)
    $stmt = $db->prepare("SELECT * FROM accounts_receivable 
                          WHERE client_id = ? AND status != 'paid' 
                          ORDER BY created_at ASC");
    $stmt->execute([$clientId]);
    $debts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($debts)) {
        echo json_encode(['success' => false, 'message' => 'Este cliente no tiene deudas pendientes.']);
        exit;
    }

    // 4. Procesar Pagos (Distribuir el monto)
    $remainingToPay = $amountUsd;
    $payCount = 0;

    // Iniciar Transacción global opcional? No, payDebt maneja sus propias transacciones.
    // Pero idealmente todo debería ser atómico.
    // CreditManager::payDebt inicia transacción por cada pago.
    // Si falla uno, quedaríamos a medias.
    // IMPROVEMENT: Podríamos envolver todo en DB transaction si CreditManager soportara nested transactions o no-commit mode.
    // Por ahora, lo haremos iterativo.

    foreach ($debts as $debt) {
        if ($remainingToPay <= 0.001)
            break;

        $debtBalance = $debt['amount'] - $debt['paid_amount'];
        $amountForThisDebt = min($remainingToPay, $debtBalance);

        // Llamar a payDebt
        // payDebt($arId, $amountToPay, $method, $transactionRef, $paymentMethodId, $sessionId)
        // Nota: method='cash' para activar la lógica de caja en CreditManager
        $result = $creditManager->payDebt(
            $debt['id'],
            $amountForThisDebt,
            'cash',
            null,
            $methodId,
            $sessionId
        );

        if (!$result) {
            // Loguear error pero tratar de continuar o parar?
            // Si falla uno, mejor parar.
            throw new Exception("Error al procesar el pago de la deuda ID {$debt['id']}");
        }

        $remainingToPay -= $amountForThisDebt;
        $payCount++;
    }

    $totalPaid = $amountUsd - $remainingToPay;

    echo json_encode([
        'success' => true,
        'message' => "Pago procesado exitosamente. Abonado: $$totalPaid USD ($payCount facturas afectadas)."
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>