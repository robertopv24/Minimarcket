<?php
// process_purchase_receipt.php
require_once '../templates/autoload.php';

session_start();
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    try {
        Csrf::validateToken();
    } catch (Exception $e) {
        die("Error de seguridad: " . $e->getMessage());
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $purchaseOrderId = $_POST['purchase_order_id'] ?? 0;
            $receiptDate = $_POST['receipt_date'] ?? '';

            try {
                $purchaseReceiptManager->createPurchaseReceipt($purchaseOrderId, $receiptDate);
                header('Location: compras.php?msg=received_success');
                exit;
            } catch (Exception $e) {
                echo '<div style="color: red; padding: 20px;">
                        <h3>Error al recibir mercancía</h3>
                        <p>' . $e->getMessage() . '</p>
                        <a href="add_purchase_receipt.php?order_id=' . $purchaseOrderId . '">Volver a intentar</a>
                      </div>';
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