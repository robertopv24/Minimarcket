<?php
session_start();

require_once '../templates/autoload.php';

// --- LÓGICA DE CONTROL DE CAJA ---
$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user_role'] ?? null;
$hasOpenSession = false;
$cajaAlert = "";

if ($userId) {
    $hasOpenSession = $cashRegisterManager->hasOpenSession($userId);

    // REGLA 1: Si es Cajero y NO tiene caja, ¡FUERA! A abrir caja.
    if ($userRole === 'user' && !$hasOpenSession) {
        header("Location: apertura_caja.php");
        exit;
    }

    // REGLA 2: Si es Admin y NO tiene caja, advertencia.
    if ($userRole === 'admin' && !$hasOpenSession) {
        $cajaAlert = '<div class="alert alert-warning text-center m-3">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>Atención Admin:</strong> No tienes una caja abierta.
                        <a href="apertura_caja.php" class="btn btn-sm btn-dark ms-2">Abrir Caja para Vender</a>
                      </div>';
    }
}
// ----------------------------------

// Procesar "Agregar al Carrito"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (!$userId) {
        echo "<script>alert('Debes iniciar sesión'); window.location='login.php';</script>";
        exit;
    }

    // REGLA 3: Nadie puede vender sin caja abierta (ni siquiera el admin si intenta agregar)
    if (!$hasOpenSession) {
        echo "<script>alert('⚠️ ERROR: Debes abrir una caja (Turno) antes de realizar ventas.'); window.location='apertura_caja.php';</script>";
        exit;
    }

    $productId = $_POST['product_id'];
    $quantity = $_POST['quantity'];

    $cartManager->addToCart($userId, $productId, $quantity);
    header('Location: carrito.php');
    exit;
}

$products = $productManager->getAllProducts();

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<?= $cajaAlert ?>

<div class="container mt-5">
    <h2 class="text-center">Productos Disponibles</h2>

    <div class="row mt-4 align-items-center">
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $product): ?>
                <div class="col-md-3 my-3">
                    <div class="card h-100 shadow-sm bg-secondary">
                        <div class="text-center p-3">
                            <img src="../<?= htmlspecialchars($product['image_url']) ?>"
                                 class="card-img-top rounded"
                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                 style="max-height: 150px; object-fit: contain;">
                        </div>

                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-center"><?= htmlspecialchars($product['name']) ?></h5>
                            <p class="card-text small text-center flex-grow-1"><?= htmlspecialchars($product['description']) ?></p>

                            <div class="text-center mb-2">
                                <span class="badge bg-info text-dark">Stock: <?= htmlspecialchars($product['stock']) ?></span>
                            </div>

                            <div class="text-center mb-3">
                                <h5 class="text-success">$<?= number_format($product['price_usd'], 2) ?></h5>
                                <small class="text-white"><?= number_format($product['price_ves'], 2) ?> VES</small>
                            </div>

                            <form action="#" method="post" class="mt-auto">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>">
                                <input type="hidden" name="quantity" value="1">

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-cart-plus me-2"></i> Agregar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center">No hay productos disponibles.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>
