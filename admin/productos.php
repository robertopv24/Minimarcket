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

// Lógica de Filtrado
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';

if ($filter === 'stock_bajo') {
    $productos = $productManager->getLowStockProducts(10); // Umbral de 10
} elseif (!empty($search)) {
    $productos = $productManager->searchProducts($search);
} else {
    $productos = $productManager->getAllProducts();
}

// Calcular Estadísticas Rápidas (KPIs)
$totalProductos = count($productos);
$valorInventarioUsd = 0;
$itemsCriticos = 0;

foreach ($productos as $p) {
    $valorInventarioUsd += $p['price_usd'] * $p['stock'];
    if ($p['stock'] <= 5) $itemsCriticos++;
}

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📦 Inventario de Productos</h2>
        <a href="add_product.php" class="btn btn-success">
            <i class="fa fa-plus-circle"></i> Nuevo Producto
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white shadow">
                <div class="card-body text-center">
                    <h3><?= $totalProductos ?></h3>
                    <small>Total Referencias</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white shadow">
                <div class="card-body text-center">
                    <h3>$<?= number_format($valorInventarioUsd, 2) ?></h3>
                    <small>Valor Venta del Inventario</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card <?= $itemsCriticos > 0 ? 'bg-danger' : 'bg-secondary' ?> text-white shadow">
                <div class="card-body text-center">
                    <h3><?= $itemsCriticos ?></h3>
                    <small>Stock Crítico (< 5)</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4 bg-light">
        <div class="card-body py-3">
            <form method="GET" action="" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Buscar por nombre..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="filter" class="form-select">
                        <option value="">Todos los productos</option>
                        <option value="stock_bajo" <?= ($filter === 'stock_bajo') ? 'selected' : '' ?>>⚠️ Stock Bajo</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 80px;">Imagen</th>
                            <th>Producto</th>
                            <th>Precios (PVP)</th>
                            <th>Margen</th>
                            <th>Stock</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($productos)): ?>
                            <?php foreach ($productos as $producto): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($producto['image_url']) && file_exists("../" . $producto['image_url'])): ?>
                                            <img src="../<?= htmlspecialchars($producto['image_url']) ?>" class="rounded border" width="50" height="50" style="object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                <i class="fa fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="fw-bold"><?= htmlspecialchars($producto['name']) ?></span>
                                        <?php if($producto['stock'] == 0): ?>
                                            <span class="badge bg-danger ms-2">AGOTADO</span>
                                        <?php endif; ?>
                                        <br>
                                        <small class="text-muted text-truncate d-block" style="max-width: 200px;">
                                            <?= htmlspecialchars($producto['description']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-success">$<?= number_format($producto['price_usd'], 2) ?></span>
                                            <span class="small text-muted"><?= number_format($producto['price_ves'], 2) ?> Bs</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-dark">
                                            <?= number_format($producto['profit_margin'], 1) ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                            $stockClass = 'bg-success';
                                            if ($producto['stock'] <= 5) $stockClass = 'bg-danger';
                                            elseif ($producto['stock'] <= 15) $stockClass = 'bg-warning text-dark';
                                        ?>
                                        <span class="badge <?= $stockClass ?> rounded-pill px-3">
                                            <?= htmlspecialchars($producto['stock']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="edit_product.php?id=<?= $producto['id'] ?>" class="btn btn-sm btn-warning" title="Editar">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <form action="delete_product.php?id=<?= $producto['id'] ?>" method="POST" style="display:inline;">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar <?= htmlspecialchars($producto['name']) ?>?');" title="Eliminar">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa fa-search fa-3x mb-3"></i><br>
                                    No se encontraron productos con ese criterio.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>
