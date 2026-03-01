<?php
session_start();
require_once '../templates/autoload.php';
require_once '../templates/header.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header("Location: login.php");
    exit;
}

// Obtener cuentas de mesa (dine_in) con pago pendiente
$sql = "SELECT o.*, u.name as customer_name 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.consumption_type = 'dine_in' 
        AND o.status NOT IN ('delivered', 'cancelled')
        AND NOT EXISTS (SELECT 1 FROM transactions t WHERE t.reference_type = 'order' AND t.reference_id = o.id AND t.type = 'income')
        ORDER BY o.created_at DESC";
$stmt = $db->query($sql);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>🍽️ Cuentas de Mesas (Comer Primero)</h2>
        <a href="tienda.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Volver</a>
    </div>

    <?php if (empty($orders)): ?>
        <div class="alert bg-secondary text-warning text-center py-5 border-warning animate-fade-in shadow">
            <h4 class="mb-0 text-warning"><i class="fa fa-utensils me-2"></i> No hay mesas con cuentas pendientes de pago.
            </h4>
        </div>
    <?php else: ?>
        <div class="row animate-fade-in">
            <?php foreach ($orders as $o): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div
                            class="card-header bg-warning bg-opacity-10 text-warning d-flex justify-content-between align-items-center border-warning">
                            <span class="fw-bold"><i class="fa fa-chair me-1"></i> Orden #<?= $o['id'] ?></span>
                            <span class="badge bg-warning text-dark"><?= strtoupper($o['status']) ?></span>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title text-white mb-3">
                                <?php
                                echo htmlspecialchars(preg_replace('/DELIVERY \([A-Z]\): /i', '', $o['shipping_address']));
                                ?>
                            </h5>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted small">Consumo:</span>
                                <span class="h4 mb-0 text-success fw-bold">$<?= number_format($o['total_price'], 2) ?></span>
                            </div>
                            <p class="card-text small text-muted"><i class="fa fa-clock me-1"></i>
                                <?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></p>

                            <hr class="opacity-10">
                            <div class="d-grid gap-2">
                                <a href="checkout.php?order_id=<?= $o['id'] ?>" class="btn btn-warning fw-bold text-dark">
                                    <i class="fa fa-file-invoice-dollar me-2"></i> Cerrar Cuenta
                                </a>
                                <a href="ticket.php?id=<?= $o['id'] ?>" target="_blank" class="btn btn-outline-warning btn-sm">
                                    <i class="fa fa-print me-1"></i> Ticket Pre-cuenta
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