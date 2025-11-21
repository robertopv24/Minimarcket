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

$mensaje = '';

// Obtener el ID del producto a editar
$productoId = $_GET['id'] ?? 0;

// Obtener la información del producto
$sql = "SELECT * FROM products WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$productoId]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    $mensaje = '<div class="alert alert-danger">Producto no encontrado.</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_GET['id'];
    $name = $_POST['name'];
    $descripcion = $_POST['descripcion'];
    $precio_usd = $_POST['precio_usd'];
    $precio_ves = $_POST['precio_ves'];
    $stock = $_POST['stock'];

    // Procesar la imagen si se ha cargado
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $nombreImagen = uniqid() . '_' . $_FILES['imagen']['name'];
        $rutaImagen = 'uploads/product_images/' . $nombreImagen;
        $rutaImagend = '../' . 'uploads/product_images/' . $nombreImagen;
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaImagend)) {
            // Actualizar el producto en la base de datos con la nueva imagen
            $sql = "UPDATE products SET id = ?, name = ?, description = ?, price_usd = ?, price_ves = ?, stock = ?, image_url = ? WHERE id = ?";
            $stmt = $db->prepare($sql);
            $resultado = $stmt->execute([$id, $name, $descripcion, $precio_usd, $precio_ves, $stock, $rutaImagen, $productoId]);

            if ($resultado) {
                $mensaje = '<div class="alert alert-success">Producto actualizado con éxito.</div>';
                // Volver a obtener la información actualizada del producto
                $stmt->execute([$productoId]);
                $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $mensaje = '<div class="alert alert-danger">Error al actualizar el producto.</div>';
            }
        } else {
            $mensaje = '<div class="alert alert-warning">Error al cargar la imagen.</div>';
        }
    } else {
        // Actualizar el producto en la base de datos sin cambiar la imagen
        $sql = "UPDATE products SET id = ?, name = ?, description = ?, price_usd = ?, price_ves = ?, stock = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $resultado = $stmt->execute([$id, $name, $descripcion, $precio_usd, $precio_ves, $stock, $productoId]);

        if ($resultado) {
            $mensaje = '<div class="alert alert-success">Producto actualizado con éxito.</div>';
            // Volver a obtener la información actualizada del producto
            $stmt->execute([$productoId]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $mensaje = '<div class="alert alert-danger">Error al actualizar el producto.</div>';
        }
    }
}
?>

<div class="container mt-5">
    <h2>Editar Producto</h2>
    <?php echo $mensaje; ?>
    <?php if ($producto): ?>
        <form method="post" action="" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($producto['name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea class="form-control" id="descripcion" name="descripcion"><?php echo htmlspecialchars($producto['description']); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="precio_usd" class="form-label">Precio (USD)</label>
                <input type="number" class="form-control" id="precio_usd" name="precio_usd" step="0.01" value="<?php echo htmlspecialchars($producto['price_usd']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="precio_ves" class="form-label">Precio (VES)</label>
                <input type="number" class="form-control" id="precio_ves" name="precio_ves" step="0.01" value="<?php echo htmlspecialchars($producto['price_ves']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="stock" class="form-label">Stock</label>
                <input type="number" class="form-control" id="stock" name="stock" value="<?php echo htmlspecialchars($producto['stock']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="imagen" class="form-label">Imagen</label>
                <input type="file" class="form-control" id="imagen" name="imagen">
                <?php if (!empty($producto['image_url']) && file_exists("../" . $producto['image_url'])): ?>
                    <img src="../<?php echo htmlspecialchars($producto['image_url']); ?>" alt="Imagen actual" width="100">
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </form>
    <?php endif; ?>
</div>

<?php require_once '../templates/footer.php'; ?>
