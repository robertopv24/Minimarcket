<?php
require_once '../templates/autoload.php';
use Minimarcket\Core\Session\SessionManager;
use Minimarcket\Modules\Inventory\Services\ProductService;
use Minimarcket\Modules\Inventory\Services\RawMaterialService;
use Minimarcket\Modules\Manufacturing\Services\ProductionService;

global $app;
$container = $app->getContainer();
$sessionManager = $container->get(SessionManager::class);
$productService = $container->get(ProductService::class);
$rawMaterialService = $container->get(RawMaterialService::class);
$productionService = $container->get(ProductionService::class);

// Validación de seguridad
if (!$sessionManager->isAuthenticated() || $sessionManager->get('user_role') !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: productos.php');
    exit;
}

$productId = $_GET['id'];
$product = $productService->getProductById($productId);
$mensaje = '';

// ---------------------------------------------------------
// PROCESAMIENTO DE ACCIONES (POST)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Cambiar Tipo de Producto
    if ($action === 'update_type') {
        $productService->updateProductType($productId, $_POST['product_type']);
        $product = $productService->getProductById($productId);
        $mensaje = '<div class="alert alert-success">Tipo de producto actualizado.</div>';
    }

    // 2. Agregar Componente a la Receta Base
    if ($action === 'add_component') {
        $productService->addComponent($productId, $_POST['type'], $_POST['component_id'], $_POST['quantity']);
        $mensaje = '<div class="alert alert-success">Ingrediente base agregado.</div>';
    }

    // 3. Eliminar Componente de la Receta Base
    if ($action === 'remove_component') {
        $productService->removeComponent($_POST['component_row_id']);
    }

    // 4. AGREGAR EXTRA VÁLIDO (NUEVA LÓGICA)
    if ($action === 'add_valid_extra') {
        $rawId = $_POST['extra_raw_id'];
        // Si el precio viene vacío, mandamos NULL (usará precio base en lógica futura)
        // O mandamos el precio que el admin escriba.
        $price = !empty($_POST['extra_price']) ? $_POST['extra_price'] : 1.00; // Default $1 si no escribe nada

        if ($productService->addValidExtra($productId, $rawId, $price)) {
            $mensaje = '<div class="alert alert-success">Extra permitido agregado correctamente.</div>';
        }
    }

    // 5. ELIMINAR EXTRA VÁLIDO
    if ($action === 'remove_valid_extra') {
        if ($productService->removeValidExtra($_POST['extra_row_id'])) {
            $mensaje = '<div class="alert alert-warning">Extra eliminado de la lista.</div>';
        }
    }
}

// ---------------------------------------------------------
// CARGA DE DATOS
// ---------------------------------------------------------
// 1. Receta Base
$components = $productService->getProductComponents($productId);

// 2. Listas para selectores
$rawMaterials = $rawMaterialService->getAllMaterials();
$manufactured = $productionService->getAllManufactured();
$allProducts = $productService->getAllProducts();

// 3. Extras Configurados (NUEVO)
$validExtras = $productService->getValidExtras($productId);

