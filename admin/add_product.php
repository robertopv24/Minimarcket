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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
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
            // Insertar el producto en la base de datos
            $sql = "INSERT INTO products (name, description, price_usd, price_ves, stock, image_url) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $resultado = $stmt->execute([$nombre, $descripcion, $precio_usd, $precio_ves, $stock, $rutaImagen]);

            if ($resultado) {
                $mensaje = '<div class="alert alert-success">Producto creado con éxito.</div>';
            } else {
                $mensaje = '<div class="alert alert-danger">Error al crear el producto.</div>';
            }
        } else {
            $mensaje = '<div class="alert alert-warning">Error al cargar la imagen.</div>';
        }
    } else {
        // Insertar el producto en la base de datos sin imagen
        $sql = "INSERT INTO products (name, description, price_usd, price_ves, stock) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $resultado = $stmt->execute([$nombre, $descripcion, $precio_usd, $precio_ves, $stock]);

        if ($resultado) {
            $mensaje = '<div class="alert alert-success">Producto creado con éxito.</div>';
        } else {
            $mensaje = '<div class="alert alert-danger">Error al crear el producto.</div>';
        }
    }
}
?>

<div class="container mt-5">
    <h2>Agregar Producto</h2>
    <?php echo $mensaje; ?>
    <form method="post" action="" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="nombre" name="nombre" required>
        </div>
        <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción</label>
            <textarea class="form-control" id="descripcion" name="descripcion"></textarea>
        </div>
        <div class="mb-3">
            <label for="precio_usd" class="form-label">Precio (USD)</label>
            <input type="number" class="form-control" id="precio_usd" name="precio_usd" step="0.01" required>
        </div>
        <div class="mb-3">
            <label for="precio_ves" class="form-label">Precio (VES)</label>
            <input type="number" class="form-control" id="precio_ves" name="precio_ves" step="0.01" required>
        </div>
        <div class="mb-3">
            <label for="stock" class="form-label">Stock</label>
            <input type="number" class="form-control" id="stock" name="stock" required>
        </div>
        <div class="mb-3">
            <label for="imagen" class="form-label">Imagen</label>
            <input type="file" class="form-control" id="imagen" name="imagen">
        </div>
        <button type="submit" class="btn btn-success">Agregar</button>
    </form>
</div>

<?php require_once '../templates/footer.php'; ?>
