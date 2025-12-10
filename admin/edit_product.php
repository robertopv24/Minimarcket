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
$productoId = $_GET['id'] ?? 0;
$producto = $productManager->getProductById($productoId);

if (!$producto) {
    echo '<div class="container mt-5"><div class="alert alert-danger">Producto no encontrado.</div><a href="productos.php" class="btn btn-primary">Volver</a></div>';
    require_once '../templates/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $precio_usd = $_POST['precio_usd'];
    $precio_ves = $_POST['precio_ves'];
    $stock = $_POST['stock'];
    $profit_margin = $_POST['profit_margin']; // <--- NUEVO CAMPO

    // Procesar imagen
    // Procesar imagen
    $rutaImagen = null; // null indica que no se actualiza la imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        require_once '../funciones/UploadHelper.php'; // Ensure helper is loaded
        $uploadedPath = UploadHelper::uploadImage($_FILES['imagen']);

        if ($uploadedPath) {
            $rutaImagen = $uploadedPath;
        } else {
            $mensaje = '<div class="alert alert-warning">Error de seguridad o formato al subir la imagen.</div>';
        }
    }

    // Actualizar usando ProductManager
    if ($productManager->updateProduct($id, $nombre, $descripcion, $precio_usd, $precio_ves, $stock, $rutaImagen, $profit_margin)) {
        $mensaje = '<div class="alert alert-success">Producto actualizado con éxito.</div>';
        $producto = $productManager->getProductById($id); // Refrescar datos
    } else {
        $mensaje = '<div class="alert alert-danger">Error al actualizar el producto.</div>';
    }
}

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-warning text-dark">
            <h3 class="mb-0">Editar Producto: <?= htmlspecialchars($producto['name']) ?></h3>
        </div>
        <div class="card-body">
            <?= $mensaje; ?>

            <form method="post" action="" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $producto['id'] ?>">

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" class="form-control" name="nombre"
                                value="<?= htmlspecialchars($producto['name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="descripcion"
                                rows="3"><?= htmlspecialchars($producto['description']) ?></textarea>
                        </div>

                        <div class="card border-warning mb-3">
                            <div class="card-header bg-transparent border-warning text-warning-dark fw-bold">Ajuste de
                                Precios y Rentabilidad</div>
                            <div class="card-body text-dark">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Precio ($)</label>
                                        <input type="number" class="form-control" id="precio_usd" name="precio_usd"
                                            step="0.01" value="<?= $producto['price_usd'] ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Precio (Bs)</label>
                                        <input type="number" class="form-control" id="precio_ves" name="precio_ves"
                                            step="0.01" value="<?= $producto['price_ves'] ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-success">Margen (%)</label>
                                        <input type="number" class="form-control border-success fw-bold"
                                            name="profit_margin" step="0.01" value="<?= $producto['profit_margin'] ?>"
                                            required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Stock</label>
                            <input type="number" class="form-control" name="stock" value="<?= $producto['stock'] ?>"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Imagen Actual</label>
                            <div class="mb-2">
                                <?php if (!empty($producto['image_url']) && file_exists("../" . $producto['image_url'])): ?>
                                    <img src="../<?= htmlspecialchars($producto['image_url']) ?>" class="img-thumbnail"
                                        width="150">
                                <?php else: ?>
                                    <span class="text-muted">Sin imagen</span>
                                <?php endif; ?>
                            </div>
                            <label class="form-label">Cambiar Imagen</label>
                            <input type="file" class="form-control" name="imagen" accept="image/*">
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-3">
                    <button type="submit" class="btn btn-warning btn-lg">Actualizar Cambios</button>
                    <a href="productos.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Calculadora automática al editar
    const rate = <?= $config->get('exchange_rate') ?? 1 ?>;
    document.getElementById('precio_usd').addEventListener('input', function (e) {
        const usd = parseFloat(e.target.value) || 0;
        document.getElementById('precio_ves').value = (usd * rate).toFixed(2);
    });
</script>

<?php require_once '../templates/footer.php'; ?>