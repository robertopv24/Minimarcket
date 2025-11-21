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

// Obtener parámetros de búsqueda y filtrado
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';

// Preparar la consulta SQL
$sql = "SELECT * FROM products WHERE 1";

if (!empty($search)) {
    $search = "%" . $search . "%";
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
}

if ($filter === 'stock_bajo') {
    $sql .= " AND stock < 10"; // Ejemplo: stock menor a 10
} elseif ($filter === 'stock_alto') {
    $sql .= " AND stock >= 10"; // Ejemplo: stock mayor o igual a 10
}

$stmt = $db->prepare($sql);

// Ejecutar la consulta
if (!empty($search)) {
    $stmt->execute([$search, $search]);
} else {
    $stmt->execute();
}

$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);





require_once '../templates/header.php';
require_once '../templates/menu.php';


?>

<div class="container mt-5">
    <h1 class="text-center">Gestión de Productos</h1>

    <?php if (!empty($_GET['success'])): ?>
        <div class="alert alert-<?= ($_GET['success'] === "true") ? "success" : "danger"; ?>">
            <?= ($_GET['success'] === "true") ? "Operación realizada con éxito." : "Error al procesar la solicitud."; ?>
        </div>
    <?php endif; ?>

    <div class="mb-3">
        <a href="add_product.php" class="btn btn-success">Agregar Nuevo Producto</a>
    </div>

    <form method="GET" action="">
        <div class="row mb-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Buscar por nombre o descripción" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-4">
                <select name="filter" class="form-select">
                    <option value="">Todos los productos</option>
                    <option value="stock_bajo" <?= ($filter === 'stock_bajo') ? 'selected' : '' ?>>Productos con bajo stock</option>
                    <option value="stock_alto" <?= ($filter === 'stock_alto') ? 'selected' : '' ?>>Productos con alto stock</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Buscar</button>
            </div>
        </div>
    </form>

    <table class="table table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Precio (USD)</th>
                <th>Precio (VES)</th>
                <th>Stock</th>
                <th>Imagen</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($productos)): ?>
                <?php foreach ($productos as $producto): ?>
                    <tr>
                        <td><?= htmlspecialchars($producto['id']) ?></td>
                        <td><?= htmlspecialchars($producto['name']) ?></td>
                        <td><?= htmlspecialchars($producto['description']) ?></td>
                        <td>$<?= number_format($producto['price_usd'], 2) ?></td>
                        <td><?= number_format($producto['price_ves'], 2) ?> VES</td>
                        <td><?= htmlspecialchars($producto['stock']) ?></td>
                        <td>
                            <?php if (!empty($producto['image_url']) && file_exists("../" . $producto['image_url'])): ?>
                                <img src="../<?= htmlspecialchars($producto['image_url']) ?>" alt="Imagen" width="50">
                            <?php else: ?>
                                <span class="text-muted">No disponible</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit_product.php?id=<?= $producto['id'] ?>" class="btn btn-warning btn-sm">Editar</a>
                            <form action="delete_product.php?id=<?= $producto['id'] ?>" method="POST" style="display:inline;">

                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este producto?');">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">No hay productos que coincidan con la búsqueda.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../templates/footer.php'; ?>
