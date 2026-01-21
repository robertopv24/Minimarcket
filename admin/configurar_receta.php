<?php
require_once '../templates/autoload.php';
session_start();

// Validación de seguridad
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: productos.php');
    exit;
}

$productId = $_GET['id'];
$product = $productManager->getProductById($productId);
$mensaje = '';

// ---------------------------------------------------------
// PROCESAMIENTO DE ACCIONES (POST)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Cambiar Tipo de Producto
    if ($action === 'update_type') {
        $productManager->updateProductType($productId, $_POST['product_type']);
        $product = $productManager->getProductById($productId);
        $mensaje = '<div class="alert alert-success">Tipo de producto actualizado.</div>';
    }

    // 2. Agregar Componente a la Receta Base
    if ($action === 'add_component') {
        $productManager->addComponent($productId, $_POST['type'], $_POST['component_id'], $_POST['quantity']);
        $mensaje = '<div class="alert alert-success">Ingrediente base agregado.</div>';
    }

    // 3. Eliminar Componente de la Receta Base
    if ($action === 'remove_component') {
        $productManager->removeComponent($_POST['component_row_id']);
    }

    // 4. AGREGAR EXTRA VÁLIDO (NUEVA LÓGICA)
    if ($action === 'add_valid_extra') {
        $rawId = $_POST['extra_raw_id'];
        // Si el precio viene vacío, mandamos NULL (usará precio base en lógica futura)
        // O mandamos el precio que el admin escriba.
        $price = !empty($_POST['extra_price']) ? $_POST['extra_price'] : 1.00; // Default $1 si no escribe nada

        $qty = !empty($_POST['extra_qty']) ? $_POST['extra_qty'] : 1.000000;

        if ($productManager->addValidExtra($productId, $rawId, $price, $qty)) {
            $mensaje = '<div class="alert alert-success">Extra permitido agregado correctamente.</div>';
        }
    }

    // 5. ELIMINAR EXTRA VÁLIDO
    if ($action === 'remove_valid_extra') {
        if ($productManager->removeValidExtra($_POST['extra_row_id'])) {
            $mensaje = '<div class="alert alert-warning">Extra eliminado de la lista.</div>';
        }
    }

    // 6. ACTUALIZAR CANTIDAD DE COMPONENTE (RECETA)
    if ($action === 'update_component') {
        $productManager->updateComponentQuantity($_POST['component_row_id'], $_POST['quantity']);
        $mensaje = '<div class="alert alert-success">Cantidad de ingrediente actualizada.</div>';
    }

    // 7. ACTUALIZAR EXTRA (PRECIO Y CANTIDAD)
    if ($action === 'update_valid_extra') {
        $productManager->updateValidExtraQuantity($_POST['extra_row_id'], $_POST['price_override'], $_POST['quantity_required']);
        $mensaje = '<div class="alert alert-success">Configuración de extra actualizada.</div>';
    }

    // 8. ACTUALIZAR MÁXIMO DE CONTORNOS (NUEVO)
    if ($action === 'update_max_sides') {
        $productManager->updateMaxSides($productId, $_POST['max_sides']);
        $product = $productManager->getProductById($productId);
        $mensaje = '<div class="alert alert-success">Límite de contornos actualizado.</div>';
    }

    // 9. AGREGAR CONTORNO VÁLIDO (NUEVO)
    if ($action === 'add_valid_side') {
        $productManager->addValidSide($productId, $_POST['side_type'], $_POST['side_component_id'], $_POST['side_qty'], $_POST['side_price']);
        $mensaje = '<div class="alert alert-success">Contorno / Opción agregada.</div>';
    }

    // 10. ELIMINAR CONTORNO VÁLIDO (NUEVO)
    if ($action === 'remove_valid_side') {
        $productManager->removeValidSide($_POST['side_row_id']);
        $mensaje = '<div class="alert alert-warning">Contorno eliminado.</div>';
    }

    // 11. ACTUALIZAR CONTORNO (NUEVO)
    if ($action === 'update_valid_side') {
        $productManager->updateValidSide($_POST['side_row_id'], $_POST['side_qty'], $_POST['side_price']);
        $mensaje = '<div class="alert alert-success">Contorno actualizado.</div>';
    }

    // 12. AGREGAR EMPAQUE (NUEVO)
    if ($action === 'add_packaging') {
        $productManager->addProductPackaging($productId, $_POST['packaging_raw_id'], $_POST['packaging_qty']);
        $mensaje = '<div class="alert alert-success">Material de empaque agregado.</div>';
    }

    // 13. ELIMINAR EMPAQUE (NUEVO)
    if ($action === 'remove_packaging') {
        $productManager->removeProductPackaging($_POST['packaging_row_id']);
        $mensaje = '<div class="alert alert-warning">Empaque eliminado.</div>';
    }

    // 14. ACTUALIZAR EMPAQUE (NUEVO)
    if ($action === 'update_packaging') {
        $productManager->updateProductPackaging($_POST['packaging_row_id'], $_POST['packaging_qty']);
        $mensaje = '<div class="alert alert-success">Cantidad de empaque actualizada.</div>';
    }

    // 15. COPIAR CONFIGURACIÓN (NUEVO)
    if ($action === 'copy_config') {
        $type = $_POST['copy_type'];
        $toId = $_POST['target_product_id'];

        if ($type === 'recipe') {
            $productManager->copyComponents($productId, $toId);
            $mensaje = '<div class="alert alert-success">Receta copiada correctamente al producto de destino.</div>';
        } elseif ($type === 'extras') {
            $productManager->copyExtras($productId, $toId);
            $mensaje = '<div class="alert alert-success">Extras copiados correctamente.</div>';
        } elseif ($type === 'sides') {
            $productManager->copySides($productId, $toId);
            $mensaje = '<div class="alert alert-success">Contornos copiados correctamente.</div>';
        } elseif ($type === 'packaging') {
            $productManager->copyPackaging($productId, $toId);
            $mensaje = '<div class="alert alert-success">Configuración de empaque copiada.</div>';
        }
    }
}

