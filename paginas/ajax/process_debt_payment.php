<?php
/**
 * AJAX Endpoint for POS Debt Payments
 * Receives: client_id, amount_usd, payment_method_id
 */
ob_start();
// session_start();
require_once '../../templates/autoload.php';

use Minimarcket\Core\Container;
use Minimarcket\Modules\Finance\Services\CashRegisterService;
use Minimarcket\Modules\Finance\Services\CreditService;

header('Content-Type: application/json');
ob_end_clean(); // Ensure clean output

// 1. Validar Sesión y Permisos
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $container = Container::getInstance();
    $cashRegisterService = $container->get(CashRegisterService::class);
    $creditService = $container->get(CreditService::class);

    $userId = $_SESSION['user_id'];

    // hasOpenSession returns bool based on service logic, but we might need the session ID for payDebt
    // Wait, payDebt requires $sessionId. 
    // CashRegisterService->hasOpenSession returns bool.
    // We need to get the Session ID. 
    // CashRegisterService->getStatus returns ['id' => ..., 'status' => 'open'] if open.

    $status = $cashRegisterService->getStatus($userId);

    if (!$status || $status['status'] !== 'open') {
        echo json_encode(['success' => false, 'message' => '⚠️ Debes tener una Caja abierta para recibir pagos.']);
        exit;
    }
    $sessionId = $status['id'];

    // 2. Validar Inputs
    $data = json_decode(file_get_contents('php://input'), true);

    $clientId = $data['client_id'] ?? null;
    $amountUsd = floatval($data['amount_usd'] ?? 0);
    $methodId = $data['payment_method_id'] ?? null;

    if (!$clientId || $amountUsd <= 0.01 || !$methodId) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos (Cliente, Monto o Método faltante).']);
        exit;
    }

    // 3. Obtener Deudas Pendientes (Más antiguas primero - FIFO)
    // Usamos el servicio
    $debts = $creditService->getPendingDebtsByClient($clientId);

    if (empty($debts)) {
        echo json_encode(['success' => false, 'message' => 'Este cliente no tiene deudas pendientes.']);
        exit;
    }

    // 4. Procesar Pagos (Distribuir el monto)
    $remainingToPay = $amountUsd;
    $payCount = 0;

    foreach ($debts as $debt) {
        if ($remainingToPay <= 0.001)
            break;

        $debtBalance = $debt['amount'] - $debt['paid_amount'];
        $amountForThisDebt = min($remainingToPay, $debtBalance);

        // Llamar a payDebt
        // payDebt($arId, $amountToPay, $paymentMethodId, $paymentRef, $paymentCurrency, $userId, $sessionId)
        // Signature check: payDebt($arId, $amountToPay, $paymentMethodId, $paymentRef = '', $paymentCurrency = 'USD', $userId = 1, $sessionId = 1)

        $result = $creditService->payDebt(
            $debt['id'],
            $amountForThisDebt,
            $methodId,
            '', // Ref
            'USD', // Assuming USD for now based on modal
            $userId,
            $sessionId
        );

        if ($result !== true) {
            throw new Exception("Error al procesar el pago de la deuda ID {$debt['id']}: " . $result);
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