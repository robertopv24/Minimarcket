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
    $productos = $productManager->getLowStockProducts();
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
    // Nota: Para KPIs rápidos usamos el stock físico,
    // pero podrías ajustarlo si quisieras valorar el stock virtual.
    $valorInventarioUsd += $p['price_usd'] * $p['stock'];
    $min = $p['min_stock'] ?? 5;
    if ($p['stock'] <= $min)
        $itemsCriticos++;
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
                    <small>Valor Venta del Inventario (Físico)</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card <?= $itemsCriticos > 0 ? 'bg-danger' : 'bg-secondary' ?> text-white shadow">
                <div class="card-body text-center">
                    <h3><?= $itemsCriticos ?></h3>
                    <small>Stock Crítico</small>
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
                        <input type="text" name="search" class="form-control" placeholder="Buscar por nombre..."
                            value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="filter" class="form-select">
                        <option value="">Todos los productos</option>
                        <option value="stock_bajo" <?= ($filter === 'stock_bajo') ? 'selected' : '' ?>>⚠️ Stock Bajo
                        </option>
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
                            <th>Categoría</th>
                            <th>Precios (PVP)</th>
                            <th>Margen</th>
                            <th class="text-center">Stock Disponible</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($productos)): ?>
                            <?php foreach ($productos as $producto): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($producto['image_url']) && file_exists("../" . $producto['image_url'])): ?>
                                            <img src="../<?= htmlspecialchars($producto['image_url']) ?>" class="rounded border"
                                                width="50" height="50" style="object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center"
                                                style="width: 50px; height: 50px;">
                                                <i class="fa fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="fw-bold"><?= htmlspecialchars($producto['name']) ?></span>

                                        <?php if ($producto['product_type'] === 'prepared'): ?>
                                            <span class="badge bg-warning text-dark ms-1" style="font-size: 0.7em;">COCINA</span>
                                        <?php elseif ($producto['product_type'] === 'simple'): ?>
                                            <span class="badge bg-secondary ms-1" style="font-size: 0.7em;">REVENTA</span>
                                        <?php endif; ?>

                                        <?php if (($producto['contour_logic_type'] ?? 'standard') === 'proportional'): ?>
                                            <span class="badge bg-info text-dark ms-1" style="font-size: 0.7em;"
                                                title="Lógica de contornos dividida">PROPORCIONAL</span>
                                        <?php endif; ?>

                                        <?php if ($producto['stock'] == 0 && $producto['product_type'] == 'simple'): ?>
                                            <span class="badge bg-danger ms-2">AGOTADO</span>
                                        <?php endif; ?>
                                        <br>
                                        <small class="text-muted text-truncate d-block" style="max-width: 200px;">
                                            <?= htmlspecialchars($producto['description'] ?? '') ?>
                                        </small>
                                    </td>

                                    <td>
                                        <?php if (!empty($producto['category_name'])): ?>
                                            <span class="badge bg-outline-primary text-primary border border-primary">
                                                <?= htmlspecialchars($producto['category_name']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">Sin Cat.</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div class="d-flex flex-column">
                                            <span
                                                class="fw-bold text-success">$<?= number_format($producto['price_usd'], 2) ?></span>
                                            <span class="small text-muted"><?= number_format($producto['price_ves'], 2) ?>
                                                Bs</span>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="badge bg-info text-dark">
                                            <?= number_format($producto['profit_margin'], 1) ?>%
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <?php
                                        $stockMostrar = 0;
                                        $esVirtual = false;

                                        // Lógica: Si es simple, muestra stock físico. Si es preparado, calcula receta.
                                        if ($producto['product_type'] === 'simple') {
                                            $stockMostrar = $producto['stock'];
                                            $esVirtual = false;
                                        } else {
                                            // ¡IMPORTANTE! Asegúrate de tener getVirtualStock en ProductManager.php
                                            $stockMostrar = $productManager->getVirtualStock($producto['id']);
                                            $esVirtual = true;
                                        }

                                        // Colores del semáforo dinámicos según min_stock
                                        $stockClass = 'bg-success';
                                        $min = $producto['min_stock'] ?? 5;
                                        if ($stockMostrar <= $min)
                                            $stockClass = 'bg-danger';
                                        elseif ($stockMostrar <= ($min * 2))
                                            $stockClass = 'bg-warning text-dark';
                                        ?>

                                        <span class="badge <?= $stockClass ?> rounded-pill px-3 position-relative">
                                            <?= $stockMostrar ?>

                                            <?php if ($esVirtual): ?>
                                                <span
                                                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary"
                                                    style="font-size: 0.6em;" title="Calculado según ingredientes disponibles">
                                                    <i class="fa fa-utensils"></i>
                                                </span>
                                            <?php endif; ?>
                                        </span>

                                        <div style="font-size: 10px;" class="text-muted mt-1">
                                            <?= $esVirtual ? 'Disp. Cocina' : 'Físico' ?>
                                        </div>
                                    </td>

                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="edit_product.php?id=<?= $producto['id'] ?>" class="btn btn-sm btn-warning"
                                                title="Editar">
                                                <i class="fa fa-edit"></i>
                                            </a>

                                            <a href="configurar_receta.php?id=<?= $producto['id'] ?>"
                                                class="btn btn-sm btn-dark ms-1" title="Configurar Receta">
                                                <i class="fa fa-cogs"></i>
                                            </a>

                                            <a href="configurar_defecto.php?id=<?= $producto['id'] ?>"
                                                class="btn btn-sm btn-outline-primary ms-1" title="Configurar Defectos">
                                                <i class="fa fa-wrench"></i>
                                            </a>

                                            <button type="button" class="btn btn-sm btn-info ms-1 text-white btn-duplicate"
                                                title="Duplicar" data-url="duplicate_product.php?id=<?= $producto['id'] ?>"
                                                data-name="<?= htmlspecialchars($producto['name']) ?>">
                                                <i class="fa fa-copy"></i>
                                            </button>

                                            <button type="button" class="btn btn-sm btn-danger ms-1 btn-delete" title="Eliminar"
                                                data-url="delete_product.php?id=<?= $producto['id'] ?>"
                                                data-name="<?= htmlspecialchars($producto['name']) ?>">
                                                <i class="fa fa-trash"></i>
                                            </button>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Manejo de Duplicación
        document.querySelectorAll('.btn-duplicate').forEach(btn => {
            btn.addEventListener('click', function () {
                const url = this.dataset.url;
                const name = this.dataset.name;

                Swal.fire({
                    title: '¿Duplicar Producto?',
                    text: `Se creará una copia de "${name}"`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, duplicar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });
        });

        // Manejo de Eliminación
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function () {
                const url = this.dataset.url; // URL de acción (era form submit, ahora podemos usar post vía fetch o form dinámico, pero delete_product también acepta GET o podemos hacer un form temporal)
                // Espera, delete_product.php originalmente era un FORM POST. 
                // Adaptaremos para enviar POST dinámico.
                const name = this.dataset.name;

                Swal.fire({
                    title: '¿Eliminar Producto?',
                    text: `Esta acción no se puede deshacer: "${name}"`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Crear formulario invisible y enviarlo
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = url;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
    });
</script>

<?php require_once '../templates/footer.php'; ?>