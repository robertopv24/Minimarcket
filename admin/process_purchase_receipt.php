<?php
// process_purchase_receipt.php
require_once '../templates/autoload.php';

// session_start();
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    try {
        /** @var \Minimarcket\Core\Security\CsrfToken $csrf */
        $csrf = $container->get(\Minimarcket\Core\Security\CsrfToken::class);
        $csrf->validateToken();
    } catch (Exception $e) {
        die("Error de seguridad: " . $e->getMessage());
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $purchaseOrderId = $_POST['purchase_order_id'] ?? 0;
            $receiptDate = $_POST['receipt_date'] ?? '';

            if ($purchaseReceiptManager->createPurchaseReceipt($purchaseOrderId, $receiptDate)) {
                header('Location: compras.php');
                exit;
            } else {
                echo "Error al registrar la recepción de mercancía.";
            }
            break;
        default:
            echo "Acción no válida.";
            break;
    }
} else {
    echo "Acceso no permitido.";
}
?>