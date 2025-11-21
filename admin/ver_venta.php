<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_name']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../paginas/login.php");
    exit();
}

require_once '../templates/autoload.php';
require_once '../templates/header.php';
require_once '../templates/menu.php';

// Obtener el ID de la venta de la URL
$ventaId = $_GET['id'] ?? 0;

// Crear una instancia de OrderManager
$orderManager = new OrderManager($db);

// Obtener la información de la venta
$venta = $orderManager->getOrderById($ventaId);

if (!$venta) {
    echo '<div class="alert alert-danger">Venta no encontrada.</div>';
    exit();
}

// Obtener los productos de la venta con detalles del producto
$productos = $orderManager->getOrderItems($ventaId);

// Calcular el subtotal
$subtotal = 0;
foreach ($productos as $producto) {
    $subtotal += $producto['quantity'] * $producto['price_usd'];
}

?>

<div class="container mt-5">
    <h2>Detalles de la Venta #<?= htmlspecialchars($venta['id']) ?></h2>

    <div class="mb-3">
        <strong>Cliente:</strong> <?= htmlspecialchars($venta['customer_name']) ?><br>
        <strong>Fecha:</strong> <?= htmlspecialchars($venta['created_at']) ?><br>
        <strong>Estado:</strong> <?= htmlspecialchars($venta['status']) ?><br>
        <strong>Total:</strong> $<?= number_format($venta['total_price'], 2) ?><br>
        <strong>Dirección de Envío:</strong> <?= htmlspecialchars($venta['shipping_address']) ?><br>
        <strong>Método de Envío:</strong> <?= htmlspecialchars($venta['shipping_method']) ?><br>
        <strong>Número de Seguimiento:</strong> <?= htmlspecialchars($venta['tracking_number']) ?>
    </div>

    <h3>Productos</h3>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($productos)): ?>
                <?php foreach ($productos as $producto): ?>
                    <tr>
                        <td><?= htmlspecialchars($producto['name']) ?></td>
                        <td><?= htmlspecialchars($producto['quantity']) ?></td>
                        <td>$<?= number_format($producto['price_usd'], 2) ?></td>
                        <td>$<?= number_format($producto['quantity'] * $producto['price_usd'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="3" class="text-right"><strong>total:</strong></td>
                    <td>$<?= number_format($subtotal, 2) ?></td>
                </tr>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center">No hay productos en esta venta.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../templates/footer.php'; ?>
