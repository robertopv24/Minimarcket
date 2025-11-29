<?php
// paginas/despacho.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../templates/autoload.php';
session_start();

// PERMISOS: Cualquier usuario logueado puede ver esto (Cajero o Admin)
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

// PROCESAR CAMBIO DE ESTADO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = $_POST['order_id'];
    $newStatus = $_POST['status'];

    // Ejecutamos update
    $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $orderId]);

    header("Location: despacho.php");
    exit;
}

// OBTENER PEDIDOS ACTIVOS (No entregados ni cancelados)
// Mostramos: paid (Cola), preparing (Cocina), ready (Para entregar)
$sql = "SELECT o.*, u.name as cliente
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.status IN ('paid', 'preparing', 'ready')
        ORDER BY o.id ASC";
$stmt = $db->query($sql);
$ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>🚀 Centro de Despacho (Caja)</h2>
        <a href="kds_tv.php" target="_blank" class="btn btn-dark">
            <i class="fa fa-desktop me-2"></i> Abrir Pantalla Cocina (TV)
        </a>
    </div>

    <div class="row">
        <?php foreach ($ordenes as $o):
            // Calcular items resumen
            $items = $orderManager->getOrderItems($o['id']);
            $count = 0;
            $txtItems = "";
            foreach($items as $i) {
                $txtItems .= $i['quantity']."x ".$i['name'].", ";
                $count++;
            }

            // Estilos según estado
            $borderClass = 'border-secondary';
            $bgClass = 'bg-white';
            $statusLabel = 'En Cola';

            if ($o['status'] == 'preparing') {
                $borderClass = 'border-warning';
                $bgClass = 'bg-light';
                $statusLabel = '🔥 Cocinando';
            } elseif ($o['status'] == 'ready') {
                $borderClass = 'border-success';
                $bgClass = 'bg-success text-white'; // Destacar mucho los listos
                $statusLabel = '✅ LISTO PARA ENTREGAR';
            }
        ?>

        <div class="col-md-4 mb-3">
            <div class="card shadow-sm h-100 <?= $borderClass ?>" style="border-width: 2px;">
                <div class="card-header d-flex justify-content-between align-items-center <?= $o['status']=='ready'?'bg-success text-white':'' ?>">
                    <h5 class="m-0">Orden #<?= $o['id'] ?></h5>
                    <span class="badge bg-dark"><?= date('h:i A', strtotime($o['created_at'])) ?></span>
                </div>

                <div class="card-body">
                    <h6 class="card-title fw-bold"><?= htmlspecialchars($o['cliente']) ?></h6>
                    <p class="card-text small text-muted mb-2">
                        <?= mb_strimwidth($txtItems, 0, 60, "...") ?>
                    </p>
                    <div class="mb-3 text-center">
                        <span class="badge rounded-pill fs-6 px-3 py-2
                            <?= $o['status']=='ready'?'bg-white text-success':'bg-secondary' ?>">
                            <?= $statusLabel ?>
                        </span>
                    </div>

                    <div class="d-grid gap-2">

                        <?php if ($o['status'] == 'paid'): ?>
                            <form method="POST">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <input type="hidden" name="status" value="preparing">
                                <button class="btn btn-warning w-100">
                                    <i class="fa fa-fire"></i> Iniciar Preparación
                                </button>
                            </form>

                        <?php elseif ($o['status'] == 'preparing'): ?>
                            <form method="POST">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <input type="hidden" name="status" value="ready">
                                <button class="btn btn-success w-100">
                                    <i class="fa fa-check-circle"></i> ¡Comida Lista!
                                </button>
                            </form>

                        <?php elseif ($o['status'] == 'ready'): ?>
                            <form method="POST">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <input type="hidden" name="status" value="delivered">
                                <button class="btn btn-dark w-100 py-2">
                                    <i class="fa fa-hand-holding-heart"></i> Entregar a Cliente
                                </button>
                            </form>
                        <?php endif; ?>

                        <a href="ticket.php?id=<?= $o['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="fa fa-print"></i> Ticket
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if(empty($ordenes)): ?>
            <div class="col-12 text-center py-5 text-muted">
                <i class="fa fa-mug-hot fa-3x mb-3"></i>
                <h4>No hay pedidos activos</h4>
                <p>Esperando clientes...</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Auto-recargar cada 30 seg para ver nuevos pedidos
    setTimeout(() => location.reload(), 30000);
</script>

<?php require_once '../templates/footer.php'; ?>