// ---------------------------------------------------------
// CARGA DE DATOS
// ---------------------------------------------------------
// 1. Receta Base
$components = $productManager->getProductComponents($productId);

// 2. Listas para selectores
$rawMaterials = $rawMaterialManager->getAllMaterials();
$manufactured = $productionManager->getAllManufactured();
$allProducts = $productManager->getAllProducts();

// 3. Extras Configurados (NUEVO)
$validExtras = $productManager->getValidExtras($productId);

// 4. Contornos Configurados (NUEVO)
$validSides = $productManager->getValidSides($productId);

// 5. Empaques Configurados (NUEVO)
$productPackaging = $productManager->getProductPackaging($productId);

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
                    <!-- Formulario Tipo de Producto -->
                    <form method="POST" class="mb-3 border-bottom pb-2">
                        <input type="hidden" name="action" value="update_type">
                        <label class="form-label fw-bold">Comportamiento del Sistema:</label>
                        <select name="product_type" class="form-select mb-2" onchange="this.form.submit()">
                            <option value="simple" <?= $product['product_type'] == 'simple' ? 'selected' : '' ?>>Simple
                                (Revender Stock propio)</option>
                            <option value="prepared" <?= $product['product_type'] == 'prepared' ? 'selected' : '' ?>>
                                Preparado (Descuenta Receta)</option>
                            <option value="compound" <?= $product['product_type'] == 'compound' ? 'selected' : '' ?>>Combo
                                (Agrupa otros productos)</option>
                        </select>
                    </form>

                    <!-- Formulario Límite de Contornos -->
                    <form method="POST">
                        <input type="hidden" name="action" value="update_max_sides">
                        <label class="form-label fw-bold">Límite de Contornos / Opciones:</label>
                        <div class="input-group mb-2">
                            <input type="number" name="max_sides" class="form-control"
                                value="<?= (int) $product['max_sides'] ?>" min="0">
                            <button class="btn btn-primary" type="submit">Establecer Límite</button>
                        </div>
                        <small class="text-muted d-block mb-2">
                            Esto define cuántos contornos puede elegir el cliente.
                            <strong>Ejemplo Mixta:</strong> Pon <code>2</code> si la hamburguesa lleva Carne y Pollo.
                        </small>
                    </form>

                    <div class="small text-muted mt-3">
                        <ul>
                            <li><strong>Simple:</strong> Productos que no se preparan (Refrescos).</li>
                            <li><strong>Preparado:</strong> Productos de cocina con receta.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <ul class="nav nav-tabs card-header-tabs" id="myTab" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active text-dark bg-white" data-bs-toggle="tab"
                                data-bs-target="#recipe">📝 Receta Base</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link text-warning fw-bold" data-bs-toggle="tab"
                                data-bs-target="#extras">➕ Extras Permitidos</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link text-info fw-bold" data-bs-toggle="tab" data-bs-target="#sides">🍱
                                Contornos / Opciones</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link text-success fw-bold" data-bs-toggle="tab"
                                data-bs-target="#packaging">📦 Empaque</button>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content" id="myTabContent">

                        <div class="tab-pane fade show active" id="recipe">
                            <h6 class="border-bottom pb-2">Agregar Ingrediente a la Receta</h6>

                            <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                                <li class="nav-item"><button class="nav-link active py-1" data-bs-toggle="pill"
                                        data-bs-target="#raw">Materia Prima</button></li>
                                <li class="nav-item"><button class="nav-link py-1" data-bs-toggle="pill"
                                        data-bs-target="#manuf">Cocina</button></li>
                                <li class="nav-item"><button class="nav-link py-1" data-bs-toggle="pill"
                                        data-bs-target="#prod">Productos</button></li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="raw">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="add_component">
                                        <input type="hidden" name="type" value="raw">
                                        <select name="component_id" class="form-select mb-2 select2">
                                            <?php foreach ($rawMaterials as $r): ?>
                                                <option value="<?= $r['id'] ?>"><?= $r['name'] ?> (<?= $r['unit'] ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="number" step="0.001" name="quantity" class="form-control mb-2"
                                            placeholder="Cantidad (Ej: 0.200)" required>
                                        <button class="btn btn-primary w-100">Agregar a Receta</button>
                                    </form>
                                </div>
                                <div class="tab-pane fade" id="manuf">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="add_component">
                                        <input type="hidden" name="type" value="manufactured">
                                        <select name="component_id" class="form-select mb-2">
                                            <?php foreach ($manufactured as $m): ?>
                                                <option value="<?= $m['id'] ?>"><?= $m['name'] ?> (Stock:
                                                    <?= floatval($m['stock']) ?>     <?= $m['unit'] ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="number" step="0.001" name="quantity" class="form-control mb-2"
                                            placeholder="Cantidad" required>
                                        <button class="btn btn-primary w-100">Agregar a Receta</button>
                                    </form>
                                </div>
                                <div class="tab-pane fade" id="prod">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="add_component">
                                        <input type="hidden" name="type" value="product">
                                        <select name="component_id" class="form-select mb-2">
                                            <?php foreach ($allProducts as $p): ?>
                                                <option value="<?= $p['id'] ?>"><?= $p['name'] ?> ($<?= $p['price_usd'] ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="number" step="1" name="quantity" class="form-control mb-2"
                                            placeholder="Cantidad" required>
                                        <button class="btn btn-primary w-100">Agregar a Receta</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="extras">
                            <div class="alert alert-info small">
                                <i class="fa fa-info-circle"></i> Aquí defines qué se le puede echar EXTRA a este
                                producto (Ej: Tocineta, Queso).
                            </div>

                            <form method="POST" class="mb-3 border p-3 rounded bg-light">
                                <input type="hidden" name="action" value="add_valid_extra">

                                <div class="mb-2">
                                    <label class="form-label fw-bold small">Ingrediente (Materia Prima):</label>
                                    <select name="extra_raw_id" class="form-select form-select-sm" required>
                                        <?php foreach ($rawMaterials as $r):
                                            // Filtramos empaques para que no salgan aquí
                                            if ($r['category'] == 'packaging')
                                                continue;
                                            ?>
                                            <option value="<?= $r['id'] ?>"><?= $r['name'] ?> (<?= $r['unit'] ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label fw-bold small">Precio de Venta del Extra ($):</label>
                                    <input type="number" step="0.01" name="extra_price"
                                        class="form-control form-control-sm" placeholder="Ej: 1.00" value="1.00"
                                        required>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label fw-bold small">Cantidad Insumo a Descontar:</label>
                                    <input type="number" step="0.000001" name="extra_qty"
                                        class="form-control form-control-sm" placeholder="Ej: 0.100" value="1.00"
                                        required>
                                </div>

                                <button type="submit" class="btn btn-warning btn-sm w-100 fw-bold">
                                    <i class="fa fa-plus-circle"></i> Autorizar Extra
                                </button>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="sides">
                            <div class="alert alert-info small">
                                <i class="fa fa-info-circle"></i> Aquí defines las opciones intercambiables (Contornos).
                                Ej: Carne, Pollo o Lomo para una hamburguesa base.
                            </div>

                            <form method="POST" class="mb-3 border p-3 rounded bg-light">
                                <input type="hidden" name="action" value="add_valid_side">

                                <div class="mb-2">
                                    <label class="form-label fw-bold small">Tipo de Opción:</label>
                                    <select name="side_type" class="form-select form-select-sm" id="side_type_select">
                                        <option value="raw">Materia Prima</option>
                                        <option value="manufactured">Cocina (Preparado)</option>
                                        <option value="product">Otro Producto</option>
                                    </select>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label fw-bold small">Seleccionar:</label>
                                    <select name="side_component_id" class="form-select form-select-sm select2"
                                        id="side_component_select" required>
                                        <?php foreach ($rawMaterials as $r): ?>
                                            <option value="<?= $r['id'] ?>" class="opt-raw"><?= $r['name'] ?>
                                                (<?= $r['unit'] ?>)</option>
                                        <?php endforeach; ?>
                                        <?php foreach ($manufactured as $m): ?>
                                            <option value="<?= $m['id'] ?>" class="opt-manuf" style="display:none;">
                                                <?= $m['name'] ?> (<?= $m['unit'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                        <?php foreach ($allProducts as $p): ?>
                                            <option value="<?= $p['id'] ?>" class="opt-prod" style="display:none;">
                                                <?= $p['name'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label fw-bold small">Cantidad a descontar:</label>
                                    <input type="number" step="0.000001" name="side_qty"
                                        class="form-control form-control-sm" value="1" required>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label fw-bold small">Cargo Adicional ($):</label>
                                    <input type="number" step="0.01" name="side_price"
                                        class="form-control form-control-sm" value="0.00" required>
                                    <small class="text-muted">Generalmente 0 si es parte del precio base.</small>
                                </div>

                                <button type="submit" class="btn btn-info btn-sm w-100 fw-bold">
                                    <i class="fa fa-plus-circle"></i> Agregar Opción
                                </button>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="packaging">
                            <div class="alert alert-success small">
                                <i class="fa fa-info-circle"></i> Aquí defines los materiales de empaque que se
                                descuentan automáticamente (Bolsas, Envases, Servilletas).
                            </div>

                            <form method="POST" class="mb-3 border p-3 rounded bg-light">
                                <input type="hidden" name="action" value="add_packaging">

                                <div class="mb-2">
                                    <label class="form-label fw-bold small">Seleccionar Empaque:</label>
                                    <select name="packaging_raw_id" class="form-select form-select-sm select2" required>
                                        <?php foreach ($rawMaterials as $r):
                                            if ($r['category'] !== 'packaging')
                                                continue;
                                            ?>
                                            <option value="<?= $r['id'] ?>"><?= $r['name'] ?>
                                                (<?= floatval($r['stock_quantity']) ?>     <?= $r['unit'] ?> disponibles)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label fw-bold small">Cantidad a descontar por venta:</label>
                                    <input type="number" step="0.0001" name="packaging_qty"
                                        class="form-control form-control-sm" value="1" required>
                                </div>

                                <button type="submit" class="btn btn-success btn-sm w-100 fw-bold">
                                    <i class="fa fa-plus-circle"></i> Agregar Empaque
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
                    <div>
                        <button class="btn btn-sm btn-outline-primary me-2" onclick="openCopyModal('recipe')"
                            title="Copiar receta a otro producto">
                            <i class="fa fa-copy"></i> Copiar a...
                        </button>
                        <span class="badge bg-info text-dark">Costo Base: $<?= number_format($totalCost, 2) ?></span>
                    </div>
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
                            <?php foreach ($components as $c):
                                // FILTRO: No mostrar empaques en la pestaña de Receta Base
                                // Si es raw material y es categoría packaging, saltar.
                                if ($c['component_type'] == 'raw' && ($c['item_category'] == 'packaging' || in_array($c['component_id'], array_column($productPackaging, 'raw_material_id')))) {
                                    continue;
                                }
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($c['item_name']) ?></td>
                                    <td>
                                        <?php if ($c['component_type'] == 'raw')
                                            echo '<span class="badge bg-secondary">Insumo</span>'; ?>
                                        <?php if ($c['component_type'] == 'manufactured')
                                            echo '<span class="badge bg-warning text-dark">Cocina</span>'; ?>
                                        <?php if ($c['component_type'] == 'product')
                                            echo '<span class="badge bg-primary">Producto</span>'; ?>
                                    </td>
                                    <td class="fw-bold"><?= floatval($c['quantity']) ?>     <?= $c['item_unit'] ?></td>
                                    <td>$<?= number_format($c['quantity'] * $c['item_cost'], 3) ?></td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <button class="btn btn-sm text-warning p-0 me-2"
                                                onclick="editComponent(<?= $c['id'] ?>, '<?= addslashes($c['item_name']) ?>', <?= floatval($c['quantity']) ?>)"
                                                title="Editar cantidad">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display:inline;"
                                                onsubmit="return confirmDelete(event, this, '¿Quitar este ingrediente?')">
                                                <input type="hidden" name="action" value="remove_component">
                                                <input type="hidden" name="component_row_id" value="<?= $c['id'] ?>">
                                                <button class="btn btn-sm text-danger p-0"><i
                                                        class="fa fa-times"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($components)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-3 text-muted">No hay ingredientes definidos.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card shadow border-warning">
                <div class="card-header bg-warning text-dark fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="fa fa-list-ul"></i> Lista de Extras Válidos para el Cliente</span>
                    <button class="btn btn-sm btn-dark" onclick="openCopyModal('extras')"
                        title="Copiar extras a otro producto">
                        <i class="fa fa-copy"></i> Copiar a...
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0 small">
                        <thead>
                            <tr>
                                <th>Ingrediente</th>
                                <th>Descuenta</th>
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
                                    <td class="fw-bold">
                                        <?= floatval($ve['quantity_required']) ?> <small class="text-muted">insumo</small>
                                    </td>
                                    <td class="fw-bold text-success">
                                        $<?= number_format((float) $ve['price_override'], 2) ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-warning py-0 me-1"
                                                onclick="editExtra(<?= $ve['id'] ?>, '<?= addslashes($ve['name']) ?>', <?= floatval($ve['price_override']) ?>, <?= floatval($ve['quantity_required']) ?>)"
                                                title="Editar Extra">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display:inline;"
                                                onsubmit="return confirmDelete(event, this, '¿Quitar este extra?')">
                                                <input type="hidden" name="action" value="remove_valid_extra">
                                                <input type="hidden" name="extra_row_id" value="<?= $ve['id'] ?>">
                                                <button class="btn btn-sm btn-outline-danger py-0"
                                                    title="Quitar de la lista">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($validExtras)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-3 text-muted">No hay extras configurados. El
                                        cliente no podrá agregar nada adicional.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card shadow border-success mt-4">
                <div
                    class="card-header bg-success text-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="fa fa-box"></i> Empaque Necesario por Venta</span>
                    <button class="btn btn-sm btn-outline-light" onclick="openCopyModal('packaging')"
                        title="Copiar empaque a otro producto">
                        <i class="fa fa-copy"></i> Copiar a...
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0 small">
                        <thead>
                            <tr>
                                <th>Insumo</th>
                                <th>Cantidad</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productPackaging as $pp): ?>
                                <tr>
                                    <td><?= htmlspecialchars($pp['name']) ?></td>
                                    <td class="fw-bold"><?= floatval($pp['quantity']) ?>     <?= $pp['unit'] ?></td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-success py-0 me-1"
                                                onclick="editPackaging(<?= $pp['id'] ?>, '<?= addslashes($pp['name']) ?>', <?= floatval($pp['quantity']) ?>)"
                                                title="Editar">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display:inline;"
                                                onsubmit="return confirmDelete(event, this, '¿Quitar este empaque?')">
                                                <input type="hidden" name="action" value="remove_packaging">
                                                <input type="hidden" name="packaging_row_id" value="<?= $pp['id'] ?>">
                                                <button class="btn btn-sm btn-outline-danger py-0">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($productPackaging)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-3 text-muted">No se ha definido empaque para este
                                        producto.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card shadow border-info mt-4">
                <div class="card-header bg-info text-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="fa fa-layer-group"></i> Opciones de Contornos Configuradas</span>
                    <button class="btn btn-sm btn-outline-light" onclick="openCopyModal('sides')"
                        title="Copiar contornos a otro producto">
                        <i class="fa fa-copy"></i> Copiar a...
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0 small">
                        <thead>
                            <tr>
                                <th>Opción</th>
                                <th>Descuenta</th>
                                <th>Extra ($)</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($validSides as $vs): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($vs['item_name']) ?>
                                        <span class="badge bg-light text-dark border"><?= $vs['component_type'] ?></span>
                                    </td>
                                    <td class="fw-bold">
                                        <?= floatval($vs['quantity']) ?>     <?= $vs['item_unit'] ?>
                                    </td>
                                    <td class="fw-bold text-info">
                                        + $<?= number_format((float) $vs['price_override'], 2) ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-info py-0 me-1"
                                                onclick="editSide(<?= $vs['id'] ?>, '<?= addslashes($vs['item_name']) ?>', <?= floatval($vs['quantity']) ?>, <?= floatval($vs['price_override']) ?>)"
                                                title="Editar">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display:inline;"
                                                onsubmit="return confirmDelete(event, this, '¿Eliminar esta opción?')">
                                                <input type="hidden" name="action" value="remove_valid_side">
                                                <input type="hidden" name="side_row_id" value="<?= $vs['id'] ?>">
                                                <button class="btn btn-sm btn-outline-danger py-0">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($validSides)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">No hay contornos definidos.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>

<!-- Modales para Copiado -->
<div class="modal fade" id="modalCopyConfig" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Copiar Configuración</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="copy_config">
                <input type="hidden" name="copy_type" id="copyTypeInput">

                <p id="copyTextHint" class="small text-muted mb-3"></p>

                <div class="mb-3">
                    <label class="form-label fw-bold">Producto de Destino:</label>
                    <select name="target_product_id" class="form-select select2-modal" style="width: 100%;" required>
                        <option value="">-- Seleccionar Producto --</option>
                        <?php foreach ($allProducts as $p):
                            if ($p['id'] == $productId)
                                continue; // No copiar a sí mismo
                            ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> ($<?= $p['price_usd'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="alert alert-warning small">
                    <i class="fa fa-exclamation-triangle"></i> Los elementos se añadirán al producto de destino. Si ya
                    existen, se actualizarán.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="submitCopyForm()">Iniciar Copiado</button>
            </div>
        </form>
    </div>
</div>

<!-- Modales para Edición -->
<div class="modal fade" id="modalEditComponent" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Editar Cantidad: <span id="editCompName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="update_component">
                <input type="hidden" name="component_row_id" id="editCompRowId">
                <div class="mb-3">
                    <label class="form-label">Nueva Cantidad:</label>
                    <input type="number" step="0.000001" name="quantity" id="editCompQty" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-warning">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEditExtra" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Editar Extra: <span id="editExtraName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="update_valid_extra">
                <input type="hidden" name="extra_row_id" id="editExtraRowId">

                <div class="mb-3">
                    <label class="form-label">Precio de Venta ($):</label>
                    <input type="number" step="0.01" name="price_override" id="editExtraPrice" class="form-control"
                        required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Cantidad a Descontar de Insumo:</label>
                    <input type="number" step="0.000001" name="quantity_required" id="editExtraQty" class="form-control"
                        required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-warning">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEditSide" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Editar Opción: <span id="editSideName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="update_valid_side">
                <input type="hidden" name="side_row_id" id="editSideRowId">

                <div class="mb-3">
                    <label class="form-label">Cantidad a Descontar:</label>
                    <input type="number" step="0.000001" name="side_qty" id="editSideQty" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Cargo Adicional ($):</label>
                    <input type="number" step="0.01" name="side_price" id="editSidePrice" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-info">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEditPackaging" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Editar Empaque: <span id="editPackName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="update_packaging">
                <input type="hidden" name="packaging_row_id" id="editPackRowId">

                <div class="mb-3">
                    <label class="form-label">Cantidad a Descontar:</label>
                    <input type="number" step="0.0001" name="packaging_qty" id="editPackQty" class="form-control"
                        required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Función Universal de Confirmación para Eliminación
    function confirmDelete(event, form, message) {
        event.preventDefault(); // Detener envío automático
        
        Swal.fire({
            title: '¿Estás seguro?',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
        return false;
    }

    // Función para el copiado
    function submitCopyForm() {
        // Obtenemos el form dentro del modal
        const form = document.querySelector('#modalCopyConfig form');
        
        Swal.fire({
            title: '¿Confirmar Copiado?',
            text: "Se sobreescribirán configuraciones en el destino si ya existen.",
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, copiar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }

    function editComponent(id, name, qty) {
        document.getElementById('editCompRowId').value = id;
        document.getElementById('editCompName').textContent = name;
        document.getElementById('editCompQty').value = qty;
        new bootstrap.Modal(document.getElementById('modalEditComponent')).show();
    }

    function editExtra(id, name, price, qty) {
        document.getElementById('editExtraRowId').value = id;
        document.getElementById('editExtraName').textContent = name;
        document.getElementById('editExtraPrice').value = price;
        document.getElementById('editExtraQty').value = qty;
        new bootstrap.Modal(document.getElementById('modalEditExtra')).show();
    }

    function editSide(id, name, qty, price) {
        document.getElementById('editSideRowId').value = id;
        document.getElementById('editSideName').textContent = name;
        document.getElementById('editSideQty').value = qty;
        document.getElementById('editSidePrice').value = price;
        new bootstrap.Modal(document.getElementById('modalEditSide')).show();
    }

    function editPackaging(id, name, qty) {
        document.getElementById('editPackRowId').value = id;
        document.getElementById('editPackName').textContent = name;
        document.getElementById('editPackQty').value = qty;
        new bootstrap.Modal(document.getElementById('modalEditPackaging')).show();
    }

    function openCopyModal(type) {
        document.getElementById('copyTypeInput').value = type;
        const hints = {
            'recipe': 'Vas a copiar los <strong>ingredientes de la receta base</strong> a otro producto.',
            'extras': 'Vas a copiar la lista de <strong>extras autorizados</strong> a otro producto.',
            'sides': 'Vas a copiar las <strong>opciones de contornos</strong> a otro producto.',
            'packaging': 'Vas a copiar la configuración de <strong>empaque</strong> a otro producto.'
        };
        document.getElementById('copyTextHint').innerHTML = hints[type] || 'Iniciando copiado...';

        // Abrir el modal primero
        const modalEl = document.getElementById('modalCopyConfig');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        // Inicializar select2 después de que el modal se muestre para asegurar que el foco funcione
        modalEl.addEventListener('shown.bs.modal', function () {
            if (typeof $.fn.select2 !== 'undefined') {
                $('.select2-modal').select2({
                    dropdownParent: $('#modalCopyConfig'),
                    theme: 'bootstrap-5' // Opcional, si tienes el tema
                });
            }
        }, { once: true });
    }

    document.getElementById('side_type_select')?.addEventListener('change', function () {
        const type = this.value;
        const select = document.getElementById('side_component_select');

        // Ocultar todos
        Array.from(select.options).forEach(opt => opt.style.display = 'none');

        // Mostrar correspondientes
        if (type === 'raw') {
            select.querySelectorAll('.opt-raw').forEach(opt => opt.style.display = 'block');
        } else if (type === 'manufactured') {
            select.querySelectorAll('.opt-manuf').forEach(opt => opt.style.display = 'block');
        } else if (type === 'product') {
            select.querySelectorAll('.opt-prod').forEach(opt => opt.style.display = 'block');
        }

        // Reset select value to first visible option
        const firstVisible = Array.from(select.options).find(opt => opt.style.display === 'block');
        if (firstVisible) select.value = firstVisible.value;
    });
</script>

<?php require_once '../templates/footer.php'; ?>