// Calcular Costo Base Total
$totalCost = 0;
foreach ($components as $c) {
    $totalCost += ($c['quantity'] * $c['item_cost']);
}

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>🛠️ Configurar: <?= htmlspecialchars($product['name']) ?></h2>
        <a href="productos.php" class="btn btn-secondary">Volver</a>
    </div>

    <?= $mensaje ?>

    <div class="row">
        <div class="col-md-5">

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">Configuración Lógica</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_type">
                        <label class="form-label fw-bold">Comportamiento del Sistema:</label>
                        <select name="product_type" class="form-select mb-3" onchange="this.form.submit()">
                            <option value="simple" <?= $product['product_type'] == 'simple' ? 'selected' : '' ?>>Simple (Revender Stock propio)</option>
                            <option value="prepared" <?= $product['product_type'] == 'prepared' ? 'selected' : '' ?>>Preparado (Descuenta Receta)</option>
                            <option value="compound" <?= $product['product_type'] == 'compound' ? 'selected' : '' ?>>Combo (Agrupa otros productos)</option>
                        </select>
                    </form>
                    <div class="small text-muted">
                        <ul>
                            <li><strong>Simple:</strong> Refrescos, Chucherías.</li>
                            <li><strong>Preparado:</strong> Pizzas, Perros (Usa ingredientes).</li>
                            <li><strong>Combo:</strong> "2 Perros + 1 Refresco".</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <ul class="nav nav-tabs card-header-tabs" id="myTab" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active text-dark bg-white" data-bs-toggle="tab" data-bs-target="#recipe">📝 Receta Base</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link text-warning fw-bold" data-bs-toggle="tab" data-bs-target="#extras">➕ Extras Permitidos</button>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content" id="myTabContent">

                        <div class="tab-pane fade show active" id="recipe">
                            <h6 class="border-bottom pb-2">Agregar Ingrediente a la Receta</h6>

                            <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                                <li class="nav-item"><button class="nav-link active py-1" data-bs-toggle="pill" data-bs-target="#raw">Materia Prima</button></li>
                                <li class="nav-item"><button class="nav-link py-1" data-bs-toggle="pill" data-bs-target="#manuf">Cocina</button></li>
                                <li class="nav-item"><button class="nav-link py-1" data-bs-toggle="pill" data-bs-target="#prod">Productos</button></li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="raw">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="add_component">
                                        <input type="hidden" name="type" value="raw">
                                        <select name="component_id" class="form-select mb-2 select2">
                                            <?php foreach($rawMaterials as $r): ?>
                                                <option value="<?= $r['id'] ?>"><?= $r['name'] ?> (<?= $r['unit'] ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="number" step="0.001" name="quantity" class="form-control mb-2" placeholder="Cantidad (Ej: 0.200)" required>
                                        <button class="btn btn-primary w-100">Agregar a Receta</button>
                                    </form>
                                </div>
                                <div class="tab-pane fade" id="manuf">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="add_component">
                                        <input type="hidden" name="type" value="manufactured">
                                        <select name="component_id" class="form-select mb-2">
                                            <?php foreach($manufactured as $m): ?>
                                                <option value="<?= $m['id'] ?>"><?= $m['name'] ?> (Stock: <?= floatval($m['stock']) ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="number" step="0.001" name="quantity" class="form-control mb-2" placeholder="Cantidad" required>
                                        <button class="btn btn-primary w-100">Agregar a Receta</button>
                                    </form>
                                </div>
                                <div class="tab-pane fade" id="prod">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="add_component">
                                        <input type="hidden" name="type" value="product">
                                        <select name="component_id" class="form-select mb-2">
                                            <?php foreach($allProducts as $p): ?>
                                                <option value="<?= $p['id'] ?>"><?= $p['name'] ?> ($<?= $p['price_usd'] ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="number" step="1" name="quantity" class="form-control mb-2" placeholder="Cantidad" required>
                                        <button class="btn btn-primary w-100">Agregar a Receta</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="extras">
                            <div class="alert alert-info small">
                                <i class="fa fa-info-circle"></i> Aquí defines qué se le puede echar EXTRA a este producto (Ej: Tocineta, Queso).
                            </div>

                            <form method="POST" class="mb-3 border p-3 rounded bg-light">
                                <input type="hidden" name="action" value="add_valid_extra">

                                <div class="mb-2">
                                    <label class="form-label fw-bold small">Ingrediente (Materia Prima):</label>
                                    <select name="extra_raw_id" class="form-select form-select-sm" required>
                                        <?php foreach($rawMaterials as $r):
                                            // Filtramos empaques para que no salgan aquí
                                            if($r['category'] == 'packaging') continue;
                                        ?>
                                            <option value="<?= $r['id'] ?>"><?= $r['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label fw-bold small">Precio de Venta del Extra ($):</label>
                                    <input type="number" step="0.01" name="extra_price" class="form-control form-control-sm" placeholder="Ej: 1.00" value="1.00" required>
                                </div>

                                <button type="submit" class="btn btn-warning btn-sm w-100 fw-bold">
                                    <i class="fa fa-plus-circle"></i> Autorizar Extra
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-7">

            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-bold">📜 Receta Estándar (Componentes)</span>
                    <span class="badge bg-info text-dark">Costo Base: $<?= number_format($totalCost, 2) ?></span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Componente</th>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                                <th>Costo</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($components as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['item_name']) ?></td>
                                <td>
                                    <?php if($c['component_type'] == 'raw') echo '<span class="badge bg-secondary">Insumo</span>'; ?>
                                    <?php if($c['component_type'] == 'manufactured') echo '<span class="badge bg-warning text-dark">Cocina</span>'; ?>
                                    <?php if($c['component_type'] == 'product') echo '<span class="badge bg-primary">Producto</span>'; ?>
                                </td>
                                <td class="fw-bold"><?= floatval($c['quantity']) ?> <?= $c['item_unit'] ?></td>
                                <td>$<?= number_format($c['quantity'] * $c['item_cost'], 3) ?></td>
                                <td class="text-end">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="remove_component">
                                        <input type="hidden" name="component_row_id" value="<?= $c['id'] ?>">
                                        <button class="btn btn-sm text-danger p-0"><i class="fa fa-times"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($components)): ?>
                                <tr><td colspan="5" class="text-center py-3 text-muted">No hay ingredientes definidos.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card shadow border-warning">
                <div class="card-header bg-warning text-dark fw-bold">
                    <i class="fa fa-list-ul"></i> Lista de Extras Válidos para el Cliente
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0 small">
                        <thead>
                            <tr>
                                <th>Ingrediente</th>
                                <th>Precio Venta ($)</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($validExtras as $ve): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($ve['name']) ?>
                                    </td>
                                    <td class="fw-bold text-success">
                                        $<?= number_format((float)$ve['price_override'], 2) ?>
                                    </td>
                                    <td class="text-end">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="remove_valid_extra">
                                            <input type="hidden" name="extra_row_id" value="<?= $ve['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger py-0" title="Quitar de la lista">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($validExtras)): ?>
                                <tr><td colspan="3" class="text-center py-3 text-muted">No hay extras configurados. El cliente no podrá agregar nada adicional.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>
