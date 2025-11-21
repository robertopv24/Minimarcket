<?php
session_start();
require_once '../templates/autoload.php';
require_once '../templates/header.php';
require_once '../templates/menu.php';

$userId = $_SESSION['user_id'] ?? null;

if ($userId) {
    $cartItems = $cartManager->getCart($userId);
    $total = $cartManager->calculateTotal($cartItems);

    if (!empty($cartItems)) {
        ?>
        <div class="container mt-5">
            <h2>Checkout</h2>
            <div class="row">
                <div class="col-md-6">
                    <h3>Información de Envío</h3>
                    <form method="post" action="process_order.php">
                        <div class="mb-3">
                            <label for="shipping_address" class="form-label">Dirección de Envío</label>
                            <textarea name="shipping_address" id="shipping_address" class="form-control" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="shipping_city" class="form-label">Ciudad</label>
                            <input type="text" name="shipping_city" id="shipping_city" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="shipping_state" class="form-label">Estado</label>
                            <input type="text" name="shipping_state" id="shipping_state" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="shipping_zip" class="form-label">Código Postal</label>
                            <input type="text" name="shipping_zip" id="shipping_zip" class="form-control" required>
                        </div>
                </div>
                <div class="col-md-6">
                    <h3>Resumen del Pedido</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Subtotal USD</th>
                                <th>Subtotal VES</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['name']) ?></td>
                                    <td><?= htmlspecialchars($item['quantity']) ?></td>
                                    <td>$<?= number_format($item['price_usd'] * $item['quantity'], 2) ?></td>
                                    <td><?= number_format($item['price_ves'] * $item['quantity'], 2) ?> VES</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2"><strong>Total</strong></td>
                                <td><strong>$<?= number_format($total['total_usd'], 2) ?></strong></td>
                                <td><strong><?= number_format($total['total_ves'], 2) ?> VES</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                    <h3>Información de Pago</h3>
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Método de Pago</label>
                        <select name="payment_method" id="payment_method" class="form-select" required>
                            <option value="credit_card">Tarjeta de Crédito</option>
                            <option value="paypal">PayPal</option>
                            <option value="transfer">Transferencia Bancaria</option>
                            </select>
                    </div>
                    <button type="submit" class="btn btn-success">Confirmar Pedido</button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    } else {
        echo "<p class='alert alert-warning text-center'>Tu carrito está vacío.</p>";
    }
} else {
    echo "<p class='alert alert-danger text-center'>Debes iniciar sesión para realizar el checkout.</p>";
}

require_once '../templates/footer.php';
?>
