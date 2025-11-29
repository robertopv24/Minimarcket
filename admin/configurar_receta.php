<?php
require_once '../templates/autoload.php';
session_start();

if (!isset($_GET['id'])) {
    header('Location: productos.php');
    exit;
}

$productId = $_GET['id'];
$product = $productManager->getProductById($productId);
$mensaje = '';

// ACCIONES
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Cambiar Tipo de Producto
    if ($_POST['action'] === 'update_type') {
        $productManager->updateProductType($productId, $_POST['product_type']);
        $product = $productManager->getProductById($productId); // Refrescar
        $mensaje = '<div class="alert alert-success">Tipo de producto actualizado.</div>';
    }

    // 2. Agregar Componente
    if ($_POST['action'] === 'add_component') {
        $productManager->addComponent($productId, $_POST['type'], $_POST['component_id'], $_POST['quantity']);
        $mensaje = '<div class="alert alert-success">Componente agregado.</div>';
    }

    // 3. Eliminar Componente
    if ($_POST['action'] === 'remove_component') {
        $productManager->removeComponent($_POST['component_row_id']);
    }
}

// Cargar listas para los selectores
$components = $productManager->getProductComponents($productId);
$rawMaterials = $rawMaterialManager->getAllMaterials();
$manufactured = $productionManager->getAllManufactured();
$allProducts = $productManager->getAllProducts();

// Calcular Costo Total
$totalCost = 0;
foreach ($components as $c) {
    $totalCost += ($c['quantity'] * $c['item_cost']);
}

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>🛠️ Receta: <?= htmlspecialchars($product['name']) ?></h2>
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
                            <option value="prepared" <?= $product['product_type'] == 'prepared' ? 'selected' : '' ?>>Preparado (Descuenta Ingredientes al momento)</option>
                            <option value="compound" <?= $product['product_type'] == 'compound' ? 'selected' : '' ?>>Combo (Agrupa otros productos)</option>
                        </select>
                    </form>
                    <div class="small text-muted">
                        <ul>
                            <li><strong>Simple:</strong> Refrescos, Chucherías.</li>
                            <li><strong>Preparado:</strong> Pizzas, Perros, Tumbarranchos.</li>
                            <li><strong>Combo:</strong> "2 Perros + 1 Refresco".</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">Agregar Ingredientes/Partes</div>
                <div class="card-body">
                    <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#raw">Materia Prima</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#manuf">Cocina</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#prod">Productos</button></li>
                    </ul>

                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="raw">
                            <form method="POST">
                                <input type="hidden" name="action" value="add_component">
                                <input type="hidden" name="type" value="raw">
                                <select name="component_id" class="form-select mb-2 select2">
                                    <?php foreach($rawMaterials as $r): ?>
                                        <option value="<?= $r['id'] ?>"><?= $r['name'] ?> (<?= $r['unit'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" step="0.001" name="quantity" class="form-control mb-2" placeholder="Cantidad" required>
                                <button class="btn btn-success w-100">Agregar</button>
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
                                <button class="btn btn-success w-100">Agregar</button>
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
                                <button class="btn btn-success w-100">Agregar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between">
                    <span>📜 Composición del Producto</span>
                    <span class="badge bg-info text-dark">Costo Base: $<?= number_format($totalCost, 2) ?></span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Componente</th>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                                <th>Costo Est.</th>
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
                                        <button class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($components)): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">Este producto no descuenta ingredientes aún.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Precio de Venta Actual:</span>
                        <span class="text-success">$<?= number_format($product['price_usd'], 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span>Margen de Ganancia:</span>
                        <span>
                            <?php
                                $margin = $product['price_usd'] - $totalCost;
                                echo "$" . number_format($margin, 2);
                                echo ($product['price_usd'] > 0) ? " (" . round(($margin / $product['price_usd']) * 100) . "%)" : "";
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>
