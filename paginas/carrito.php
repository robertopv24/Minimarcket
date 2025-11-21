<?php
session_start();
// carrito.php - Página del carrito de compras
require_once '../templates/autoload.php';
require_once '../templates/header.php';
require_once '../templates/menu.php';




$userId = $_SESSION['user_id'] ?? null;

if ($userId) {
    // Procesar acciones del carrito
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
            case 'process':
                // Obtener los ítems del carrito para crear la orden
                $cartItems = $cartManager->getCart($userId);
                $items = [];
                foreach ($cartItems as $item) {
                    $items[] = [
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price_usd'] // O price_ves, según tu preferencia
                    ];

                }


                    // Actualizar el stock antes de crear la orden
                    foreach ($cartItems as $item) {
                        $product = $productManager->getProductById($item['product_id']);

                        if ($product) {
                            $newStock = $product['stock'] - $item['quantity'];
                            if ($newStock >= 0) {
                                $productManager->updateProductStock($item['product_id'], $newStock);

                            } else {
                                echo "<p class='alert alert-danger text-center'>Stock insuficiente para el producto: " . htmlspecialchars($item['name']) . "</p>";
                                return; // Detener el proceso si hay stock insuficiente
                            }
                        } else {
                            echo "<p class='alert alert-danger text-center'>Producto no encontrado: " . htmlspecialchars($item['name']) . "</p>";
                            return; // Detener el proceso si el producto no se encuentra
                        }
                    }


                // Obtener la dirección del usuario
                $user = $userManager->getUserById($userId);
                $shippingAddress = $user['address'] ?? 'Dirección no especificada';

                // Crear la orden
                $orderId = $orderManager->createOrder($userId, $items, $shippingAddress);

                if ($orderId) {
                    // Vaciar el carrito después de crear la orden
                    $cartManager->emptyCart($userId);
                    echo "<p class='alert alert-success text-center'>¡Compra confirmada! Número de orden: $orderId</p>";
                } else {
                    echo "<p class='alert alert-danger text-center'>Error al procesar la compra.</p>";
                }
                break;
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
                        <th>Stock Disponible</th>
                        <th>Precio Unitario (USD)</th>
                        <th>Precio Unitario (VES)</th>
                        <th>Subtotal (USD)</th>
                        <th>Subtotal (VES)</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItems as $item): ?>
                        <tr>
                            <?php
                            $productManager = new ProductManager($db);
                            $product = $productManager->getProductById($item['product_id']);
                            $stock = $product['stock'] ?? 0;
                            ?>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td>
                                <form method="post" action="">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?= htmlspecialchars($item['product_id']) ?>">
                                    <input type="number" name="quantity" value="<?= htmlspecialchars($item['quantity']) ?>" min="1" class="form-control" style="width: 80px;">
                                    <button type="submit" class="btn btn-sm btn-primary mt-2" <?= ($item['quantity'] >= $stock) ? 'disabled' : '' ?>>Actualizar</button>
                                </form>
                            </td>
                            <td><?= $stock ?></td>
                            <td>$<?= number_format($item['price_usd'], 2) ?></td>
                            <td><?= number_format($item['price_ves'], 2) ?> VES</td>
                            <td>$<?= number_format($item['price_usd'] * $item['quantity'], 2) ?></td>
                            <td><?= number_format($item['price_ves'] * $item['quantity'], 2) ?> VES</td>
                            <td>
                                <form method="post" action="">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?= htmlspecialchars($item['product_id']) ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                </table>

                <div class="text-end">
                    <h4>Total en USD: $<?= number_format($total['total_usd'], 2) ?></h4>
                    <h4>Total en VES: <?= number_format($total['total_ves'], 2) ?> VES</h4>
                </div>

                <div class="text-center mt-4">
                    <form method="post" action="">
                        <input type="hidden" name="action" value="process">
                        <button type="submit" class="btn btn-success">Confirmar Compra</button>
                    </form>
                    <a href="tienda.php" class="btn btn-primary">Seguir Comprando</a>
                    <form method="post" action="" class="d-inline">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn btn-warning">Vaciar Carrito</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

    <?php
} else {
    echo "<p class='alert alert-danger text-center'>Debes iniciar sesión para ver tu carrito.</p>";
}




require_once '../templates/footer.php';
?>
