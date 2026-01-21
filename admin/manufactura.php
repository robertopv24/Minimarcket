<?php
require_once '../templates/autoload.php';
session_start();
// (Validar admin aquí como en los otros archivos)

$mensaje = '';

// ACCIONES
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    // 1. Crear Producto Manufacturado
    if ($action === 'create_product') {
        if ($productionManager->createManufacturedProduct($_POST['name'], $_POST['unit'], $_POST['min_stock'])) {
            $mensaje = '<div class="alert alert-success">Producto creado. Ahora define su receta.</div>';
        }
    }

    // 2. Agregar Ingrediente a Receta
    if ($action === 'add_ingredient') {
        if ($productionManager->addIngredientToRecipe($_POST['manuf_id'], $_POST['raw_id'], $_POST['qty'])) {
            $mensaje = '<div class="alert alert-success">Ingrediente agregado a la receta.</div>';
        }
    }

    // 3. Registrar Producción (Cocinar)
    if ($action === 'produce') {
        $res = $productionManager->registerProduction(
            $_POST['manuf_id'],
            $_POST['qty_produced'],
            $_SESSION['user_id']
        );
        if ($res === true) {
            $mensaje = '<div class="alert alert-success">¡Producción registrada! Inventario actualizado.</div>';
        } else {
            $mensaje = '<div class="alert alert-danger">' . $res . '</div>';
        }
    }

    // 4. Borrar ingrediente de receta
    if ($action === 'delete_recipe_item') {
        $productionManager->removeIngredientFromRecipe($_POST['recipe_id']);
    }

    // 5. Editar ingrediente de receta
    if ($action === 'update_recipe_item') {
        $productionManager->updateRecipeIngredientQuantity($_POST['recipe_id'], $_POST['qty']);
        $mensaje = '<div class="alert alert-success">Cantidad actualizada en la receta.</div>';
    }

    // 6. Editar Información Básica
    if ($action === 'update_info') {
        if ($productionManager->updateManufacturedProduct($_POST['manuf_id'], $_POST['name'], $_POST['unit'], $_POST['min_stock'])) {
            $mensaje = '<div class="alert alert-success">Información del producto actualizada.</div>';
        }
    }
}

// 5. Configurar Receta (desde Configurar Receta Modal)
// ... (Si hubiese mas logica)

