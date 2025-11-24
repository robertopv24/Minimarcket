<?php
// admin/compras.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../templates/autoload.php';

session_start();
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

// Obtener todas las órdenes
$purchaseOrders = $purchaseOrderManager->getAllPurchaseOrders();

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📦 Gestión de Compras</h2>
        <a href="add_purchase_order.php" class="btn btn-success">
            <i class="fa fa-plus-circle"></i> Nueva Compra
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body p-0">
            <?php if ($purchaseOrders): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Fecha Compra</th>
                                <th>Proveedor</th>
                                <th>Entrega Estimada</th> <th>Total (USD)</th>
                                <th>Pago (Tesorería)</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($purchaseOrders as $order): ?>
                                <?php
                                    $supplier = $supplierManager->getSupplierById($order['supplier_id']);

                                    // Info Financiera
                                    $stmt = $db->prepare("SELECT pm.name, t.amount, t.currency
                                                          FROM transactions t
                                                          JOIN payment_methods pm ON t.payment_method_id = pm.id
                                                          WHERE t.reference_type = 'purchase' AND t.reference_id = ?");
                                    $stmt->execute([$order['id']]);
                                    $pago = $stmt->fetch(PDO::FETCH_ASSOC);

                                    // Estilos
                                    $badgeClass = match($order['status']) {
                                        'received' => 'bg-success',
                                        'pending' => 'bg-warning text-dark',
                                        'canceled' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                ?>
                                <tr>
                                    <td class="fw-bold">#<?= $order['id'] ?></td>
                                    <td><?= date('d/m/Y', strtotime($order['order_date'])) ?></td>
                                    <td>
                                        <span class="fw-bold"><?= htmlspecialchars($supplier['name'] ?? 'Desconocido') ?></span>
                                    </td>

                                    <td>
                                        <i class="fa fa-calendar-day text-muted me-1"></i>
                                        <?= date('d/m/Y', strtotime($order['expected_delivery_date'])) ?>
                                    </td>

                                    <td class="fw-bold text-primary">
                                        $<?= number_format($order['total_amount'], 2) ?>
                                    </td>

                                    <td>
                                        <?php if ($pago): ?>
                                            <small class="d-block text-muted">Método:</small>
                                            <strong><?= $pago['name'] ?></strong>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">N/A</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="badge <?= $badgeClass ?> rounded-pill px-3">
                                            <?= strtoupper($order['status']) ?>
                                        </span>
                                    </td>

                                    <td class="text-end">
                                        <div class="btn-group">
                                            <?php if($order['status'] !== 'received'): ?>
                                                <a href="add_purchase_receipt.php?order_id=<?= $order['id'] ?>" class="btn btn-sm btn-success" title="Registrar Recepción de Mercancía">
                                                    <i class="fa fa-box-open"></i>
                                                </a>
                                            <?php endif; ?>

                                            <a href="edit_purchase_order.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-primary" title="Ver Detalles">
                                                <i class="fa fa-eye"></i>
                                            </a>

                                            <?php if($order['status'] !== 'received'): ?>
                                                <a href="delete_purchase_order.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Eliminar esta orden?');">
                                                    <i class="fa fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-5 text-center text-muted">
                    <i class="fa fa-box-open fa-3x mb-3"></i>
                    <p>No hay órdenes de compra registradas.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>
