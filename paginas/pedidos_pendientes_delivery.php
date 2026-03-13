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
        AND o.status IN ('preparing', 'ready', 'paid')
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
                            <?php 
                            // Priorizar el nombre del cliente guardado en la nota (nombre real) sobre el nombre de usuario
                            $displayName = !empty($o['customer_note']) ? $o['customer_note'] : $o['customer_name'];
                            ?>
                            <h5 class="card-title text-white mb-2"><?= htmlspecialchars($displayName) ?></h5>
                            <p class="text-info small mb-3"><i class="fa fa-map-marker-alt me-1"></i> <?= htmlspecialchars($o['shipping_address']) ?></p>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted small">Total Orden:</span>
                                <span class="fw-bold">$<?= number_format($o['total_price'], 2) ?></span>
                            </div>
                            <?php 
                            $paid = $transactionManager->getTotalPaidByOrder($o['id']);
                            $remaining = max(0, $o['total_price'] - $paid);
                            ?>
                            <?php if ($paid > 0): ?>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted small">Ya Pagado (Abonos):</span>
                                    <span class="text-info">$<?= number_format($paid, 2) ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted small fw-bold">Saldo Pendiente:</span>
                                <span class="h4 mb-0 text-success fw-bold text-shadow">$<?= number_format($remaining, 2) ?></span>
                            </div>
                            <p class="card-text small text-muted"><i class="fa fa-clock me-1"></i>
                                <?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></p>

                            <hr class="opacity-10">
                            <div class="d-grid gap-2">
                                <?php if ($remaining > 0): ?>
                                    <a href="checkout.php?order_id=<?= $o['id'] ?>" class="btn btn-primary fw-bold">
                                        <i class="fa fa-cash-register me-2"></i> Procesar Pago
                                    </a>
                                <?php else: ?>
                                    <button onclick="orderAction(<?= $o['id'] ?>, 'deliver')" class="btn btn-success fw-bold">
                                        <i class="fa fa-check-double me-2"></i> Finalizar Entrega
                                    </button>
                                <?php endif; ?>
                                <a href="ticket.php?id=<?= $o['id'] ?>" target="_blank" class="btn btn-outline-info btn-sm">
                                    <i class="fa fa-print me-1"></i> Ver Ticket
                                </a>
                                <?php 
                                $isModifiable = $orderManager->isOrderModifiable($o['id']);
                                $isCancellable = $orderManager->isOrderCancellable($o['id']);
                                ?>
                                <?php if ($isModifiable || $isCancellable): ?>
                                    <div class="row g-2 mt-1">
                                        <?php if ($isModifiable): ?>
                                            <div class="col-6">
                                                <button onclick="orderAction(<?= $o['id'] ?>, 'modify')" class="btn btn-outline-primary btn-sm w-100">
                                                    <i class="fa fa-edit"></i> Editar
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($isCancellable): ?>
                                            <div class="<?= $isModifiable ? 'col-6' : 'col-12' ?>">
                                                <button onclick="orderAction(<?= $o['id'] ?>, 'cancel')" class="btn btn-outline-danger btn-sm w-100">
                                                    <i class="fa fa-times"></i> Cancelar
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Script de Acciones de Orden -->
<script>
function orderAction(orderId, action) {
    let actionText = 'procesar';
    let actionColor = '#3085d6';
    let confirmButtonText = 'Confirmar';
    let text = '¿Deseas realizar esta acción?';

    if (action === 'modify') {
        actionText = 'modificar';
        text = 'La orden actual se cancelará y los productos se cargarán en tu carrito para editarlos.';
        confirmButtonText = 'Sí, modificar';
    } else if (action === 'cancel') {
        actionText = 'cancelar';
        actionColor = '#d33';
        text = 'Esta acción revertirá el stock y cancelará la orden permanentemente.';
        confirmButtonText = 'Sí, cancelar';
    } else if (action === 'deliver') {
        actionText = 'finalizar entrega';
        actionColor = '#198754';
        text = '¿Confirmas que el pedido ha sido entregado al cliente?';
        confirmButtonText = 'Sí, Entregado';
    }
    
    Swal.fire({
        title: `¿Estás seguro de ${actionText} la orden #${orderId}?`,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: actionColor,
        cancelButtonColor: '#aaa',
        confirmButtonText: confirmButtonText,
        cancelButtonText: 'No, volver'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Procesando...',
                didOpen: () => { Swal.showLoading() },
                allowOutsideClick: false
            });

            $.ajax({
                url: '../ajax/order_actions.php',
                method: 'POST',
                data: { order_id: orderId, action: action },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('¡Éxito!', response.message, 'success').then(() => {
                            if (action === 'modify') {
                                window.location.href = 'tienda.php?order_id=' + orderId;
                            } else {
                                location.reload();
                            }
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'No se pudo procesar la solicitud.', 'error');
                }
            });
        }
    });
}
</script>

<?php require_once '../templates/footer.php'; ?>