$search = $_GET['search'] ?? '';
$products = $productionManager->searchManufacturedProducts($search);
$rawMaterials = $rawMaterialManager->getAllMaterials();

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>🏭 Gestión de Producción y Recetas</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreateProd">
            <i class="fa fa-plus"></i> Nuevo Producto de Cocina
        </button>
    </div>
    <?= $mensaje ?>

    <!-- Barra de Búsqueda -->
    <div class="card mb-4 bg-light">
        <div class="card-body py-3">
            <form method="GET" action="" class="row g-2 align-items-center">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                        <input type="text" name="search" class="form-control"
                            placeholder="Buscar producto manufacturado..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Buscar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <?php foreach ($products as $p):
            $recipe = $productionManager->getRecipe($p['id']);
            ?>
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="m-0"><?= htmlspecialchars($p['name']) ?></h5>
                        <?php
                        $isLow = $p['stock'] <= ($p['min_stock'] ?? 0);
                        $stockBadgeClass = $isLow ? 'bg-danger' : 'bg-warning text-dark';
                        ?>
                        <span class="badge <?= $stockBadgeClass ?>">Stock: <?= floatval($p['stock']) ?>
                            <?= $p['unit'] ?></span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Costo Unitario Promedio:</strong> $<?= number_format($p['unit_cost_average'], 6) ?>
                        </div>

                        <h6>📜 Receta (Para 1 <?= $p['unit'] ?>):</h6>
                        <?php if (empty($recipe)): ?>
                            <p class="text-danger small">No hay receta definida.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush mb-3 small">
                                <?php foreach ($recipe as $r): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-3">
                                        <span><?= floatval($r['quantity_required']) ?> <small
                                                class="text-muted"><?= $r['material_unit'] ?></small> de
                                            <strong><?= $r['material_name'] ?></strong></span>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm text-warning hover-scale me-1"
                                                onclick="openEditRecipeModal(<?= $r['id'] ?>, '<?= addslashes($r['material_name']) ?>', <?= floatval($r['quantity_required']) ?>)"
                                                title="Editar Cantidad">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display:inline;"
                                                onsubmit="return confirm('¿Quitar ingrediente?')">
                                                <input type="hidden" name="action" value="delete_recipe_item">
                                                <input type="hidden" name="recipe_id" value="<?= $r['id'] ?>">
                                                <button type="submit" class="btn btn-sm text-danger hover-scale" title="Eliminar"><i
                                                        class="fa fa-times"></i></button>
                                            </form>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <div class="d-grid gap-2">
                            <div class="row g-2">
                                <div class="col-6">
                                    <button class="btn btn-outline-secondary btn-sm w-100"
                                        onclick="openEditInfoModal(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name']) ?>', '<?= $p['unit'] ?>', <?= floatval($p['min_stock']) ?>)">
                                        <i class="fa fa-info-circle"></i> Info
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-outline-primary btn-sm w-100"
                                        onclick="openRecipeModal(<?= $p['id'] ?>, '<?= $p['name'] ?>', '<?= $p['unit'] ?>')">
                                        <i class="fa fa-list"></i> Receta
                                    </button>
                                </div>
                            </div>
                            <button class="btn btn-success btn-sm"
                                onclick="openProduceModal(<?= $p['id'] ?>, '<?= $p['name'] ?>', '<?= $p['unit'] ?>')"
                                <?= empty($recipe) ? 'disabled' : '' ?>>
                                <i class="fa fa-fire"></i> Registrar Producción
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="modal fade" id="modalCreateProd" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Item de Cocina</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="create_product">
                <div class="mb-3">
                    <label>Nombre (Ej: Masa Pizza, Tequeño Crudo)</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Unidad de Medida</label>
                    <select name="unit" class="form-select">
                        <option value="und">Unidades (und)</option>
                        <option value="kg">Kilogramos (kg)</option>
                        <option value="lt">Litros (lt)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Stock Mínimo (Alerta)</label>
                    <input type="number" step="0.000001" name="min_stock" class="form-control" value="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Crear</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalRecipe" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Agregar Ingrediente a: <span id="recipeTargetName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add_ingredient">
                <input type="hidden" name="manuf_id" id="recipeManufId">

                <div class="alert alert-info small">Define cuánto se gasta para hacer <strong>1 <span
                            id="recipeTargetUnit"></span></strong> de este producto.</div>

                <div class="mb-3">
                    <label>Ingrediente (Materia Prima)</label>
                    <select name="raw_id" class="form-select" required>
                        <?php foreach ($rawMaterials as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= $m['name'] ?> (<?= $m['unit'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Cantidad Requerida</label>
                    <input type="number" step="0.000001" name="qty" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Agregar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEditRecipe" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Editar Cantidad: <span id="editRecipeMaterialName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="update_recipe_item">
                <input type="hidden" name="recipe_id" id="editRecipeId">

                <div class="mb-3">
                    <label>Nueva Cantidad Requerida</label>
                    <input type="number" step="0.000001" name="qty" id="editRecipeQty" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-warning">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalProduce" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Cocinar: <span id="produceName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="produce">
                <input type="hidden" name="manuf_id" id="produceId">

                <div class="mb-3">
                    <label class="fw-bold">Cantidad Producida (<span id="produceUnit"></span>)</label>
                    <input type="number" step="0.000001" name="qty_produced" class="form-control form-control-lg"
                        required autofocus>
                    <div class="form-text">
                        Ingresa cuánto acabas de preparar. El sistema descontará los ingredientes automáticamente.
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success w-100">Confirmar Producción</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEditInfo" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title">Editar Información Básica</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="update_info">
                <input type="hidden" name="manuf_id" id="editInfoId">
                <div class="mb-3">
                    <label>Nombre del Producto</label>
                    <input type="text" name="name" id="editInfoName" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Unidad de Medida</label>
                    <select name="unit" id="editInfoUnit" class="form-select">
                        <option value="und">Unidades (und)</option>
                        <option value="kg">Kilogramos (kg)</option>
                        <option value="lt">Litros (lt)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Stock Mínimo (Alerta)</label>
                    <input type="number" step="0.000001" name="min_stock" id="editInfoMinStock" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary w-100">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRecipeModal(id, name, unit) {
        document.getElementById('recipeManufId').value = id;
        document.getElementById('recipeTargetName').textContent = name;
        document.getElementById('recipeTargetUnit').textContent = unit;
        new bootstrap.Modal(document.getElementById('modalRecipe')).show();
    }

    function openEditRecipeModal(id, name, qty) {
        document.getElementById('editRecipeId').value = id;
        document.getElementById('editRecipeMaterialName').textContent = name;
        document.getElementById('editRecipeQty').value = qty;
        new bootstrap.Modal(document.getElementById('modalEditRecipe')).show();
    }

    function openProduceModal(id, name, unit) {
        document.getElementById('produceId').value = id;
        document.getElementById('produceName').textContent = name;
        document.getElementById('produceUnit').textContent = unit;
        new bootstrap.Modal(document.getElementById('modalProduce')).show();
    }

    function openEditInfoModal(id, name, unit, minStock) {
        document.getElementById('editInfoId').value = id;
        document.getElementById('editInfoName').value = name;
        document.getElementById('editInfoUnit').value = unit;
        document.getElementById('editInfoMinStock').value = minStock;
        new bootstrap.Modal(document.getElementById('modalEditInfo')).show();
    }
</script>

<?php require_once '../templates/footer.php'; ?>