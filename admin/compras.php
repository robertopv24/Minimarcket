<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Cargar el sistema completo
require_once '../templates/autoload.php';

session_start();
// 2. Seguridad: Solo admin
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// 3. Procesar formularios POST (Corrección de variable y método)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_purchase'])) {
    $items = json_decode($_POST['items'], true);
    if ($items) {
        // CORRECCIÓN: Usamos $purchaseOrderManager y los parámetros correctos de la Fase 2
        // Se usa la fecha de hoy y la tasa actual del sistema
        if ($purchaseOrderManager->createPurchaseOrder(
            $_POST['supplier_id'] ?? 0,
            date('Y-m-d'),
            date('Y-m-d', strtotime('+7 days')),
            $items,
            $GLOBALS['config']->get('exchange_rate')
        )) {
            $success_message = "Compra registrada con éxito.";
        } else {
            $error_message = "Error al registrar la compra.";
        }
    } else {
        $error_message = "Error: Ítems de compra no válidos.";
    }
}

// 4. Obtener todas las órdenes de compra
$purchases = $purchaseOrderManager->getAllPurchaseOrders() ?? [];

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5">
    <h2 class="text-center">Gestión de Compras (Historial)</h2>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="d-flex justify-content-end mb-3">
        <a href="add_purchase_order.php" class="btn btn-success">Registrar Nueva Compra</a>
    </div>

    <table class="table table-striped mt-4">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Proveedor</th>
                <th>Tasa Histórica</th>
                <th>Total (USD)</th>
                <th>Total Costo (VES)</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($purchases)): ?>
                <?php foreach ($purchases as $purchase): ?>
                    <?php
                        // FASE 2: Lógica de Precio Histórico
                        $historicalRate = (isset($purchase['exchange_rate']) && $purchase['exchange_rate'] > 0)
                                          ? $purchase['exchange_rate']
                                          : 1;

                        $totalUsd = $purchase['total_amount'];
                        $totalVes = $totalUsd * $historicalRate;

                        // Obtener nombre del proveedor
                        $supplier = $supplierManager->getSupplierById($purchase['supplier_id']);
                        $supplierName = $supplier ? $supplier['name'] : 'Desconocido';
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($purchase['id']); ?></td>
                        <td><?php echo htmlspecialchars($purchase['order_date'] ?? $purchase['created_at']); ?></td>
                        <td><?php echo htmlspecialchars($supplierName); ?></td>

                        <td><?php echo number_format($historicalRate, 2); ?> VES/USD</td>

                        <td>$<?php echo number_format($totalUsd, 2); ?></td>

                        <td class="fw-bold"><?php echo number_format($totalVes, 2); ?> VES</td>

                        <td>
                            <?php if($purchase['status'] == 'received'): ?>
                                <span class="badge bg-success">Recibido</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit_purchase_order.php?id=<?= $purchase['id'] ?>" class="btn btn-sm btn-primary">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">No hay compras registradas.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../templates/footer.php'; ?>
