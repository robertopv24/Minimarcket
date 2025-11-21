<?php


session_start();


require_once '../templates/autoload.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $productId = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $userId = $_SESSION['user_id'] ?? null; // Obtener el ID del usuario de la sesión

    if ($userId) {
        $cartManager->addToCart($userId, $productId, $quantity);
        // Redirigir a la página del carrito o mostrar un mensaje de éxito
        header('Location: carrito.php'); // Redirigir a la página del carrito
        exit;
    } else {
        // Manejar el caso en que el usuario no está logueado
        echo "<p>Debes iniciar sesión para agregar productos al carrito.</p>";
    }
}



$products = $productManager->getAllProducts();

// tienda.php - Página de productos
require_once '../templates/header.php';
require_once '../templates/menu.php';



?>





            <div class="container mt-5">
                <h2 class="text-center">Productos Disponibles</h2>

                <div class="row mt-4 align-items-center " >
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $product): ?>






                            <div class="bg-secondary align-items-center  card col-md-3 p-3 p-sm-2 my-3 mx-3">
                                <div class="card  col-md-5 p-5 p-sm-3 my-3 mx-5">
                                    <img src="../<?= htmlspecialchars($product['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>">
                                    </div>

                                    <div class="card-body ">
                                        <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                        <p class="card-text"><?= htmlspecialchars($product['description']) ?></p>
                                        <p class="card-text"><strong>Disponibles:</strong> <?= htmlspecialchars($product['stock']) ?></p>
                                        <p class="card-text"><strong>Precio:</strong> $<?= number_format($product['price_usd'], 2) ?> | <?= number_format($product['price_ves'], 2) ?> VES</p>
                                        <form action="#" method="post">
                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>">
                                            <input type="hidden" name="quantity" value="1">

                                              <br>
                                            <button type="submit" class="btn btn-primary">Agregar al Carrito</button>

                                        </form>
                                    </div>

                            </div>





                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center">No hay productos disponibles.</p>
                    <?php endif; ?>
                </div>
            </div>




<?php
require_once '../templates/footer.php';
?>
