<?php
session_start();
// carrito.php - Página del carrito de compras
require_once '../templates/autoload.php';
require_once '../templates/header.php';
require_once '../templates/menu.php';

$userId = $_SESSION['user_id'] ?? null;

if ($userId) {
    // 1. Validar si tiene caja abierta (Regla de Negocio)
    $hasOpenSession = $cashRegisterManager->hasOpenSession($userId);

    // Procesar acciones del carrito (Solo actualizar/eliminar/vaciar)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'update':
                $productId = $_POST['product_id'] ?? 0;
                $quantity = $_POST['quantity'] ?? 0;
                if ($productId && $quantity > 0) {
                    $result = $cartManager->updateCartQuantity($userId, $productId, $quantity);
                    if ($result !== true) {
                        echo "<p class='alert alert-danger text-center'>$result</p>";
                    }
                }
                break;
            case 'remove':
                $productId = $_POST['product_id'] ?? 0;
                if ($productId) {
                    $cartManager->removeFromCart($userId, $productId);
                }
                break;
            case 'clear':
                $cartManager->emptyCart($userId);
                break;
            // ELIMINADO: case 'process'. La orden ya no se crea aquí.
        }
    }

    $cartItems = $cartManager->getCart($userId);
    $total = $cartManager->calculateTotal($cartItems);

    ?>

    <div class="container mt-5">
        <h2 class="text-center">Carrito de Compras</h2>

        <?php if (empty($cartItems)): ?>
            <div class="alert alert-warning text-center">
                Tu carrito está vacío.
                <a href="tienda.php" class="btn btn-primary mt-2">Seguir Comprando</a>
            </div>
        <?php else: ?>
            <table class="table table-striped mt-4">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Stock</th>
                        <th>Precio (USD)</th>
                        <th>Precio (VES)</th>
                        <th>Subtotal (USD)</th>
                        <th>Subtotal (VES)</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItems as $item): ?>
                        <tr>
                            <?php
                            // Consultamos stock actual
                            $product = $productManager->getProductById($item['product_id']);
                            $stock = $product['stock'] ?? 0;
                            ?>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td>
                                <form method="post" action="" class="d-flex">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?= htmlspecialchars($item['product_id']) ?>">
                                    <input type="number" name="quantity" value="<?= htmlspecialchars($item['quantity']) ?>" min="1" class="form-control me-2" style="width: 80px;">
                                    <button type="submit" class="btn btn-sm btn-primary" <?= ($item['quantity'] >= $stock) ? 'disabled' : '' ?>>
                                        <i class="fa fa-sync"></i>
                                    </button>
                                </form>
                            </td>
                            <td><?= $stock ?></td>
                            <td>$<?= number_format($item['price_usd'], 2) ?></td>
                            <td><?= number_format($item['price_ves'], 2) ?> Bs</td>
                            <td>$<?= number_format($item['price_usd'] * $item['quantity'], 2) ?></td>
                            <td><?= number_format($item['price_ves'] * $item['quantity'], 2) ?> Bs</td>
                            <td>
                                <form method="post" action="">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?= htmlspecialchars($item['product_id']) ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="row mt-4">
                <div class="col-md-6 offset-md-6 text-end">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h4>Total USD: <span class="text-warning">$<?= number_format($total['total_usd'], 2) ?></span></h4>
                            <h5>Total VES: <span class="text-warning"><?= number_format($total['total_ves'], 2) ?> Bs</span></h5>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4 mb-5">
                <a href="tienda.php" class="btn btn-secondary me-2">Seguir Comprando</a>

                <form method="post" action="" class="d-inline me-2">
                    <input type="hidden" name="action" value="clear">
                    <button type="submit" class="btn btn-warning">Vaciar Carrito</button>
                </form>

                <?php if ($hasOpenSession): ?>
                    <a href="checkout.php" class="btn btn-success btn-lg">
                        <i class="fa fa-cash-register me-2"></i> Ir a Pagar
                    </a>
                <?php else: ?>
                    <a href="apertura_caja.php" class="btn btn-danger btn-lg">
                        <i class="fa fa-lock me-2"></i> Abrir Caja para Pagar
                    </a>
                <?php endif; ?>
            </div>

        <?php endif; ?>
    </div>

<?php
} else {
    echo "<div class='container mt-5'><p class='alert alert-danger text-center'>Debes iniciar sesión para ver tu carrito.</p></div>";
}

require_once '../templates/footer.php';
?>
