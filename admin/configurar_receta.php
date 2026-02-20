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
        $compId = $_POST['extra_component_id'];
        $type = $_POST['extra_component_type'] ?? 'raw';
        // Si el precio viene vacío, mandamos NULL (usará precio base en lógica futura)
        // O mandamos el precio que el admin escriba.
        $price = !empty($_POST['extra_price']) ? $_POST['extra_price'] : 1.00; // Default $1 si no escribe nada

        $qty = !empty($_POST['extra_qty']) ? $_POST['extra_qty'] : 1.000000;

        if ($productManager->addValidExtra($productId, $compId, $price, $qty, $type)) {
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

    // 8B. ACTUALIZAR LÓGICA DE CONTORNOS (NUEVO)
    if ($action === 'update_contour_logic') {
        $productManager->updateContourLogic($productId, $_POST['contour_logic_type']);
        $product = $productManager->getProductById($productId);
        $mensaje = '<div class="alert alert-success">Lógica de descuento actualizada.</div>';
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
        } elseif ($type === 'companions') {
            $productManager->copyCompanions($productId, $toId);
            $mensaje = '<div class="alert alert-success">Lista de acompañantes (y sus recetas) copiada correctamente.</div>';
        }
    }

    // 16. AGREGAR ACOMPAÑANTE (NUEVO)
    if ($action === 'add_companion') {
        $productManager->addCompanion($productId, $_POST['companion_id'], $_POST['companion_qty'], $_POST['companion_price']);
        $mensaje = '<div class="alert alert-success">Acompañante agregado.</div>';
    }

    // 17. ELIMINAR ACOMPAÑANTE (NUEVO)
    if ($action === 'remove_companion') {
        $productManager->removeCompanion($_POST['companion_row_id']);
        $mensaje = '<div class="alert alert-warning">Acompañante eliminado.</div>';
    }

    // 18. ACTUALIZAR ACOMPAÑANTE (NUEVO)
    if ($action === 'update_companion') {
        $productManager->updateCompanion($productId, $_POST['companion_id'], $_POST['companion_qty'], $_POST['companion_price']);
        $mensaje = '<div class="alert alert-success">Acompañante actualizado.</div>';
    }

    // 19. GUARDAR RECETA PERSONALIZADA ACOMPAÑANTE (NUEVO)
    if ($action === 'save_companion_recipe') {
        $compId = $_POST['companion_row_id'];
        $componentsJSON = $_POST['custom_recipe_data']; // JSON String
        $components = json_decode($componentsJSON, true);

        if ($productManager->updateCompanionRecipe($compId, $components)) {
            $mensaje = '<div class="alert alert-success">Receta personalizada guardada correctamente.</div>';
        } else {
            $mensaje = '<div class="alert alert-danger">Error al guardar receta personalizada.</div>';
        }
    }

    // 20. GUARDAR RECETA PERSONALIZADA COMPONENTE DE COMBO (NUEVO)
    // 20. GUARDAR RECETA PERSONALIZADA COMPONENTE DE COMBO (NUEVO)
    if ($action === 'save_component_override') {
        $rowId = $_POST['component_row_id'];
        
        // Receta
        $ingredientsJSON = $_POST['override_data'];
        $ingredients = json_decode($ingredientsJSON, true);
        
        // Contornos
        $sidesJSON = $_POST['override_sides_data'];
        $sides = json_decode($sidesJSON, true);

        $res1 = $productManager->updateComponentOverrides($rowId, $ingredients);
        $res2 = $productManager->updateComponentSideOverrides($rowId, $sides);

        if ($res1 && $res2) {
            $mensaje = '<div class="alert alert-success">Personalización de componente para el combo guardada correctamente.</div>';
        } else {
            $mensaje = '<div class="alert alert-danger">Error al guardar personalización del componente.</div>';
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
// 5. Empaques Configurados (NUEVO)
$productPackaging = $productManager->getProductPackaging($productId);

// 6. Acompañantes (NUEVO)
$productCompanions = $productManager->getCompanions($productId);

// Calcular Costo Base Total (Incluye Receta, Contornos y Empaque) con desglose
$costBreakdown = $productManager->calculateProductCost($productId, 0, true);
$totalCost = $costBreakdown['total'];

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
                    <form method="POST" class="mb-3 border-bottom pb-2">
                        <input type="hidden" name="action" value="update_max_sides">
                        <label class="form-label fw-bold">Límite de Contornos / Opciones:</label>
                        <div class="input-group mb-2">
                            <input type="number" name="max_sides" class="form-control"
                                value="<?= (int) $product['max_sides'] ?>" min="0">
                            <button class="btn btn-primary" type="submit">Establecer Límite</button>
                        </div>
                        <small class="text-muted d-block mb-2">
                            Esto define cuántos contornos puede elegir el cliente.
                        </small>
                    </form>

                    <!-- Lógica de Descuento (NUEVO LUGAR) -->
                    <form method="POST" class="mb-3">
                        <input type="hidden" name="action" value="update_contour_logic">
                        <label class="form-label fw-bold small"><i class="fa fa-cogs text-primary"></i> Lógica de
                            Descuento de Inventario:</label>
                        <select name="contour_logic_type" class="form-select form-select-sm mb-2"
                            onchange="this.form.submit()">
                            <option value="standard" <?= ($product['contour_logic_type'] ?? 'standard') === 'standard' ? 'selected' : '' ?>>Estándar (Descuento exacto)</option>
                            <option value="proportional" <?= ($product['contour_logic_type'] ?? 'standard') === 'proportional' ? 'selected' : '' ?>>Proporcional (Dividido entre
                                seleccionados)</option>
                        </select>
                        <div class="form-text small text-muted">Use "Proporcional" si el cliente elige 2 o más contornos
                            y la porción se divide entre ellos.</div>
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
                        <li class="nav-item">
                            <button class="nav-link text-primary fw-bold" data-bs-toggle="tab"
                                data-bs-target="#companions">🤝 Acompañantes</button>
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
                                    <label class="form-label fw-bold small">Tipo de Componente:</label>
                                    <select name="extra_component_type" id="extra_type_select"
                                        class="form-select form-select-sm" required onchange="toggleExtraInputs()">
                                        <option value="raw">Materia Prima</option>
                                        <option value="manufactured">Cocina (Preparado)</option>
                                        <option value="product">Producto</option>
                                    </select>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label fw-bold small">Seleccionar:</label>
                                    <select name="extra_component_id" id="extra_component_select"
                                        class="form-select form-select-sm select2" required>
                                        <?php foreach ($rawMaterials as $r):
                                            if ($r['category'] == 'packaging')
                                                continue;
                                            ?>
                                            <option value="<?= $r['id'] ?>" class="opt-raw-extra"><?= $r['name'] ?>
                                                (<?= $r['unit'] ?>)</option>
                                        <?php endforeach; ?>
                                        <?php foreach ($manufactured as $m): ?>
                                            <option value="<?= $m['id'] ?>" class="opt-manuf-extra" style="display:none;">
                                                <?= $m['name'] ?> (<?= $m['unit'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                        <?php foreach ($allProducts as $p): ?>
                                            <option value="<?= $p['id'] ?>" class="opt-prod-extra" style="display:none;">
                                                <?= $p['name'] ?>
                                            </option>
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

                        <div class="tab-pane fade" id="companions">
                            <div class="alert alert-primary small">
                                <i class="fa fa-info-circle"></i> Aquí defines productos que se <strong>agregan
                                    automáticamente</strong> al carrito junto con este producto (Ej: "Combo Hamburguesa"
                                trae "Refresco" y "Papas").
                            </div>

                            <form method="POST" class="mb-3 border p-3 rounded bg-light">
                                <input type="hidden" name="action" value="add_companion">

                                <div class="mb-2">
                                    <label class="form-label fw-bold small">Seleccionar Producto Acompañante:</label>
                                    <select name="companion_id" class="form-select form-select-sm select2" required>
                                        <?php foreach ($allProducts as $p):
                                            if ($p['id'] == $productId)
                                                continue;
                                            ?>
                                            <option value="<?= $p['id'] ?>">
                                                <?= $p['name'] ?> ($<?= $p['price_usd'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-6 mb-2">
                                        <label class="form-label fw-bold small">Cantidad:</label>
                                        <input type="number" step="0.0001" name="companion_qty"
                                            class="form-control form-control-sm" value="1" required>
                                    </div>
                                    <div class="col-6 mb-2">
                                        <label class="form-label fw-bold small">Precio Override (Opcional):</label>
                                        <input type="number" step="0.01" name="companion_price"
                                            class="form-control form-control-sm"
                                            placeholder="Dejar vacío para precio oficial">
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">
                                    <i class="fa fa-plus-circle"></i> Agregar Acompañante
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>

            <div class="card shadow border-primary mt-4">
                <div
                    class="card-header bg-primary text-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="fa fa-handshake"></i> Lista de Acompañantes</span>
                    <button type="button" class="btn btn-sm btn-light text-primary border"
                        onclick="openCopyModal('companions')">
                        <i class="fa fa-copy"></i> Copiar A...
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0 small">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio ($)</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productCompanions as $pc): ?>
                                <tr>
                                    <td><?= htmlspecialchars($pc['name']) ?></td>
                                    <td class="fw-bold"><?= floatval($pc['quantity']) ?></td>
                                    <td class="fw-bold text-success">
                                        <?php if ($pc['price_override'] !== null): ?>
                                            $<?= number_format((float) $pc['price_override'], 2) ?>
                                            <span class="badge bg-warning text-dark"
                                                title="Precio original sobreescrito">*</span>
                                        <?php else: ?>
                                            $<?= number_format((float) $pc['base_price'], 2) ?>
                                            <small class="text-muted">(Base)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-info py-0 me-1" 
                                                    title="Personalizar Receta para este combo"
                                                    onclick="openCompanionRecipeModal(<?= $pc['id'] ?>, '<?= addslashes($pc['name']) ?>')">
                                                <i class="fa fa-flask"></i>
                                            </button>
                                            <form method="POST" style="display:inline;"
                                                onsubmit="return confirmDelete(event, this, '¿Quitar este acompañante?')">
                                                <input type="hidden" name="action" value="remove_companion">
                                                <input type="hidden" name="companion_row_id" value="<?= $pc['id'] ?>">
                                                <button class="btn btn-sm btn-outline-danger py-0">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($productCompanions)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">No hay acompañantes definidos.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-7">

            <!-- PANEL DE ANÁLISIS DE COSTOS (NUEVO) -->
            <div class="card shadow border-info mb-4">
                <div class="card-header bg-info text-dark fw-bold">
                    <i class="fa fa-chart-pie"></i> Análisis de Costos (COGS Estimado)
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-3">
                            <div class="small text-muted">Receta Base</div>
                            <div class="fw-bold text-primary">$<?= number_format($costBreakdown['recipe'], 2) ?></div>
                        </div>
                        <div class="col-3 border-start">
                            <div class="small text-muted">Contornos (avg)</div>
                            <div class="fw-bold text-primary">$<?= number_format($costBreakdown['sides'], 2) ?></div>
                            <?php if ($product['max_sides'] > 0): ?>
                                <small class="badge bg-light text-dark border" style="font-size: 0.7em;">
                                    Lógica:
                                    <?= $product['contour_logic_type'] === 'proportional' ? 'Proporcional' : 'Estándar' ?>
                                </small>
                            <?php endif; ?>
                        </div>
                        <div class="col-3 border-start">
                            <div class="small text-muted">Empaque</div>
                            <div class="fw-bold text-primary">$<?= number_format($costBreakdown['packaging'], 2) ?>
                            </div>
                        </div>
                        <div class="col-3 border-start">
                            <div class="small text-muted">Acompañantes</div>
                            <div class="fw-bold text-primary">
                                $<?= number_format($costBreakdown['companions'] ?? 0, 2) ?>
                            </div>
                        </div>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold">COSTO TOTAL DE PRODUCCIÓN:</span>
                        <span class="h4 mb-0 text-success fw-bold">$<?= number_format($totalCost, 2) ?></span>
                    </div>
                    <?php
                    $price = floatval($product['price_usd']);
                    $profit = $price - $totalCost;
                    $margin = ($price > 0) ? ($profit / $price) * 100 : 0;
                    ?>
                    <div class="mt-2 d-flex justify-content-between align-items-center small">
                        <span class="text-muted">Margen sobre venta ($<?= number_format($price, 2) ?>):</span>
                        <span
                            class="badge <?= $margin > 20 ? 'bg-success' : ($margin > 10 ? 'bg-warning text-dark' : 'bg-danger') ?>">
                            <?= number_format($margin, 1) ?>% ($<?= number_format($profit, 2) ?>)
                        </span>
                    </div>
                </div>
            </div>

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
                                // FILTRO: en la pestaña de Receta Base
                                // Solo ocultar si está específicamente asignado como empaque del producto
                                if ($c['component_type'] == 'raw' && in_array($c['component_id'], array_column($productPackaging, 'raw_material_id'))) {
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
                                            <?php if ($product['product_type'] == 'compound' && $c['component_type'] == 'product'): ?>
                                                <button type="button" class="btn btn-sm text-info p-0 me-2" 
                                                        title="Personalizar receta de este componente en este combo"
                                                        onclick="openComponentOverrideModal(<?= $c['id'] ?>, '<?= addslashes($c['item_name']) ?>')">
                                                    <i class="fa fa-flask"></i>
                                                </button>
                                            <?php endif; ?>
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

                                        <span class="badge bg-light text-dark border small">
                                            <?= $ve['component_type'] == 'raw' ? 'Insumo' : ($ve['component_type'] == 'product' ? 'Producto' : 'Cocina') ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold">
                                        <?= floatval($ve['quantity_required']) ?> <small
                                            class="text-muted">descuento</small>
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
    function toggleExtraInputs() {
        const type = document.getElementById('extra_type_select').value;
        const select = document.getElementById('extra_component_select');
        const optsRaw = select.querySelectorAll('.opt-raw-extra');
        const optsManuf = select.querySelectorAll('.opt-manuf-extra');
        const optsProd = select.querySelectorAll('.opt-prod-extra');

        if (type === 'raw') {
            optsRaw.forEach(o => o.style.display = '');
            optsManuf.forEach(o => o.style.display = 'none');
            optsProd.forEach(o => o.style.display = 'none');
            // Seleccionar el primero visible si el actual no lo es
            if (select.selectedOptions.length > 0 && select.selectedOptions[0].style.display === 'none') {
                if (optsRaw.length > 0) select.value = optsRaw[0].value;
            }
        } else if (type === 'manufactured') {
            optsRaw.forEach(o => o.style.display = 'none');
            optsManuf.forEach(o => o.style.display = '');
            optsProd.forEach(o => o.style.display = 'none');
            if (select.selectedOptions.length > 0 && select.selectedOptions[0].style.display === 'none') {
                if (optsManuf.length > 0) select.value = optsManuf[0].value;
            }
        } else if (type === 'product') {
            optsRaw.forEach(o => o.style.display = 'none');
            optsManuf.forEach(o => o.style.display = 'none');
            optsProd.forEach(o => o.style.display = '');
            if (select.selectedOptions.length > 0 && select.selectedOptions[0].style.display === 'none') {
                if (optsProd.length > 0) select.value = optsProd[0].value;
            }
        }

        // Refrescar Select2 si existe
        if (typeof $ !== 'undefined' && $(select).data('select2')) {
            $(select).trigger('change');
        }
    }

    // Ejecutar al cargar para inicializar estado
    document.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('extra_type_select')) {
            toggleExtraInputs();
        }
    });

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
        console.log('openCopyModal called with:', type);
        try {
            document.getElementById('copyTypeInput').value = type;
            const hints = {
                'recipe': 'Vas a copiar los <strong>ingredientes de la receta base</strong> a otro producto.',
                'extras': 'Vas a copiar la lista de <strong>extras autorizados</strong> a otro producto.',
                'sides': 'Vas a copiar las <strong>opciones de contornos</strong> a otro producto.',
                'packaging': 'Vas a copiar la configuración de <strong>empaque</strong> a otro producto.',
                'companions': 'Vas a copiar la <strong>lista de acompañantes y sus recetas personalizadas</strong> a otro producto.'
            };
            document.getElementById('copyTextHint').innerHTML = hints[type] || 'Iniciando copiado...';

            // Abrir el modal primero
            const modalEl = document.getElementById('modalCopyConfig');
            if (!modalEl) {
                alert('Error: Modal de copiado no encontrado en el DOM.');
                return;
            }
            const modal = new bootstrap.Modal(modalEl);
            modal.show();

            // Inicializar select2 después de que el modal se muestre
            modalEl.addEventListener('shown.bs.modal', function () {
                if (typeof $.fn.select2 !== 'undefined') {
                    $('.select2-modal').select2({
                        dropdownParent: $('#modalCopyConfig'),
                        theme: 'bootstrap-5'
                    });
                }
            }, { once: true });
        } catch (e) {
            console.error(e);
            alert('Error al abrir modal: ' + e.message);
        }
    }

    // Safer event listener
    const sideTypeSelect = document.getElementById('side_type_select');
    if (sideTypeSelect) {
        sideTypeSelect.addEventListener('change', function () {
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
    }
</script>

    <!-- MODAL DE PERSONALIZACIÓN DE RECETA DE ACOMPAÑANTE -->
    <div class="modal fade" id="modalCompanionRecipe" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-dark">
                    <h5 class="modal-title"><i class="fa fa-flask"></i> Personalizar Receta del Acompañante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="modal_companion_row_id">
                    <h6 id="modal_companion_name" class="fw-bold mb-3"></h6>

                    <div class="alert alert-warning small">
                        <i class="fa fa-exclamation-triangle"></i>
                        Esta receta personalizada aplicará <strong>SOLO</strong> cuando este producto se venda como
                        parte de
                        este combo. No afecta al producto original.
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">Agregar Ingrediente Extra:</label>
                            <select id="new_comp_select" class="form-select form-select-sm select2-modal">
                                <?php foreach ($rawMaterials as $r): ?>
                                    <option value="<?= $r['id'] ?>" data-type="raw" data-unit="<?= $r['unit'] ?>">
                                        <?= htmlspecialchars($r['name']) ?> (<?= $r['unit'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Cantidad:</label>
                            <input type="number" id="new_comp_qty" class="form-control form-control-sm" step="0.0001"
                                placeholder="0.00">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-sm btn-success w-100" onclick="addNewComponentRow()">
                                <i class="fa fa-plus"></i> Agregar
                            </button>
                        </div>
                    </div>

                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Ingrediente</th>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="companion_recipe_tbody">
                            <!-- JS populated -->
                        </tbody>
                    </table>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST">
                        <input type="hidden" name="action" value="save_companion_recipe">
                        <input type="hidden" name="companion_row_id" id="form_companion_row_id">
                        <input type="hidden" name="custom_recipe_data" id="form_custom_recipe_data">
                        <button type="button" onclick="submitCustomRecipe()" class="btn btn-primary">
                            <i class="fa fa-save"></i> Guardar Receta Personalizada
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Init select2 inside modal
            $('#new_comp_select').select2({
                dropdownParent: $('#modalCompanionRecipe'),
                width: '100%'
            });
        });

        // Store current recipe in memory
        let currentRecipe = [];

        function openCompanionRecipeModal(rowId, name) {
            $('#modal_companion_row_id').val(rowId);
            $('#form_companion_row_id').val(rowId);
            $('#modal_companion_name').text('Editando: ' + name);

            $('#companion_recipe_tbody').html('<tr><td colspan="4" class="text-center">Cargando...</td></tr>');

            $.ajax({
                url: '../ajax/get_companion_recipe.php',
                method: 'GET',
                data: {
                    id: rowId
                },
                success: function(response) {
                    try {
                        // Si jQuery ya parseó el JSON ver el header, response será un objeto.
                        // Si no, será un string. Manejamos ambos casos.
                        const data = (typeof response === 'string') ? JSON.parse(response) : response;
                        
                        console.log("Datos recibidos:", data); // Para depuración

                        if (data.error) {
                            alert('Error del servidor: ' + data.error);
                            return;
                        }

                        currentRecipe = data.components || [];
                        renderRecipeTable();
                        new bootstrap.Modal(document.getElementById('modalCompanionRecipe')).show();
                    } catch (e) {
                        console.error(e);
                        alert('Error al procesar datos: ' + e.message);
                    }
                },
                error: function() {
                    alert('Error de conexión');
                }
            });
        }

        function renderRecipeTable() {
            const tbody = $('#companion_recipe_tbody');
            tbody.empty();

            if (currentRecipe.length === 0) {
                tbody.html('<tr><td colspan="4" class="text-center text-muted">Sin ingredientes definidos (Usará receta por defecto si existe, o nada)</td></tr>');
                return;
            }

            currentRecipe.forEach((comp, index) => {
                let badge = comp.component_type === 'raw' ? '<span class="badge bg-secondary">Insumo</span>' :
                    (comp.component_type === 'manufactured' ? '<span class="badge bg-warning text-dark">Cocina</span>' : '<span class="badge bg-primary">Producto</span>');

                let row = `
                <tr>
                    <td>${comp.item_name}</td>
                    <td>${badge}</td>
                    <td>
                        <input type="number" step="0.0001" class="form-control form-control-sm" 
                               value="${parseFloat(comp.quantity)}" 
                               onchange="updateRowQty(${index}, this.value)">
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-danger p-0 px-1" onclick="removeRow(${index})">
                            <i class="fa fa-times"></i>
                        </button>
                    </td>
                </tr>
            `;
                tbody.append(row);
            });
        }

        function updateRowQty(index, val) {
            currentRecipe[index].quantity = val;
        }

        function removeRow(index) {
            if (confirm('¿Quitar ingrediente?')) {
                currentRecipe.splice(index, 1);
                renderRecipeTable();
            }
        }

        function addNewComponentRow() {
            const id = $('#new_comp_select').val();
            const text = $('#new_comp_select option:selected').text();
            const type = $('#new_comp_select option:selected').data('type');
            const qty = $('#new_comp_qty').val();

            if (!id || qty <= 0) {
                alert('Seleccione ingrediente y cantidad válida');
                return;
            }

            // Check if exists
            const exists = currentRecipe.find(r => r.component_id == id && r.component_type == type);
            if (exists) {
                alert('El ingrediente ya está en la lista');
                return;
            }

            currentRecipe.push({
                component_id: id,
                component_type: type,
                item_name: text, // Visual only
                quantity: qty,
                // Normalized keys for saving
                id: id,
                type: type,
                qty: qty
            });

            renderRecipeTable();
            $('#new_comp_qty').val('');
        }

        function submitCustomRecipe() {
            // Prepare data for PHP
            // PHP expects: array of {id, type, qty}
            const dataToSave = currentRecipe.map(c => ({
                id: c.component_id || c.id, // Handle DB field vs local field
                type: c.component_type || c.type,
                qty: c.quantity || c.qty
            }));

            $('#form_custom_recipe_data').val(JSON.stringify(dataToSave));
            $('#form_custom_recipe_data').closest('form').submit();
        }
    </script>

    <!-- MODAL DE PERSONALIZACIÓN DE RECETA DE COMPONENTE DE COMBO -->
    <div class="modal fade" id="modalComponentOverride" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="fa fa-flask"></i> Personalizar Componente en Combo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="override_row_id">
                    <h6 id="override_comp_name" class="fw-bold mb-3"></h6>

                    <!-- Nav Tabs -->
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-recipe" type="button">
                                <i class="fa fa-list"></i> Receta Personalizada
                                <span id="badge-recipe-fallback" class="badge bg-warning text-dark d-none" title="Usando valores por defecto">Base</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-sides" type="button">
                                <i class="fa fa-utensils"></i> Contornos Permitidos
                                <span id="badge-sides-fallback" class="badge bg-warning text-dark d-none" title="Usando valores por defecto">Base</span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- TAB: RECETA -->
                        <div class="tab-pane fade show active" id="tab-recipe">
                            <div class="row g-2 mb-3">
                                <div class="col-md-5">
                                    <select id="new_override_select" class="form-select form-select-sm select2-modal">
                                        <?php foreach ($rawMaterials as $r): ?>
                                            <option value="<?= $r['id'] ?>" data-type="raw" data-unit="<?= $r['unit'] ?>">
                                                <?= htmlspecialchars($r['name']) ?> (<?= $r['unit'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                        <?php foreach ($manufactured as $m): ?>
                                            <option value="<?= $m['id'] ?>" data-type="manufactured" data-unit="<?= $m['unit'] ?>">
                                                [COCINA] <?= htmlspecialchars($m['name']) ?> (<?= $m['unit'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" id="new_override_qty" class="form-control form-control-sm" value="1" step="0.0001">
                                </div>
                                <div class="col-md-4">
                                    <button type="button" class="btn btn-sm btn-primary w-100" onclick="addOverrideToList('recipe')">
                                        <i class="fa fa-plus"></i> Añadir Ingrediente
                                    </button>
                                </div>
                            </div>
                            <table class="table table-sm table-bordered">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Ingrediente</th>
                                        <th>Cantidad</th>
                                        <th>Unidad</th>
                                        <th style="width: 40px"></th>
                                    </tr>
                                </thead>
                                <tbody id="override_list_body"></tbody>
                            </table>
                        </div>

                        <!-- TAB: CONTORNOS -->
                        <div class="tab-pane fade" id="tab-sides">
                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <select id="new_side_select" class="form-select form-select-sm select2-modal">
                                        <?php foreach ($rawMaterials as $r): ?>
                                            <option value="<?= $r['id'] ?>" data-type="raw" data-unit="<?= $r['unit'] ?>">
                                                <?= htmlspecialchars($r['name']) ?> (<?= $r['unit'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                        <?php foreach ($manufactured as $m): ?>
                                            <option value="<?= $m['id'] ?>" data-type="manufactured" data-unit="<?= $m['unit'] ?>">
                                                [COCINA] <?= htmlspecialchars($m['name']) ?> (<?= $m['unit'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-sm btn-info w-100 text-white" onclick="addOverrideToList('sides')">
                                        <i class="fa fa-plus"></i> Añadir Contorno
                                    </button>
                                </div>
                            </div>
                            <table class="table table-sm table-bordered">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Contorno</th>
                                        <th>Tipo</th>
                                        <th style="width: 40px"></th>
                                    </tr>
                                </thead>
                                <tbody id="override_sides_list_body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <form method="POST" id="formSaveOverride">
                        <input type="hidden" name="action" value="save_component_override">
                        <input type="hidden" name="component_row_id" id="post_override_row_id">
                        <input type="hidden" name="override_data" id="post_override_data">
                        <input type="hidden" name="override_sides_data" id="post_override_sides_data">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Guardar Cambios en Combo</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let overrideList = [];
        let overrideSidesList = [];

        async function openComponentOverrideModal(rowId, name) {
            document.getElementById('override_row_id').value = rowId;
            document.getElementById('post_override_row_id').value = rowId;
            document.getElementById('override_comp_name').innerText = name;
            
            // Cargar datos actuales vía AJAX
            const response = await fetch(`../ajax/get_component_overrides.php?row_id=${rowId}`);
            const data = await response.json();
            
            // Receta
            overrideList = data.recipe.map(item => ({
                id: item.ingredient_id,
                name: item.item_name,
                type: item.ingredient_type,
                qty: parseFloat(item.quantity),
                unit: item.item_unit
            }));
            
            // Contornos
            overrideSidesList = data.sides.map(item => ({
                id: item.side_id,
                name: item.item_name,
                type: item.side_type,
                qty: parseFloat(item.quantity),
                unit: item.item_unit,
                is_default: item.is_default
            }));

            // Badges fallback
            document.getElementById('badge-recipe-fallback').classList.toggle('d-none', !data.is_recipe_fallback);
            document.getElementById('badge-sides-fallback').classList.toggle('d-none', !data.is_sides_fallback);
            
            renderOverrideList();
            renderOverrideSidesList();
            
            const modal = new bootstrap.Modal(document.getElementById('modalComponentOverride'));
            modal.show();
        }

        function addOverrideToList(target) {
            if (target === 'recipe') {
                const select = document.getElementById('new_override_select');
                const selectedOpt = select.options[select.selectedIndex];
                const qty = parseFloat(document.getElementById('new_override_qty').value);
                if (qty <= 0) return;

                overrideList.push({
                    id: select.value,
                    name: selectedOpt.text.split('(')[0].trim(),
                    type: selectedOpt.dataset.type,
                    qty: qty,
                    unit: selectedOpt.dataset.unit
                });
                document.getElementById('badge-recipe-fallback').classList.add('d-none');
                renderOverrideList();
            } else {
                const select = document.getElementById('new_side_select');
                const selectedOpt = select.options[select.selectedIndex];

                overrideSidesList.push({
                    id: select.value,
                    name: selectedOpt.text.split('(')[0].trim(),
                    type: selectedOpt.dataset.type,
                    qty: 1,
                    unit: selectedOpt.dataset.unit,
                    is_default: 0
                });
                document.getElementById('badge-sides-fallback').classList.add('d-none');
                renderOverrideSidesList();
            }
        }

        function removeOverrideItem(index, target) {
            if (target === 'recipe') {
                overrideList.splice(index, 1);
                document.getElementById('badge-recipe-fallback').classList.add('d-none');
                renderOverrideList();
            } else {
                overrideSidesList.splice(index, 1);
                document.getElementById('badge-sides-fallback').classList.add('d-none');
                renderOverrideSidesList();
            }
        }

        function renderOverrideList() {
            const tbody = document.getElementById('override_list_body');
            tbody.innerHTML = '';
            overrideList.forEach((item, index) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.name} <span class="badge bg-light text-dark border">${item.type}</span></td>
                    <td>${item.qty}</td>
                    <td>${item.unit}</td>
                    <td>
                        <button type="button" class="btn btn-sm text-danger" onclick="removeOverrideItem(${index}, 'recipe')">
                            <i class="fa fa-times"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            document.getElementById('post_override_data').value = JSON.stringify(overrideList);
        }

        function renderOverrideSidesList() {
            const tbody = document.getElementById('override_sides_list_body');
            tbody.innerHTML = '';
            overrideSidesList.forEach((item, index) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.name}</td>
                    <td><span class="badge bg-light text-dark border">${item.type}</span></td>
                    <td>
                        <button type="button" class="btn btn-sm text-danger" onclick="removeOverrideItem(${index}, 'sides')">
                            <i class="fa fa-times"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            document.getElementById('post_override_sides_data').value = JSON.stringify(overrideSidesList);
        }
    </script>

<?php require_once '../templates/footer.php'; ?>