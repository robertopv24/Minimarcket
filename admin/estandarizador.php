<?php
session_start();
require_once __DIR__ . '/../templates/autoload.php';

// Verificación de sesión y rol de administrador
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

$rawMaterials = $rawMaterialManager->getAllMaterials();
$manufacturedProducts = $productionManager->getAllManufactured();

// 1. Manejador AJAX para obtener receta existente
if (isset($_GET['action']) && $_GET['action'] === 'get_recipe' && !empty($_GET['id'])) {
    header('Content-Type: application/json');
    $recipe = $productionManager->getRecipe($_GET['id']);
    echo json_encode($recipe);
    exit;
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'export_recipe') {
    $manufId = $_POST['target_product_id'];
    $newName = trim($_POST['new_product_name'] ?? '');
    $rawIds = $_POST['raw_ids'] ?? [];
    $quantities = $_POST['normalized_quantities'] ?? [];

    // Si no hay ID pero hay nombre, crear el producto primero
    // El usuario mencionó que si es por unidad, se maneja igual, pero para el estandarizador
    // asumiremos que los nuevos productos son 'kg' por defecto a menos que se cambie luego, 
    // pero podemos dejar que el usuario elija o simplemente usar 'kg' por ahora.
    if (empty($manufId) && !empty($newName)) {
        $newUnit = $_POST['new_product_unit'] ?? 'kg';
        if ($productionManager->createManufacturedProduct($newName, $newUnit)) {
            // Obtener el ID del producto recién creado
            $manufId = $db->lastInsertId();
        } else {
            $mensaje = '<div class="alert alert-danger">Error al crear el nuevo producto manufacturado.</div>';
        }
    }

    if (!empty($manufId) && !empty($rawIds)) {
        $ingredients = [];
        for ($i = 0; $i < count($rawIds); $i++) {
            $ingredients[] = [
                'raw_id' => $rawIds[$i],
                'qty' => $quantities[$i]
            ];
        }

        if ($productionManager->replaceRecipe($manufId, $ingredients)) {
            $mensaje = '<div class="alert alert-success">Receta estandarizada y guardada correctamente.</div>';
            // Recargar productos
            $manufacturedProducts = $productionManager->getAllManufactured();
        } else {
            $mensaje = '<div class="alert alert-danger">Error al guardar la receta.</div>';
        }
    } else {
        if (empty($mensaje)) {
            $mensaje = '<div class="alert alert-warning">Datos incompletos para exportar. Selecciona un producto o ingresa un nombre.</div>';
        }
    }
}

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container-fluid pt-4 px-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0 text-primary"><i class="fa fa-balance-scale me-2"></i>Estandarizador de Recetas</h4>
        <a href="manufactura.php" class="btn btn-sm btn-secondary">Volver a Cocina</a>
    </div>

    <?= $mensaje ?>

    <div class="row">
        <!-- Panel de Entrada -->
        <div class="col-lg-5 mb-4">
            <div class="card shadow bg-secondary text-white border-0">
                <div class="card-header border-bottom border-light">
                    <h5 class="card-title mb-0">1. Datos de la Mezcla de Prueba</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Producto Final (Destino):</label>
                        <div class="input-group mb-2">
                            <span class="input-group-text bg-dark border-0 text-white"><i class="fa fa-list"></i></span>
                            <select id="target_product_select" name="target_product_id" class="form-select bg-dark text-white border-0">
                                <option value="">-- Seleccionar Existente --</option>
                                <?php foreach ($manufacturedProducts as $mp): ?>
                                    <option value="<?= $mp['id'] ?>" data-unit="<?= $mp['unit'] ?>"><?= htmlspecialchars($mp['name']) ?> (<?= $mp['unit'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="input-group mb-2">
                            <span class="input-group-text bg-dark border-0 text-white"><i class="fa fa-plus"></i></span>
                            <input type="text" id="new_product_name" name="new_product_name" class="form-control bg-dark text-white border-0" placeholder="Nombre de producto nuevo...">
                            <select name="new_product_unit" id="new_product_unit" class="form-select bg-dark text-white border-0" style="max-width: 80px;">
                                <option value="kg">kg</option>
                                <option value="und">und</option>
                            </select>
                        </div>
                        <small class="text-info mt-1 d-block">Si seleccionas uno existente, cargaré su receta actual.</small>
                    </div>

                    <hr class="bg-light">

                    <h6 class="mb-3 mt-4">Ingredientes Utilizados:</h6>
                    <div id="ingredient_rows">
                        <!-- Filas de ingredientes dinámicas -->
                        <div class="row g-2 mb-2 ingredient-row align-items-center">
                            <div class="col-7">
                                <select class="form-select form-select-sm bg-dark text-white border-0 raw-select">
                                    <option value="">-- Materia Prima --</option>
                                    <?php foreach ($rawMaterials as $rm): ?>
                                        <option value="<?= $rm['id'] ?>" data-name="<?= htmlspecialchars($rm['name']) ?>"><?= htmlspecialchars($rm['name']) ?> (<?= $rm['unit'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-3">
                                <input type="number" step="0.0001" class="form-control form-control-sm bg-dark text-white border-0 qty-input" placeholder="Cant.">
                            </div>
                            <div class="col-2 text-end">
                                <button type="button" class="btn btn-sm text-danger remove-row"><i class="fa fa-times"></i></button>
                            </div>
                        </div>
                    </div>

                    <button type="button" id="add_ingredient" class="btn btn-sm btn-outline-info w-100 mt-2">
                        <i class="fa fa-plus me-1"></i> Añadir Ingrediente
                    </button>

                    <div class="mt-4 pt-3 border-top border-light">
                        <label class="form-label fw-bold text-warning">Peso Resultante de la Mezcla (Kg):</label>
                        <input type="number" id="total_yield" step="0.0001" class="form-control bg-dark text-warning border-0 fw-bold" placeholder="Ej: 5.400">
                        <p class="small text-muted mt-1 italic">El peso real obtenido después del proceso (fermentación, cocción, etc.)</p>
                    </div>

                    <div id="units_produced_container" class="mt-3" style="display:none;">
                        <label class="form-label fw-bold text-info">Unidades Producidas (und):</label>
                        <input type="number" id="units_produced" step="1" class="form-control bg-dark text-info border-0 fw-bold" placeholder="Ej: 50">
                        <p class="small text-muted mt-1 italic">¿Cuántas piezas / unidades salieron de esta mezcla?</p>
                    </div>

                    <button type="button" id="btn_calculate" class="btn btn-primary w-100 mt-3 fw-bold">
                        <i class="fa fa-calculator me-2"></i> Calcular Estándar
                    </button>
                </div>
            </div>
        </div>

        <!-- Panel de Resultados -->
        <div class="col-lg-7">
            <div class="card shadow border-0 h-100">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">2. Análisis y Normalización (<span id="norm_unit_title">1 Kg</span>)</h5>
                    <div class="text-end">
                        <span id="label_total_input" class="badge bg-secondary d-block mb-1">Total Insumos: 0.000 kg</span>
                        <span id="label_units_per_kg" class="badge bg-info d-none">0.00 und / kg</span>
                    </div>
                </div>
                <div class="card-body bg-light">
                    <div id="results_placeholder" class="text-center py-5">
                        <i class="fa fa-info-circle fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Ingresa los datos y haz clic en calcular para ver los porcentajes.</p>
                    </div>

                    <div id="results_table_container" style="display:none;">
                        <table class="table table-bordered table-hover shadow-sm bg-white">
                            <thead class="table-dark">
                                <tr>
                                    <th>Ingrediente</th>
                                    <th class="text-center">Porcentaje (%)</th>
                                    <th class="text-center text-primary" id="col_norm_header">Para 1 Kg (Receta)</th>
                                </tr>
                            </thead>
                            <tbody id="results_body">
                                <!-- Filas de resultados dinámicas -->
                            </tbody>
                            <tfoot class="table-light">
                                <tr class="fw-bold">
                                    <td>TOTALES</td>
                                    <td class="text-center" id="total_pct">100%</td>
                                    <td class="text-center" id="total_norm">1.000 kg</td>
                                </tr>
                            </tfoot>
                        </table>

                        <div class="alert mt-4 d-flex align-items-center border-success" style="background-color: #161c22;">
                            <i class="fa fa-check-circle fa-2x me-3 text-success"></i>
                            <div>
                                <h6 class="mb-1 text-success">¿Todo listo?</h6>
                                <p class="mb-0 small text-white">Ahora puedes guardar estos valores para <span class="badge bg-success" id="export_unit_label">1 kg</span> como la receta oficial del producto seleccionado.</p>
                            </div>
                        </div>

                        <form method="POST" id="form_export">
                            <input type="hidden" name="action" value="export_recipe">
                            <input type="hidden" name="target_product_id" id="export_manuf_id">
                            <input type="hidden" name="new_product_name" id="export_new_name">
                            <input type="hidden" name="new_product_unit" id="export_new_unit">
                            <!-- Los inputs ocultos de ingredientes se generarán por JS -->
                            <div id="hidden_inputs"></div>
                            <button type="submit" id="btn_export" class="btn btn-success btn-lg w-100 fw-bold shadow">
                                <i class="fa fa-save me-2"></i> ACTUALIZAR RECETA OFICIAL
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ingredientRows = document.getElementById('ingredient_rows');
    const addBtn = document.getElementById('add_ingredient');
    const calcBtn = document.getElementById('btn_calculate');
    const resultsPlaceholder = document.getElementById('results_placeholder');
    const resultsContainer = document.getElementById('results_table_container');
    const resultsBody = document.getElementById('results_body');
    const totalInputLabel = document.getElementById('label_total_input');
    const unitsPerKgLabel = document.getElementById('label_units_per_kg');
    const hiddenInputs = document.getElementById('hidden_inputs');
    const unitsContainer = document.getElementById('units_produced_container');
    const unitsInput = document.getElementById('units_produced');
    const normUnitTitle = document.getElementById('norm_unit_title');
    const colNormHeader = document.getElementById('col_norm_header');
    const exportUnitLabel = document.getElementById('export_unit_label');
    const totalNormCell = document.getElementById('total_norm');

    let currentTargetUnit = 'kg';

    // Plantilla para nueva fila
    const rowTemplate = `
        <div class="row g-2 mb-2 ingredient-row align-items-center">
            <div class="col-7">
                <select class="form-select form-select-sm bg-dark text-white border-0 raw-select">
                    <option value="">-- Materia Prima --</option>
                    <?php foreach ($rawMaterials as $rm): ?>
                        <option value="<?= $rm['id'] ?>" data-name="<?= htmlspecialchars($rm['name']) ?>"><?= htmlspecialchars($rm['name']) ?> (<?= $rm['unit'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-3">
                <input type="number" step="0.0001" class="form-control form-control-sm bg-dark text-white border-0 qty-input" placeholder="Cant.">
            </div>
            <div class="col-2 text-end">
                <button type="button" class="btn btn-sm text-danger remove-row"><i class="fa fa-times"></i></button>
            </div>
        </div>
    `;

    addBtn.addEventListener('click', () => {
        addIngredientRow();
    });

    function addIngredientRow(id = '', qty = '') {
        const div = document.createElement('div');
        div.innerHTML = rowTemplate;
        const row = div.firstElementChild;
        if (id) row.querySelector('.raw-select').value = id;
        if (qty) row.querySelector('.qty-input').value = qty;
        ingredientRows.appendChild(row);
    }

    ingredientRows.addEventListener('click', (e) => {
        if (e.target.closest('.remove-row')) {
            const row = e.target.closest('.ingredient-row');
            if (document.querySelectorAll('.ingredient-row').length > 1) {
                row.remove();
            } else {
                // Si es la última, solo limpiarla
                row.querySelector('.raw-select').value = '';
                row.querySelector('.qty-input').value = '';
            }
        }
    });

    // Cargar receta de producto seleccionado
    document.getElementById('target_product_select').addEventListener('change', function() {
        const id = this.value;
        const option = this.options[this.selectedIndex];
        currentTargetUnit = option.getAttribute('data-unit') || 'kg';

        if (!id) {
            currentTargetUnit = 'kg';
            unitsContainer.style.display = 'none';
            updateUIForUnit();
            return;
        }

        // Mostrar campo de unidades si el producto es 'und'
        if (currentTargetUnit === 'und') {
            unitsContainer.style.display = 'block';
        } else {
            unitsContainer.style.display = 'none';
        }
        updateUIForUnit();

        // Limpiar nombre nuevo si selecciona existente
        document.getElementById('new_product_name').value = '';

        fetch(`estandarizador.php?action=get_recipe&id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data && data.length > 0) {
                    ingredientRows.innerHTML = '';
                    data.forEach(item => {
                        addIngredientRow(item.raw_material_id, item.quantity_required);
                    });
                    
                    if (currentTargetUnit === 'kg') {
                        document.getElementById('total_yield').value = 1;
                    } else {
                        // Si es por unidad, no sabemos el rendimiento original pero podemos poner 1
                        document.getElementById('total_yield').value = 1;
                        document.getElementById('units_produced').value = 1;
                    }
                    calcBtn.click(); // Auto-calcular
                }
            });
    });

    function updateUIForUnit() {
        if (currentTargetUnit === 'und') {
            normUnitTitle.textContent = '1 Unidad';
            colNormHeader.textContent = 'Para 1 Unidad (Receta)';
            exportUnitLabel.textContent = '1 unidad';
            totalNormCell.textContent = '1.00 und';
        } else {
            normUnitTitle.textContent = '1 Kg';
            colNormHeader.textContent = 'Para 1 Kg (Receta)';
            exportUnitLabel.textContent = '1 kg';
            totalNormCell.textContent = '1.000 kg';
        }
    }

    document.getElementById('new_product_name').addEventListener('input', function() {
        if (this.value.trim() !== '') {
            document.getElementById('target_product_select').value = '';
            currentTargetUnit = document.getElementById('new_product_unit').value;
            unitsContainer.style.display = (currentTargetUnit === 'und') ? 'block' : 'none';
            updateUIForUnit();
        }
    });

    document.getElementById('new_product_unit').addEventListener('change', function() {
        if (document.getElementById('new_product_name').value.trim() !== '') {
            currentTargetUnit = this.value;
            unitsContainer.style.display = (currentTargetUnit === 'und') ? 'block' : 'none';
            updateUIForUnit();
        }
    });

    calcBtn.addEventListener('click', () => {
        const rows = document.querySelectorAll('.ingredient-row');
        const yieldVal = parseFloat(document.getElementById('total_yield').value);
        const unitsProd = parseFloat(unitsInput.value) || 0;
        
        if (!yieldVal || yieldVal <= 0) {
            alert('Por favor ingresa un peso resultante válido mayor a cero.');
            return;
        }

        if (currentTargetUnit === 'und' && (!unitsProd || unitsProd <= 0)) {
            alert('Por favor ingresa la cantidad de unidades producidas.');
            return;
        }

        let totalIn = 0;
        const data = [];

        rows.forEach(row => {
            const select = row.querySelector('.raw-select');
            const qtyInput = row.querySelector('.qty-input');
            const id = select.value;
            const name = select.options[select.selectedIndex].getAttribute('data-name');
            const qty = parseFloat(qtyInput.value);

            if (id && qty > 0) {
                totalIn += qty;
                data.push({ id, name, qty });
            }
        });

        if (data.length === 0) {
            alert('Añade al menos un ingrediente con cantidad mayor a cero.');
            return;
        }

        renderResults(data, yieldVal, totalIn, unitsProd);
    });

    function renderResults(data, yieldVal, totalIn, unitsProd) {
        resultsPlaceholder.style.display = 'none';
        resultsContainer.style.display = 'block';
        resultsBody.innerHTML = '';
        hiddenInputs.innerHTML = '';
        totalInputLabel.textContent = `Total Insumos: ${totalIn.toFixed(3)} kg`;

        if (currentTargetUnit === 'und' && unitsProd > 0) {
            const upk = unitsProd / yieldVal;
            unitsPerKgLabel.textContent = `${upk.toFixed(2)} und / kg`;
            unitsPerKgLabel.classList.remove('d-none');
        } else {
            unitsPerKgLabel.classList.add('d-none');
        }

        data.forEach(item => {
            let normalizedQty;
            let displayUnit = 'kg';

            if (currentTargetUnit === 'und') {
                // Cantidad por unidad = (Cant Batch / Unidades Batch)
                normalizedQty = (item.qty / unitsProd);
                displayUnit = 'und'; // Realmente es kg/und, pero mostramos la meta
            } else {
                // Cantidad para 1kg = (Cant Batch / Rendimiento Batch)
                normalizedQty = (item.qty / yieldVal);
            }
            
            const pct = (item.qty / totalIn) * 100; // Porcentaje sobre la mezcla (insumos)

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="fw-bold">${item.name}</td>
                <td class="text-center font-monospace">${pct.toFixed(2)}%</td>
                <td class="text-center fw-bold text-primary">${normalizedQty.toFixed(6)} kg</td>
            `;
            resultsBody.appendChild(tr);

            // Preparar inputs para exportar
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'raw_ids[]';
            idInput.value = item.id;
            
            const qInput = document.createElement('input');
            qInput.type = 'hidden';
            qInput.name = 'normalized_quantities[]';
            qInput.value = normalizedQty.toFixed(6);

            hiddenInputs.appendChild(idInput);
            hiddenInputs.appendChild(qInput);
        });
    }

    document.getElementById('form_export').addEventListener('submit', function(e) {
        const targetId = document.getElementById('target_product_select').value;
        const newName = document.getElementById('new_product_name').value.trim();

        if (!targetId && !newName) {
            e.preventDefault();
            alert('Debes seleccionar un producto existente o escribir un nombre para el nuevo producto.');
            return;
        }

        document.getElementById('export_manuf_id').value = targetId;
        document.getElementById('export_new_name').value = newName;
        document.getElementById('export_new_unit').value = document.getElementById('new_product_unit').value;
        
        const confirmMsg = targetId 
            ? '¿Estás seguro de que deseas REEMPLAZAR la receta actual de este producto?' 
            : `¿Estás seguro de que deseas CREAR el producto "${newName}" con esta receta?`;

        if(!confirm(confirmMsg)) {
            e.preventDefault();
        }
    });
});
</script>

<style>
.bg-secondary { background-color: #2c3e50 !important; }
.bg-dark { background-color: #1a252f !important; }
.card { border-radius: 15px; }
.form-control, .form-select { border: 1px solid rgba(255,255,255,0.1); }
.form-control:focus, .form-select:focus { 
    box-shadow: 0 0 0 0.25rem rgba(255,193,7, 0.25); 
    border-color: #ffc107;
}
.hover-scale { transition: transform 0.2s; }
.hover-scale:hover { transform: scale(1.02); }
.italic { font-style: italic; }
</style>

<?php require_once '../templates/footer.php'; ?>
