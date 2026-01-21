<?php
// Production settings applied via autoload

require_once '../templates/autoload.php';
require_once '../funciones/Csrf.php';
require_once '../funciones/UploadHelper.php';

session_start();
// Validación de seguridad estándar
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
        die("Error de seguridad: Token CSRF inválido.");
    }
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $precio_usd = $_POST['precio_usd'] ?? 0;
    $precio_ves = $_POST['precio_ves'] ?? 0;
    $stock = $_POST['stock'] ?? 0;
    $min_stock = $_POST['min_stock'] ?? 5;
    $profit_margin = $_POST['profit_margin'] ?? 20.00;

    // Procesar imagen (RCE FIX)
    $rutaImagen = 'default.jpg';
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $uploadedPath = UploadHelper::uploadImage($_FILES['imagen']);
        if ($uploadedPath) {
            $rutaImagen = $uploadedPath;
        } else {
            $mensaje = '<div class="alert alert-warning">Error de seguridad o formato al subir la imagen. Se usará default.</div>';
        }
    }

    // Usar ProductManager para crear (Limpio y Seguro)
    if ($productManager->createProduct($nombre, $descripcion, $precio_usd, $precio_ves, $stock, $rutaImagen, $profit_margin, $min_stock)) {
        $mensaje = '<div class="alert alert-success">Producto creado con éxito.</div>';
    } else {
        $mensaje = '<div class="alert alert-danger">Error al crear el producto en base de datos.</div>';
    }
}

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-success text-white">
            <h3 class="mb-0">Agregar Nuevo Producto</h3>
        </div>
        <div class="card-body">
            <?= $mensaje; ?>

            <form method="post" action="" enctype="multipart/form-data">
                <?= Csrf::insertTokenField(); ?>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del Producto</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                        </div>

                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6 class="card-title text-primary text-warning">Estructura de Costos</h6>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label for="precio_usd" class="form-label fw-bold text-warning">Precio Venta
                                            ($)</label>
                                        <input type="number" class="form-control" id="precio_usd" name="precio_usd"
                                            step="0.01" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="precio_ves" class="form-label text-warning">Precio Venta
                                            (Bs)</label>
                                        <input type="number" class="form-control" id="precio_ves" name="precio_ves"
                                            step="0.01" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="profit_margin" class="form-label fw-bold text-warning">Margen
                                            (%)</label>
                                        <input type="number" class="form-control border-success" id="profit_margin"
                                            name="profit_margin" step="0.01" value="20.00" required>
                                        <small class="text-muted" style="font-size: 0.7rem;">Usado para recálculo
                                            automático</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label for="stock" class="form-label text-warning">Stock Inicial</label>
                                <input type="number" class="form-control border-warning" id="stock" name="stock"
                                    value="0" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label for="min_stock" class="form-label text-danger">Stock Mínimo (Alerta)</label>
                                <input type="number" class="form-control border-danger" id="min_stock" name="min_stock"
                                    value="5" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="imagen" class="form-label">Imagen del Producto</label>
                            <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*">
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">Guardar Producto</button>
                    <a href="productos.php" class="btn btn-secondary">Volver a la lista</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Script opcional para calcular precio VES automático al escribir USD
    const rate = <?= $config->get('exchange_rate') ?? 1 ?>;
    document.getElementById('precio_usd').addEventListener('input', function (e) {
        const usd = parseFloat(e.target.value) || 0;
        document.getElementById('precio_ves').value = (usd * rate).toFixed(2);
    });
</script>

<?php require_once '../templates/footer.php'; ?>