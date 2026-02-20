<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../templates/autoload.php';

session_start();
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

$mensaje = '';
$ventaId = $_GET['id'] ?? 0;
$venta = $orderManager->getOrderById($ventaId);

if (!$venta) {
    echo '<div class="container mt-5"><div class="alert alert-danger">Venta no encontrada.</div><a href="ventas.php" class="btn btn-primary">Volver</a></div>';
    require_once '../templates/footer.php';
    exit;
}

// Procesar Formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevoEstado = $_POST['estado'];
    $tracking = $_POST['tracking_number'] ?? '';
    $estadoAnterior = $venta['status'];

    if ($orderManager->updateOrderStatus($ventaId, $nuevoEstado, $tracking)) {
        // SI PASA A CANCELADO: Devolver stock automáticamente
        if ($nuevoEstado === 'cancelled' && $estadoAnterior !== 'cancelled') {
            try {
                $orderManager->revertStockFromSale($ventaId);
                $mensaje = '<div class="alert alert-success">Estado actualizado y stock devuelto al inventario.</div>';
            } catch (Exception $e) {
                $mensaje = '<div class="alert alert-warning">Estado actualizado pero hubo un error con el stock: ' . $e->getMessage() . '</div>';
            }
        } else {
            $mensaje = '<div class="alert alert-success">Estado actualizado correctamente.</div>';
        }
        $venta = $orderManager->getOrderById($ventaId); // Refrescar datos
    } else {
        $mensaje = '<div class="alert alert-danger">Error al actualizar el estado.</div>';
    }
}

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Editar Venta #<?= htmlspecialchars($venta['id']) ?></h3>
                </div>
                <div class="card-body">
                    <?= $mensaje ?>

                    <div class="alert alert-secondary">
                        <strong>Cliente:</strong> <?= htmlspecialchars($venta['customer_name'] ?? 'Desconocido') ?><br>
                        <strong>Total:</strong> $<?= number_format($venta['total_price'], 2) ?>
                    </div>

                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="estado" class="form-label fw-bold">Estado del Pedido</label>
                            <select class="form-select form-select-lg" id="estado" name="estado">
                                <option value="pending" <?= ($venta['status'] === 'pending') ? 'selected' : '' ?>>Pendiente</option>
                                <option value="paid" <?= ($venta['status'] === 'paid') ? 'selected' : '' ?>>Pagado (Completado)</option>
                                <option value="shipped" <?= ($venta['status'] === 'shipped') ? 'selected' : '' ?>>Enviado</option>
                                <option value="delivered" <?= ($venta['status'] === 'delivered') ? 'selected' : '' ?>>Entregado</option>
                                <option value="cancelled" <?= ($venta['status'] === 'cancelled') ? 'selected' : '' ?>>Cancelado</option>
                            </select>
                            <div class="form-text text-success">
                                <i class="fa fa-check-circle"></i> Al cambiar a "Cancelado", el sistema devolverá automáticamente los insumos al inventario.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="tracking" class="form-label">Número de Seguimiento / Nota</label>
                            <input type="text" class="form-control" id="tracking" name="tracking_number" value="<?= htmlspecialchars($venta['tracking_number'] ?? '') ?>" placeholder="Ej: Nota interna o guía de envío">
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">Guardar Cambios</button>
                            <a href="ver_venta.php?id=<?= $venta['id'] ?>" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>
