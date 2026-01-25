<?php
require_once '../templates/autoload.php';

session_start();
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

$productId = $_GET['id'] ?? null;
if (!$productId) {
    header('Location: productos.php');
    exit;
}

$producto = $productManager->getProductById($productId);
if (!$producto) {
    header('Location: productos.php');
    exit;
}

$mensaje = "";

// Procesar Formulario (JSON)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifiers_json'])) {
    try {
        $data = json_decode($_POST['modifiers_json'], true);
        if ($productManager->saveProductExplodedDefaults($productId, $data)) {
            $mensaje = '<div class="alert alert-success">Configuración por defecto guardada correctamente.</div>';
        }
    } catch (Exception $e) {
        $mensaje = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

// Determinar si es un combo (compound) para saber cómo "explotar" la vista
$isCombo = ($producto['product_type'] === 'compound');
$explodedComponents = [];

if (!$isCombo) {
    // Producto Individual (Simple o Preparado): Una sola tarjeta del producto mismo
    $explodedComponents[] = [
        'component_type' => 'product',
        'component_id' => $productId,
        'item_name' => $producto['name'],
        'quantity' => 1,
        'item_unit' => 'und'
    ];
} else {
    // Combo: Mostrar una tarjeta por cada unidad de cada producto integrante
    $components = $productManager->getProductComponents($productId);
    foreach ($components as $comp) {
        if ($comp['component_type'] !== 'product') continue; 
        $qty = intval($comp['quantity']);
        for ($i = 0; $i < $qty; $i++) {
            $explodedComponents[] = $comp;
        }
    }
}
$components = $explodedComponents;

// Obtener defaults actuales
$currentDefaultsRaw = $productManager->getProductExplodedDefaults($productId);
$currentDefaults = [
    'general_note' => '',
    'items' => []
];

foreach ($currentDefaultsRaw as $def) {
    $idx = $def['sub_item_index'];
    if ($idx == -1) {
        $currentDefaults['general_note'] = $def['note'];
        continue;
    }
    if (!isset($currentDefaults['items'][$idx])) {
        $currentDefaults['items'][$idx] = [
            'consumption' => ($def['is_takeaway'] == 1) ? 'takeaway' : 'dine_in',
            'remove' => [],
            'add' => [],
            'sides' => []
        ];
    }
    if ($def['modifier_type'] == 'remove') $currentDefaults['items'][$idx]['remove'][] = (int)$def['component_id'];
    if ($def['modifier_type'] == 'add') $currentDefaults['items'][$idx]['add'][] = (int)$def['component_id'];
    if ($def['modifier_type'] == 'side') $currentDefaults['items'][$idx]['sides'][] = (int)$def['component_id'];
}

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container-fluid mt-4" style="max-width: 1200px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="productos.php">Productos</a></li>
                    <li class="breadcrumb-item active">Configurar Defectos Advanced</li>
                </ol>
            </nav>
            <h2 class="fw-bold"><i class="fa fa-layer-group text-primary me-2"></i>Vista de Componentes: <?= htmlspecialchars($producto['name']) ?></h2>
            <p class="text-muted small mb-0">Define la configuración que tendrá el pedido por defecto al agregarlo al carrito.</p>
        </div>
        <div>
            <a href="productos.php" class="btn btn-outline-secondary me-2">Cancelar</a>
            <button type="button" onclick="submitDefaults()" class="btn btn-primary px-4 shadow">
                 <i class="fa fa-save me-1"></i> Guardar Todo
            </button>
        </div>
    </div>

    <?= $mensaje ?>

    <form id="defaultsForm" method="POST">
        <input type="hidden" name="modifiers_json" id="modifiers_json">
        
        <!-- Nota General -->
        <div class="card shadow-sm mb-4">
            <div class="card-body py-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="fa fa-sticky-note me-2"></i> Nota Global por Defecto</span>
                    <input type="text" id="general_note" class="form-control" placeholder="Ej: Por favor bien caliente..." value="<?= htmlspecialchars($currentDefaults['general_note']) ?>">
                </div>
            </div>
        </div>

        <div class="row g-3" id="componentsContainer">
            <div class="col-md-8">
                <div class="row g-3">
                    <?php foreach ($components as $idx => $comp): 
                        $compId = $comp['component_id'];
                        $compType = $comp['component_type'];
                        
                        $validExtras = [];
                        $validSides = [];
                        $removableIngredients = [];
                        $maxSides = 0;

                        // Obtener costo base del componente
                        $baseCompCost = 0;
                        if ($compType === 'product') {
                            $baseCompCost = $productManager->calculateProductCost($compId);
                            $validExtras = $productManager->getValidExtras($compId);
                            $validSides = $productManager->getValidSides($compId);
                            $subCompData = $productManager->getProductById($compId);
                            $maxSides = $subCompData['max_sides'] ?? 0;
                            if ($subCompData['product_type'] === 'prepared') {
                                $removableIngredients = $productManager->getProductComponents($compId);
                            }
                        }

                        $state = $currentDefaults['items'][$idx] ?? [
                            'consumption' => 'dine_in',
                            'remove' => [],
                            'add' => [],
                            'sides' => []
                        ];
                    ?>
                        <div class="col-md-12 col-lg-6">
                            <div class="card h-100 shadow-sm border-0 component-card" data-idx="<?= $idx ?>" data-base-cost="<?= $baseCompCost ?>">
                                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
                                    <span class="fw-bold small">#<?= $idx + 1 ?> <?= htmlspecialchars($comp['item_name']) ?></span>
                                    <div class="btn-group btn-group-sm rounded-pill overflow-hidden border border-secondary" role="group">
                                        <input type="radio" class="btn-check" name="cons_<?= $idx ?>" id="cons_dine_<?= $idx ?>" value="dine_in" <?= $state['consumption'] === 'dine_in' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-light py-0 px-2 border-0" for="cons_dine_<?= $idx ?>"><i class="fa fa-utensils" style="font-size: 0.8em;"></i></label>
                                        
                                        <input type="radio" class="btn-check" name="cons_<?= $idx ?>" id="cons_take_<?= $idx ?>" value="takeaway" <?= $state['consumption'] === 'takeaway' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-light py-0 px-2 border-0" for="cons_take_<?= $idx ?>"><i class="fa fa-shopping-bag" style="font-size: 0.8em;"></i></label>
                                    </div>
                                </div>
                                <div class="card-body p-3 overflow-auto" style="max-height: 400px;">
                                    
                                    <!-- REMOCIONES -->
                                    <?php if (!empty($removableIngredients)): ?>
                                        <label class="fw-bold text-danger small mb-2"><i class="fa fa-minus-circle"></i> Quitar por Defecto:</label>
                                        <div class="mb-3 ps-2">
                                            <?php foreach ($removableIngredients as $ring): 
                                                if ($ring['component_type'] !== 'raw') continue;
                                                $isRemoved = in_array($ring['component_id'], $state['remove']);
                                                $ringCost = floatval($ring['item_cost'] * $ring['quantity']);
                                            ?>
                                                <div class="form-check mb-1">
                                                    <input class="form-check-input remove-chk border-danger" type="checkbox" value="<?= $ring['component_id'] ?>" 
                                                            data-idx="<?= $idx ?>" data-cost="<?= $ringCost ?>" <?= $isRemoved ? 'checked' : '' ?> onchange="updateTotalCostUI()">
                                                    <label class="form-check-label small text-muted">Quitar <?= htmlspecialchars($ring['item_name']) ?></label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- EXTRAS -->
                                    <?php if (!empty($validExtras)): ?>
                                        <label class="fw-bold text-success small mb-2"><i class="fa fa-plus-circle"></i> Extras Incluidos:</label>
                                        <div class="mb-3 ps-2">
                                            <?php foreach ($validExtras as $extra): 
                                                $isSelected = in_array($extra['component_id'], $state['add']);
                                                $extraCost = floatval($extra['cost_per_unit'] * $extra['quantity_required']);
                                            ?>
                                                <div class="form-check mb-1">
                                                    <input class="form-check-input add-chk border-success" type="checkbox" value="<?= $extra['component_id'] ?>" 
                                                        data-type="<?= $extra['component_type'] ?>" data-qty="<?= $extra['quantity_required'] ?>" data-price="<?= $extra['price_override'] ?>"
                                                        data-cost="<?= $extraCost ?>" data-idx="<?= $idx ?>" <?= $isSelected ? 'checked' : '' ?> onchange="updateTotalCostUI()">
                                                    <label class="form-check-label small d-flex justify-content-between pe-2 w-100">
                                                        <span><?= htmlspecialchars($extra['name']) ?></span>
                                                        <span class="text-success small">+$<?= number_format($extra['price_override'], 2) ?></span>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- CONTORNOS -->
                                    <?php if (!empty($validSides)): ?>
                                        <label class="fw-bold text-info small mb-2"><i class="fa fa-utensils"></i> Contornos Seleccionados:</label>
                                        <div class="mb-3 ps-2">
                                            <small class="text-muted d-block mb-1">(Límite: <?= $maxSides ?>)</small>
                                            <?php foreach ($validSides as $side): 
                                                $isSelected = in_array($side['component_id'], $state['sides']);
                                                // El costo de los sides es más complejo, pero si es raw o manuf lo tenemos.
                                                // Vamos a simplificar asumiendo que el costo ya está en el side manager si fuera necesario, 
                                                // o calcularlo aquí.
                                                $sideCost = 0;
                                                if ($side['component_type'] === 'raw') {
                                                    $rawM = $rawMaterialManager->getMaterialById($side['component_id']);
                                                    $sideCost = floatval($rawM['cost_per_unit'] * $side['quantity']);
                                                } elseif ($side['component_type'] === 'manufactured') {
                                                    $manufM = $productionManager->getProductionById($side['component_id']);
                                                    $sideCost = floatval($manufM['unit_cost_average'] * $side['quantity']);
                                                }
                                            ?>
                                                <div class="form-check mb-1">
                                                    <input class="form-check-input side-chk border-info" type="checkbox" value="<?= $side['component_id'] ?>" 
                                                        data-type="<?= $side['component_type'] ?>" data-qty="<?= $side['quantity'] ?>" data-price="<?= $side['price_override'] ?>"
                                                        data-cost="<?= $sideCost ?>" data-idx="<?= $idx ?>" data-max="<?= $maxSides ?>" <?= $isSelected ? 'checked' : '' ?> onchange="updateTotalCostUI()">
                                                    <label class="form-check-label small d-flex justify-content-between pe-2 w-100">
                                                        <span><?= htmlspecialchars($side['item_name']) ?></span>
                                                        <span class="text-info small">+$<?= number_format($side['price_override'], 2) ?></span>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (empty($removableIngredients) && empty($validExtras) && empty($validSides)): ?>
                                        <div class="text-center py-4 text-muted small">
                                            <i class="fa fa-info-circle fa-2x mb-2 opacity-25"></i><br>
                                            Este componente no tiene opciones personalizables.
                                        </div>
                                    <?php endif; ?>

                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Analizador de Costos -->
            <div class="col-md-4">
                <div class="card shadow border-primary sticky-top" style="top: 20px; z-index: 1020;">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="mb-0"><i class="fa fa-calculator me-2"></i> Análisis de Costo Base</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Producción Original:</span>
                            <span class="fw-bold" id="cost_original">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>(+) Extras por Defecto:</span>
                            <span class="fw-bold" id="cost_extras">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 text-info">
                            <span>(+) Contornos/Lados:</span>
                            <span class="fw-bold" id="cost_sides">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 text-danger">
                            <span>(-) Ahorro Remociones:</span>
                            <span class="fw-bold" id="cost_removals">-$0.00</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">Costo Final:</h4>
                            <h3 class="text-primary fw-bold mb-0" id="cost_total">$0.00</h3>
                        </div>
                        <p class="text-muted small mt-3">
                            <i class="fa fa-info-circle"></i> Este es el costo estimado de <strong>producción</strong> 
                            bajo esta configuración. Se calcula sumando el costo de cada ingrediente del inventario.
                        </p>
                    </div>
                    <div class="card-footer bg-light p-0">
                        <button type="button" onclick="submitDefaults()" class="btn btn-primary btn-lg w-100 rounded-0 py-3 fw-bold">
                            <i class="fa fa-save me-2"></i> GUARDAR CONFIGURACIÓN
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    .component-card { transition: transform 0.2s, box-shadow 0.2s; border: 1px solid #eee !important; }
    .component-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1) !important; border-color: #0d6efd !important; }
    .btn-check:checked + label { background-color: #fff !important; color: #212529 !important; font-weight: bold; }
    .card-body::-webkit-scrollbar { width: 5px; }
    .card-body::-webkit-scrollbar-track { background: #f1f1f1; }
    .card-body::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sideCheckboxes = document.querySelectorAll('.side-chk');
        
        function validateMaxSides(idx) {
            const chks = document.querySelectorAll(`.side-chk[data-idx="${idx}"]`);
            if (chks.length === 0) return;
            
            const max = parseInt(chks[0].dataset.max || 0);
            const checkedCount = Array.from(chks).filter(cb => cb.checked).length;
            
            chks.forEach(cb => {
                if (!cb.checked && checkedCount >= max) {
                    cb.disabled = true;
                } else {
                    cb.disabled = false;
                }
            });
        }

        // Inicializar validación para cada tarjeta
        const cards = document.querySelectorAll('.component-card');
        cards.forEach(card => validateMaxSides(card.dataset.idx));

        // Listener para cambios
        sideCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                validateMaxSides(this.dataset.idx);
            });
        });

        updateTotalCostUI(); // Cálculo inicial
    });

    function updateTotalCostUI() {
        let originalCost = 0;
        let extrasCost = 0;
        let sidesCost = 0;
        let removalsSaving = 0;

        // 1. Costo Base de cada componente expandido
        document.querySelectorAll('.component-card').forEach(card => {
            originalCost += parseFloat(card.dataset.baseCost || 0);
        });

        // 2. Costo de Extras seleccionados
        document.querySelectorAll('.add-chk:checked').forEach(chk => {
            extrasCost += parseFloat(chk.dataset.cost || 0);
        });

        // 3. Costo de Contornos seleccionados
        document.querySelectorAll('.side-chk:checked').forEach(chk => {
            sidesCost += parseFloat(chk.dataset.cost || 0);
        });

        // 4. Ahorro por Remociones (Ingredientes que no se usarán)
        document.querySelectorAll('.remove-chk:checked').forEach(chk => {
            removalsSaving += parseFloat(chk.dataset.cost || 0);
        });

        const totalFinal = (originalCost + extrasCost + sidesCost) - removalsSaving;

        // Actualizar UI
        document.getElementById('cost_original').innerText = `$${originalCost.toFixed(2)}`;
        document.getElementById('cost_extras').innerText = `+$${extrasCost.toFixed(2)}`;
        document.getElementById('cost_sides').innerText = `+$${sidesCost.toFixed(2)}`;
        document.getElementById('cost_removals').innerText = `-$${removalsSaving.toFixed(2)}`;
        document.getElementById('cost_total').innerText = `$${totalFinal.toFixed(2)}`;
    }

    function submitDefaults() {
        const items = [];
        const cards = document.querySelectorAll('.component-card');
        const generalNote = document.getElementById('general_note').value;

        cards.forEach(card => {
            const idx = card.dataset.idx;
            const consumption = document.querySelector(`input[name="cons_${idx}"]:checked`).value;
            
            const remove = [];
            const add = [];
            const sides = [];

            card.querySelectorAll('.remove-chk:checked').forEach(el => remove.push(parseInt(el.value)));
            card.querySelectorAll('.add-chk:checked').forEach(el => add.push({
                id: parseInt(el.value),
                type: el.dataset.type,
                qty: parseFloat(el.dataset.qty),
                price: parseFloat(el.dataset.price)
            }));
            card.querySelectorAll('.side-chk:checked').forEach(el => sides.push({
                id: parseInt(el.value),
                type: el.dataset.type,
                qty: parseFloat(el.dataset.qty),
                price: parseFloat(el.dataset.price)
            }));

            items.push({
                index: parseInt(idx),
                consumption: consumption,
                remove: remove,
                add: add,
                sides: sides
            });
        });

        const finalData = {
            general_note: generalNote,
            items: items
        };

        document.getElementById('modifiers_json').value = JSON.stringify(finalData);
        document.getElementById('defaultsForm').submit();
    }
</script>

<?php require_once '../templates/footer.php'; ?>
