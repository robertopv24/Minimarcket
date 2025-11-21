<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../templates/autoload.php';

session_start();
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}



// Procesar creación de compra
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_purchase'])) {
    $items = json_decode($_POST['items'], true);
    if ($items) {
        if ($purchaseManager->createPurchase($items)) {
            $success_message = "Compra registrada con éxito.";
        } else {
            $error_message = "Error al registrar la compra.";
        }
    } else {
        $error_message = "Error: Ítems de compra no válidos.";
    }
}

// Obtener compras y productos
$purchases = $purchaseManager->getPurchasesByYear(date('Y')) ?? [];
$products = $productManager->getAllProducts();

require_once '../templates/header.php';
require_once '../templates/menu.php';


?>

<div class="container mt-5">
    <h2 class="text-center">Gestión de Compras</h2>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>



    <h3 class="mt-4">Historial de Compras</h3>
    <table class="table table-striped mt-4">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Tasa de Cambio</th>
                <th>Total (USD)</th>
                <th>Total (VES)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($purchases)): ?>
                <?php foreach ($purchases as $purchase): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($purchase['id']); ?></td>
                        <td><?php echo htmlspecialchars($purchase['created_at']); ?></td>
                        <td><?php echo htmlspecialchars($newExchangeRate, 2); ?></td>
                        <td>$<?php echo htmlspecialchars($purchase['total_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($purchase['total_amount'] * $newExchangeRate, 2); ?> VES</td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">No hay compras registradas.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../templates/footer.php'; ?>
