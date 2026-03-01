<?php
session_start();
require_once '../templates/autoload.php';
require_once '../templates/header.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header("Location: login.php");
    exit;
}

// Obtener pedidos delivery con pago pendiente (status preparando o listo, pero sin transacciones de pago completas)
// Para simplificar esta versión, buscaremos órdenes con status != 'delivered' y status != 'cancelled' de tipo 'delivery'
$sql = "SELECT o.*, u.name as customer_name 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.consumption_type = 'delivery' 
        AND o.status IN ('preparing', 'ready')
        ORDER BY o.created_at DESC";
$stmt = $db->query($sql);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>🛵 Pedidos Delivery Pendientes</h2>
        <a href="tienda.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Volver</a>
    </div>

    <?php if (empty($orders)): ?>
        <div class="alert bg-secondary text-info text-center py-5 border-info animate-fade-in shadow">
            <h4 class="mb-0 text-info"><i class="fa fa-info-circle me-2"></i> No hay pedidos delivery pendientes de pago.
            </h4>
        </div>
    <?php else: ?>
        <div class="row animate-fade-in">
            <?php foreach ($orders as $o): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div
                            class="card-header bg-info bg-opacity-10 text-info d-flex justify-content-between align-items-center border-info">
                            <span class="fw-bold"><i class="fa fa-receipt me-1"></i> Orden #<?= $o['id'] ?></span>
                            <span class="badge bg-info text-white"><?= strtoupper($o['status']) ?></span>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title text-white mb-3"><?= htmlspecialchars($o['shipping_address']) ?></h5>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted small">Total a Cobrar:</span>
                                <span
                                    class="h4 mb-0 text-success fw-bold text-shadow">$<?= number_format($o['total_price'], 2) ?></span>
                            </div>
                            <p class="card-text small text-muted"><i class="fa fa-clock me-1"></i>
                                <?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></p>

                            <hr class="opacity-10">
                            <div class="d-grid gap-2">
                                <a href="checkout.php?order_id=<?= $o['id'] ?>" class="btn btn-primary fw-bold">
                                    <i class="fa fa-cash-register me-2"></i> Procesar Pago
                                </a>
                                <a href="ticket.php?id=<?= $o['id'] ?>" target="_blank" class="btn btn-outline-info btn-sm">
                                    <i class="fa fa-print me-1"></i> Ver Ticket
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../templates/footer.php'; ?>