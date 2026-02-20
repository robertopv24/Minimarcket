<?php
session_start();
// -------------------------------------------------------------------------
// 1. LÓGICA Y CONTROLADOR
// -------------------------------------------------------------------------
require_once '../templates/autoload.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header("Location: login.php");
    exit;
}

$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $cartId = $_POST['cart_id'] ?? 0;

    try {
        // GUARDAR MODIFICACIONES
        if ($action === 'save_modifiers') {
            $json = $_POST['modifiers_json'];
            $itemsData = json_decode($json, true);
            $generalNote = $_POST['general_note'] ?? '';

            $result = $cartManager->updateItemModifiers($cartId, [
                'items' => $itemsData,
                'general_note' => $generalNote
            ]);

            if ($result === true) {
                header("Location: carrito.php");
                exit;
            } else {
                throw new Exception($result);
            }
        }

        // OTRAS ACCIONES
        if ($action === 'update_qty')
            $cartManager->updateCartQuantity($cartId, $_POST['quantity']);
        if ($action === 'remove')
            $cartManager->removeFromCart($cartId);
        if ($action === 'clear')
            $cartManager->emptyCart($userId);

        header("Location: carrito.php");
        exit;

    } catch (Exception $e) {
        $error_msg = "Error: " . $e->getMessage();
    }
}

$cartItems = $cartManager->getCart($userId);
$total = $cartManager->calculateTotal($cartItems);

// -------------------------------------------------------------------------
// 1.1 FETCH MODIFIERS (Organized by Item ID & SubIndex)
// -------------------------------------------------------------------------
$allModifiers = [];
if (!empty($cartItems)) {
    // Get all cart IDs involved
    $cIds = array_map(function ($i) {
        return $i['id'];
    }, $cartItems);
    $inPart = implode(',', array_fill(0, count($cIds), '?'));

    // Fetch raw modifiers (including component details for names)
    $sqlMods = "SELECT cm.*, 
                       CASE 
                           WHEN cm.modifier_type = 'side' OR cm.modifier_type = 'add' OR cm.modifier_type = 'remove' 
                           THEN (
                               CASE 
                                   WHEN cm.component_type = 'raw' OR cm.component_type IS NULL THEN (SELECT name FROM raw_materials WHERE id = cm.component_id)
                                   WHEN cm.component_type = 'manufactured' THEN (SELECT name FROM manufactured_products WHERE id = cm.component_id)
                                   WHEN cm.component_type = 'product' THEN (SELECT name FROM products WHERE id = cm.component_id)
                               END
                           )
                           ELSE '' 
                       END as resolved_name
                FROM cart_item_modifiers cm 
                WHERE cm.cart_id IN ($inPart)";

    $stmtMods = $db->prepare($sqlMods);
    $stmtMods->execute($cIds);
    $rawMods = $stmtMods->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rawMods as $m) {
        $cId = $m['cart_id'];
        $sIdx = $m['sub_item_index'];

        if (!isset($allModifiers[$cId]))
            $allModifiers[$cId] = [];
        if (!isset($allModifiers[$cId][$sIdx]))
            $allModifiers[$cId][$sIdx] = [];
        $allModifiers[$cId][$sIdx][] = $m;
    }
}

// -------------------------------------------------------------------------
// 2. VISTA
// -------------------------------------------------------------------------
require_once '../templates/header.php';
?>

<style>
    /* Modal XL con ajuste dinámico de ancho */
    @media (min-width: 1200px) {
        .modal-xl {
            max-width: 95vw;
            width: fit-content;
            min-width: 1140px; /* Asegura un mínimo decente */
        }
    }

    /* Estilo para barras de desplazamiento modernas */
    .custom-scroll::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scroll::-webkit-scrollbar-track {
        background: rgba(255,255,255,0.05);
        border-radius: 10px;
    }
    .custom-scroll::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.2);
        border-radius: 10px;
    }
    .custom-scroll::-webkit-scrollbar-thumb:hover {
        background: rgba(255,255,255,0.3);
    }

    /* Botones de contornos más visibles */
    .btn-side-option {
        border-color: rgba(255,255,255,0.2) !important;
        color: #dee2e6 !important;
        background-color: rgba(255,255,255,0.05) !important;
        transition: all 0.2s;
        min-height: 45px;
    }
    .btn-side-option:hover {
        background-color: var(--bs-primary) !important;
        border-color: var(--bs-primary) !important;
        color: white !important;
        transform: scale(1.02);
    }

    /* Efectos de opacidad */
    .hover-opacity-100 { transition: opacity 0.2s; }
    .hover-opacity-100:hover { opacity: 1 !important; }

    /* Mejorar visibilidad de selectores (Radios) en modo oscuro */
    .form-check-input:checked {
        background-color: #ff4d4d;
        border-color: #ff4d4d;
    }
    .form-check-label {
        color: #eee;
    }

    /* Espaciador de grupos de productos */
    .cart-group-separator td {
        padding: 0.5rem 0 !important;
        background-color: transparent !important;
        border: none !important;
        height: 15px;
    }
    .cart-group-separator td::after {
        content: "";
        display: block;
        height: 2px;
        background: repeating-linear-gradient(90deg, #dee2e6, #dee2e6 10px, transparent 10px, transparent 20px);
        margin: 5px 15px;
        opacity: 0.5;
    }

    /* Divisor para el panel izquierdo (Grid) */
    .group-divider {
        height: 1px;
        border-top: 2px dashed #dee2e6;
        margin: 1rem 0;
        opacity: 0.5;
        width: 100%;
    }
</style>

<div class="container mt-5 mb-5">
    <h2 class="text-center mb-4"><i class="fa fa-shopping-cart text-primary"></i> Tu Pedido</h2>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger shadow-sm"><?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>

    <?php if (empty($cartItems)): ?>
        <div class="text-center py-5">
            <h4 class="text-muted">Carrito vacío</h4>
            <a href="tienda.php" class="btn btn-primary mt-3">Ir al Menú</a>
        </div>
    <?php else: ?>
        <div class="row h-100">
            <!-- PANEL IZQUIERDO: VISUALIZADOR DE TODOS LOS ITEMS (60%) -->
            <div class="col-md-7 h-100 overflow-auto border-end">
                <div class="p-2">
                    <h5 class="mb-3"><i class="fa fa-th me-2"></i>Vista de Componentes</h5>
                    <div class="row g-2">
                        <?php
                        // Bucle EXPLOTADO para el Panel Izquierdo
                        $firstGroupLeft = true;
                        foreach ($cartItems as $item) {
                            $cId = $item['id'];

                            // Si es un item raíz, ponemos el divisor
                            if ($item['parent_cart_id'] === null) {
                                if (!$firstGroupLeft) {
                                    echo '<div class="col-12"><div class="group-divider"></div></div>';
                                }
                                $firstGroupLeft = false;
                            }

                            if ($item['product_type'] == 'compound') {
                                $components = $productManager->getProductComponents($item['product_id']);
                                $idx = 0;
                                foreach ($components as $comp) {
                                    $qty = intval($comp['quantity']);
                                    $rowId = $comp['id'];

                                    // LÓGICA DE OVERRIDE: Si el admin definió una receta para este componente en el combo
                                    $overrideRecipe = $productManager->getComponentOverrides($rowId);
                                    $hasOverride = !empty($overrideRecipe);

                                    if ($comp['component_type'] == 'product') {
                                        $subP = $productManager->getProductById($comp['component_id']);
                                        $name = $subP['name'];
                                        $img = !empty($subP['image_url']) ? $subP['image_url'] : 'img/no-image.png';
                                    } elseif ($comp['component_type'] == 'manufactured') {
                                        $stmtMan = $db->prepare("SELECT name, image_url FROM manufactured_products WHERE id = ?");
                                        $stmtMan->execute([$comp['component_id']]);
                                        $manP = $stmtMan->fetch(PDO::FETCH_ASSOC);
                                        $name = $manP ? $manP['name'] : 'Item Cocina';
                                        $img = ($manP && !empty($manP['image_url'])) ? $manP['image_url'] : 'img/no-image.png';
                                    } else {
                                        $name = 'Ingrediente';
                                        $img = 'img/no-image.png';
                                    }

                                    for ($i = 0; $i < $qty; $i++) {
                                        // MODIFICADORES DE ESTE ÍTEM
                                        $myMods = $allModifiers[$cId][$idx] ?? [];

                                        // RENDER CARD
                                        ?>
                                        <div class="col-12 col-xl-6">
                                            <div class="card p-2 shadow-sm clickable-row border h-100" style="cursor: pointer;"
                                                onclick="openModifierModal(<?= $item['id'] ?>, <?= $idx ?>)">
                                                <div class="d-flex align-items-center h-100 position-relative">
                                                    <?php if ($hasOverride): ?>
                                                        <span class="position-absolute top-0 end-0 p-1" title="Personalizado por Admin">
                                                            <i class="fa fa-flask text-primary" style="font-size: 0.6rem;"></i>
                                                        </span>
                                                    <?php endif; ?>
                                                    
                                                    <!-- COL 1: IMAGEN + NOMBRE -->
                                                    <div class="text-center me-2" style="min-width: 80px; max-width: 80px;">
                                                        <img src="../<?= htmlspecialchars($img) ?>" class="rounded border mb-1"
                                                            style="width: 60px; height: 60px; object-fit: cover;">
                                                        <div class="small fw-bold lh-sm text-truncate w-100" style="font-size: 0.65rem;">
                                                            <?= htmlspecialchars($name) ?>
                                                        </div>
                                                    </div>

                                                    <!-- COL 2: LEYENDA MODIFICACIONES -->
                                                    <div class="flex-grow-1 small border-start ps-2" style="font-size: 0.7rem;">
                                                        <?php if (in_array($idx, $item['incomplete_indices'] ?? [])): ?>
                                                            <div class="mb-1"><span class="badge bg-danger w-100"><i class="fa fa-exclamation-triangle"></i> INCOMPLETO</span></div>
                                                        <?php endif; ?>
                                                        <?php if (empty($myMods)): ?>
                                                            <span class="text-muted fst-italic">Sin cambios</span>
                                                        <?php else: ?>
                                                            <?php foreach ($myMods as $m): ?>
                                                                <?php
                                                                if ($m['modifier_type'] == 'info' && $m['is_takeaway'] == 1) {
                                                                    echo '<div class="mb-1"><span class="badge bg-secondary">🥡 Llevar</span></div>';
                                                                } elseif ($m['modifier_type'] == 'remove') {
                                                                    echo '<div class="mb-1"><span class="badge text-bg-danger text-wrap text-start"><i class="fa fa-times me-1"></i>Sin ' . htmlspecialchars($m['resolved_name']) . '</span></div>';
                                                                } elseif ($m['modifier_type'] == 'add') {
                                                                    echo '<div class="mb-1"><span class="badge text-bg-success text-wrap text-start"><i class="fa fa-plus me-1"></i>' . htmlspecialchars($m['resolved_name']) . '</span></div>';
                                                                } elseif ($m['modifier_type'] == 'side') {
                                                                    echo '<div class="mb-1"><span class="badge text-bg-info text-wrap text-start"><i class="fa fa-check me-1"></i>' . htmlspecialchars($m['resolved_name']) . '</span></div>';
                                                                }
                                                                ?>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                        $idx++;
                                    }
                                }
                            } else {
                                // ITEM SIMPLE (No Combo)
                                $idx = 0;
                                $myMods = $allModifiers[$cId][0] ?? [];
                                ?>
                                <div class="col-12 col-xl-6">
                                    <div class="card p-2 shadow-sm clickable-row border h-100" style="cursor: pointer;"
                                        onclick="openModifierModal(<?= $item['id'] ?>, 0)">
                                        <div class="d-flex align-items-center h-100">
                                            <!-- COL 1 -->
                                            <div class="text-center me-2" style="min-width: 80px; max-width: 80px;">
                                                <img src="../<?= htmlspecialchars($item['image_url'] ?? 'img/no-image.png') ?>"
                                                    class="rounded border mb-1"
                                                    style="width: 60px; height: 60px; object-fit: cover;">
                                                <div class="small fw-bold lh-sm text-truncate w-100" style="font-size: 0.65rem;">
                                                    <?= htmlspecialchars($item['name']) ?>
                                                </div>
                                            </div>

                                            <!-- COL 2 -->
                                            <div class="flex-grow-1 small border-start ps-2" style="font-size: 0.7rem;">
                                                <?php if (in_array(0, $item['incomplete_indices'] ?? [])): ?>
                                                    <div class="mb-1"><span class="badge bg-danger w-100"><i class="fa fa-exclamation-triangle"></i> INCOMPLETO</span></div>
                                                <?php endif; ?>
                                                <?php if (empty($myMods)): ?>
                                                    <span class="text-muted fst-italic">Sin cambios</span>
                                                <?php else: ?>
                                                    <?php foreach ($myMods as $m): ?>
                                                        <?php
                                                        if ($m['modifier_type'] == 'info' && $m['is_takeaway'] == 1) {
                                                            echo '<div class="mb-1"><span class="badge bg-secondary">🥡 Llevar</span></div>';
                                                        } elseif ($m['modifier_type'] == 'remove') {
                                                            echo '<div class="mb-1"><span class="badge text-bg-danger text-wrap text-start"><i class="fa fa-times me-1"></i>Sin ' . htmlspecialchars($m['resolved_name']) . '</span></div>';
                                                        } elseif ($m['modifier_type'] == 'add') {
                                                            echo '<div class="mb-1"><span class="badge text-bg-success text-wrap text-start"><i class="fa fa-plus me-1"></i>' . htmlspecialchars($m['resolved_name']) . '</span></div>';
                                                        } elseif ($m['modifier_type'] == 'side') {
                                                            echo '<div class="mb-1"><span class="badge text-bg-info text-wrap text-start"><i class="fa fa-check me-1"></i>' . htmlspecialchars($m['resolved_name']) . '</span></div>';
                                                        }
                                                        ?>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- PANEL DERECHO: TABLA DE RESUMEN (40%) -->
            <div class="col-md-5 h-100 overflow-auto">
                <div class="p-2">
                    <h5 class="mb-3"><i class="fa fa-list me-2"></i>Resumen de Pedido</h5>

                    <!-- INFO CLIENTE (POS) -->
                    <?php if (isset($_SESSION['pos_client_name'])): ?>
                        <div class="card bg-primary text-white border-0 shadow-sm mb-3">
                            <div class="card-body py-2 d-flex align-items-center">
                                <i class="fa fa-user-circle fa-2x me-3"></i>
                                <div>
                                    <small class="text-white-50 text-uppercase fw-bold" style="font-size: 0.7rem;">Cliente
                                        Asignado</small>
                                    <div class="fw-bold fs-6"><?= htmlspecialchars($_SESSION['pos_client_name']) ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow-sm border-0">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 40%;">Producto</th>
                                            <th style="width: 20%;">Cant.</th>
                                            <th class="text-end" style="width: 25%;">Precio</th>
                                            <th style="width: 15%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $firstItem = true;
                                        foreach ($cartItems as $item): 
                                            // Si es un item raíz (no es un acompañante vinculado) y no es el primero, ponemos un separador
                                            if ($item['parent_cart_id'] === null && !$firstItem) {
                                                echo '<tr class="cart-group-separator"><td colspan="4"></td></tr>';
                                            }
                                            if ($item['parent_cart_id'] === null) {
                                                $firstItem = false;
                                            }
                                        ?>
                                            <tr onclick="openModifierModal(<?= $item['id'] ?>)" style="cursor: pointer;">
                                                <!-- Producto -->
                                                <td>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($item['name']) ?></div>
                                                    <?php if ($item['product_type'] == 'compound'): ?>
                                                        <span class="badge bg-warning text-dark"
                                                            style="font-size:0.7em">COMBO</span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($item['modifiers_desc'])): ?>
                                                        <div class="text-muted small text-truncate" style="max-width: 150px;">
                                                            <?= count($item['modifiers_desc']) ?> cambios
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!$item['is_complete']): ?>
                                                        <div class="text-danger small fw-bold"><i class="fa fa-warning"></i> Configuración pendiente</div>
                                                    <?php endif; ?>
                                                </td>

                                                <!-- Cantidad -->
                                                <td onclick="event.stopPropagation()">
                                                    <form method="post">
                                                        <input type="hidden" name="action" value="update_qty">
                                                        <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                                        <input type="number" name="quantity" value="<?= $item['quantity'] ?>"
                                                            class="form-control form-control-sm text-center fw-bold"
                                                            style="width:60px;" onchange="this.form.submit()">
                                                    </form>
                                                </td>

                                                <!-- Precio -->
                                                <td class="text-end">
                                                    <div class="fw-bold text-success">
                                                        $<?= number_format($item['total_price'], 2) ?></div>
                                                </td>

                                                <!-- Eliminar -->
                                                <td class="text-end" onclick="event.stopPropagation()">
                                                    <form method="post">
                                                        <input type="hidden" name="action" value="remove">
                                                        <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                                        <button class="btn btn-link text-danger btn-sm p-0"><i
                                                                class="fa fa-trash"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumen Total (Full Width Abajo) -->
            <div class="col-12 mt-3">
                <div class="card bg-white shadow-sm border-0">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div class="d-flex gap-2">
                            <a href="tienda.php" class="btn btn-secondary shadow-sm">
                                <i class="fa fa-arrow-left me-1"></i> Volver a la Tienda
                            </a>
                            <form method="post" onclick="return confirm('¿Vaciar carrito?')">
                                <input type="hidden" name="action" value="clear">
                                <button class="btn btn-outline-danger btn-sm h-100">Vaciar Todo</button>
                            </form>
                        </div>
                        <div class="text-end d-flex align-items-center gap-3">
                            <div>
                                <span class="text-muted me-2">Total:</span>
                                <span
                                    class="h4 fw-bold text-success mb-0">$<?= number_format($total['total_usd'], 2) ?></span>
                            </div>
                             <button type="button" class="btn btn-success px-4 fw-bold shadow" onclick="validateCheckout()">
                                Pagar <i class="fa fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="modifierModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form method="post" class="modal-content" id="modalForm">
            <input type="hidden" name="action" value="save_modifiers">
            <input type="hidden" name="cart_id" id="modalCartId">
            <input type="hidden" name="modifiers_json" id="modalModifiersJson">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Personalizar</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div id="modalLoading" class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2 text-muted">Cargando datos...</p>
                </div>

                <div id="modalItemsContainer" class="accordion" style="display:none;"></div>

                <div class="mt-3">
                    <label class="fw-bold">Nota General:</label>
                    <input type="text" name="general_note" id="modalGeneralNote" class="form-control"
                        placeholder="Ej: Sin servilletas...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="submitModifiers()">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
    const cartIsComplete = <?= json_encode(array_reduce($cartItems, function($acc, $i){ return $acc && $i['is_complete']; }, true)) ?>;

    function validateCheckout() {
        if (!cartIsComplete) {
            alert("⚠️ Algunos productos no están completamente configurados (Contornos obligatorios pendientes). Por favor, presiona sobre los productos en rojo para personalizarlos antes de pagar.");
            return;
        }
        window.location.href = 'checkout.php';
    }

    // Función global para abrir el modal desde cualquier lugar
    // Si se pasa targetIndex != null, se expande SOLO ese ítem en el acordeón
    function openModifierModal(cartId, targetIndex = null) {
        document.getElementById('modalCartId').value = cartId;

        // Reset UI
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalItemsContainer').style.display = 'none';
        document.getElementById('modalGeneralNote').value = '';

        new bootstrap.Modal(document.getElementById('modifierModal')).show();

        // Solicitar datos frescos al servidor (Incluye estructura + guardados)
        fetch(`../ajax/get_cart_item.php?cart_id=${cartId}`)
            .then(r => r.json())
            .then(data => {
                if (data.error) { alert(data.error); return; }

                // Debug en consola para verificar qué llega
                console.log("Datos del Carrito:", data);

                // Restaurar Nota General
                if (data.saved_mods) {
                    const noteRow = data.saved_mods.find(m => m.sub_item_index == -1 && m.modifier_type == 'info');
                    if (noteRow) document.getElementById('modalGeneralNote').value = noteRow.note;
                }

                renderComboItems(data, targetIndex);

                document.getElementById('modalLoading').style.display = 'none';
                document.getElementById('modalItemsContainer').style.display = 'block';
            })
            .catch(err => {
                console.error(err);
                alert("Error de conexión al cargar datos.");
            });
    }

    // Estado Global para la selección de contornos en el modal
    // Indexed by SubItem Index: currentCartSides[0] = [{id:1, type:'raw'}, ...]
    let currentCartSides = {};
    let currentCartLogics = {}; // Almacena el tipo de lógica (standard/proportional)

    function renderComboItems(data, targetIndex = null) {
        const container = document.getElementById('modalItemsContainer');
        container.innerHTML = '';
        currentCartSides = {}; // Reset global state
        currentCartLogics = {};

        // Esta es la clave: Usamos los datos guardados que vienen del AJAX
        const savedMods = data.saved_mods || [];

        data.sub_items.forEach((item, idx) => {
            // Lógica de "Zoom In" MODIFICADA:
            // Renderizamos TODOS los ítems para que el formulario (submitModifiers) pueda leer sus datos.
            // Pero ocultamos visualmente con 'd-none' los que no son el target.

            const isHidden = (targetIndex !== null && targetIndex != idx);
            const hiddenClass = isHidden ? 'd-none' : '';

            // Al ser el único visible, siempre va expandido (aunque ahora todos se renderizan expanded pero hidden)
            let isExpanded = 'show';

            const btnCollapsed = ''; // Siempre expandido

            // 1. RECUPERAR ESTADO MESA/LLEVAR (Booleano)
            let isTakeaway = false;
            const infoMod = savedMods.find(m => m.sub_item_index == idx && m.modifier_type == 'info');
            if (infoMod) {
                isTakeaway = (infoMod.is_takeaway == 1);
            }

            const checkTak = isTakeaway ? 'checked' : '';
            const checkDin = !isTakeaway ? 'checked' : '';

            // 2. Determinar Columnas Activas (Quitar, Extras, Contornos)
            const hasRemovables = item.removables.length > 0;
            const hasExtras = item.available_extras.length > 0;
            const hasSides = item.available_sides.length > 0;

            // En modal-xl (12 cols), distribuimos así: 
            // Quitar: 2, Extras: 2, Contornos: 8 (Si están todos)
            let qClass = 'col-md-2 border-end';
            let eClass = 'col-md-2 border-end';
            let sClass = 'col-md-8';

            if (!hasRemovables) {
                eClass = 'col-md-3 border-end';
                sClass = 'col-md-9';
            }
            if (!hasExtras) {
                qClass = 'col-md-3 border-end';
                sClass = 'col-md-9';
            }
            if (!hasRemovables && !hasExtras) {
                sClass = 'col-md-12';
            }

            // Displays
            const displayRem = hasRemovables ? '' : 'd-none';
            const displayExt = hasExtras ? '' : 'd-none';

            // GENERAR HTML - Notar 'hiddenClass' en el primer div
            let html = `
                <div class="accordion-item mb-2 border shadow-sm ${hiddenClass}">
                    <h2 class="accordion-header">
                        <button class="accordion-button ${btnCollapsed} fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#c${idx}">
                            <span class="badge bg-secondary me-2">#${idx + 1}</span> ${item.name}
                        </button>
                    </h2>
                    <div id="c${idx}" class="accordion-collapse collapse ${isExpanded}" data-bs-parent="#modalItemsContainer">
                        <div class="accordion-body bg-white">

                            <div class="d-flex justify-content-center gap-3 mb-3 p-2 bg-light rounded border">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="cons_${idx}" value="takeaway" ${checkTak}>
                                    <label class="form-check-label fw-bold">🥡 Para Llevar</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="cons_${idx}" value="dine_in" ${checkDin}>
                                    <label class="form-check-label fw-bold">🍽️ Comer Aquí</label>
                                </div>
                            </div>

                            <div class="row">
                                <div class="${qClass} ${displayRem}">
                                    <h6 class="text-danger small fw-bold border-bottom pb-1">❌ QUITAR</h6>
                                    <div class="mt-2">
                                        ${item.removables.map(ing => {
                const isChecked = savedMods.some(m =>
                    m.sub_item_index == idx &&
                    m.modifier_type == 'remove' &&
                    m.component_id == ing.id
                );
                return `
                                            <div class="form-check mb-1">
                                                <input class="form-check-input remove-chk border-danger" type="checkbox" value="${ing.id}" data-idx="${idx}" ${isChecked ? 'checked' : ''}>
                                                <label class="form-check-label small">${ing.name}</label>
                                            </div>`;
            }).join('')}
                                    </div>
                                </div>

                                <div class="${eClass} ${displayExt}">
                                    <h6 class="text-success small fw-bold border-bottom pb-1">➕ EXTRAS</h6>
                                    <div class="mt-2">
                                        ${(hasExtras)
                    ? item.available_extras.map(ext => {
                        const isChecked = savedMods.some(m =>
                            m.sub_item_index == idx &&
                            m.modifier_type == 'add' &&
                            m.component_id == ext.id
                        );
                        return `
                                                <div class="form-check mb-1">
                                                    <input class="form-check-input add-chk border-success" type="checkbox" value="${ext.id}" data-type="${ext.type}" data-qty="${ext.qty}" data-price="${ext.price}" data-idx="${idx}" ${isChecked ? 'checked' : ''}>
                                                    <label class="form-check-label small d-flex justify-content-between pe-2">
                                                        <span>${ext.name}</span>
                                                        <span class="text-success fw-bold">+$${ext.price}</span>
                                                    </label>
                                                </div>`;
                    }).join('')
                    : '<span class="text-muted small fst-italic">No hay extras disponibles.</span>'
                }
                                    </div>
                                </div>

                                <div class="${sClass}">
                                    <h6 class="text-primary small fw-bold border-bottom pb-1">🍱 CONTORNOS</h6>
                                    <div class="mt-2" id="sides-area-${idx}">
                                        ${(item.available_sides && item.available_sides.length > 0)
                    ? (() => {
                        // 1. Inicializar Estado
                        currentCartSides[idx] = [];
                        
                        // Cargar seleccionados desde savedMods
                        const mySides = savedMods.filter(m => m.sub_item_index == idx && m.modifier_type == 'side');
                        mySides.forEach(ms => {
                            const original = item.available_sides.find(s => s.component_id == ms.component_id && s.component_type == ms.component_type);
                            if (original) {
                                currentCartSides[idx].push({...original});
                            }
                        });

                        // 2. Renderizar UI
                        const logic = item.contour_logic_type || 'standard';
                        currentCartLogics[idx] = { logic: logic, max: item.max_sides, name: item.name };

                        const labelText = (logic === 'standard') ? 'Obligatorios:' : 'Máximo:';
                        const labelClass = (logic === 'standard') ? 'text-danger fw-bold' : '';

                        return `
                            <div class="alert alert-light border small py-1 mb-2 d-flex justify-content-between align-items-center">
                                <span class="${labelClass}">${labelText} <strong id="max-label-${idx}">${item.max_sides}</strong></span>
                                <span class="badge bg-primary fs-6" id="count-badge-${idx}">${currentCartSides[idx].length}</span>
                            </div>

                            <div class="row g-2">
                                <!-- IZQUIERDA: DISPONIBLES (2 Columnas) -->
                                <div class="col-8 border-end pe-2">
                                    <div class="row row-cols-2 g-1 custom-scroll" style="max-height: 250px; overflow-y: auto; padding-right: 4px;">
                                        ${item.available_sides.map(side => {
                                            const priceLabel = parseFloat(side.price_override) > 0 ? ` +$${parseFloat(side.price_override)}` : '';
                                            const isOutOfStock = (side.stock <= 0);
                                            const disabledAttr = isOutOfStock ? 'disabled' : '';
                                            const opacityClass = isOutOfStock ? 'opacity-50' : '';
                                            const outOfStockLabel = isOutOfStock ? ' <span class="badge bg-danger p-1" style="font-size: 0.5rem;">AGOTADO</span>' : '';
                                            
                                            return `
                                            <div class="col">
                                                <button type="button" class="btn btn-side-option btn-sm w-100 text-start text-truncate py-2 px-1 ${opacityClass}" 
                                                    title="${side.item_name} ${isOutOfStock ? '(Sin existencias)' : ''}"
                                                    ${disabledAttr}
                                                    onclick='addSideToCartModal(${JSON.stringify(side)}, ${idx}, ${item.max_sides})'>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fa ${isOutOfStock ? 'fa-times-circle' : 'fa-plus-circle'} small me-1 opacity-75"></i>
                                                        <div class="flex-grow-1 text-wrap lh-1" style="font-size: 0.65rem;">
                                                            ${side.item_name}${outOfStockLabel}
                                                            <div class="text-info" style="font-size: 0.6rem;">${priceLabel}</div>
                                                        </div>
                                                    </div>
                                                </button>
                                            </div>`;
                                        }).join('')}

                                    </div>
                                </div>

                                <!-- DERECHA: SELECCIONADOS -->
                                <div class="col-4 ps-2">
                                    <div id="selected-list-${idx}" class="list-group list-group-flush small custom-scroll" style="max-height: 250px; overflow-y: auto; padding-right: 2px;">
                                        <!-- Se llenará via JS al cargar -->
                                    </div>
                                </div>
                            </div>
                        `;
                    })()
                    : '<div class="alert alert-secondary p-1 small text-center">Sin opciones</div>'
                }
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>`;
            container.innerHTML += html;

            // Renderizar lista inicial de seleccionados
            setTimeout(() => renderSelectedList(idx), 0);
        });
    }

    // --- FUNCIONES NUEVAS PARA SHOPPING LIST EN MODAL ---

    function addSideToCartModal(side, idx, max) {
        if (!currentCartSides[idx]) currentCartSides[idx] = [];
        
        // Bloqueo por Stock
        if (side.stock <= 0) {
            alert("Este sabor se ha agotado y no puede ser seleccionado.");
            return;
        }
        
        if (currentCartSides[idx].length >= max) {

             // Feedback visual rápido
             const badge = document.getElementById(`count-badge-${idx}`);
             if(badge) {
                 badge.classList.remove('bg-primary');
                 badge.classList.add('bg-danger');
                 setTimeout(() => {
                     badge.classList.remove('bg-danger');
                     badge.classList.add('bg-primary');
                 }, 300);
             }
             return;
        }

        currentCartSides[idx].push(side);
        renderSelectedList(idx);
    }

    function removeSideFromCartModal(idx, arrayIndex) {
        if (!currentCartSides[idx]) return;
        currentCartSides[idx].splice(arrayIndex, 1);
        renderSelectedList(idx);
    }

    function renderSelectedList(idx) {
        const list = document.getElementById(`selected-list-${idx}`);
        const badge = document.getElementById(`count-badge-${idx}`);
        
        if (!list) return;

        const sides = currentCartSides[idx] || [];
        if (badge && currentCartLogics[idx]) {
            const l = currentCartLogics[idx];
            badge.textContent = sides.length;

            if (l.logic === 'standard') {
                if (sides.length === l.max) {
                    badge.classList.remove('bg-primary', 'bg-danger');
                    badge.classList.add('bg-success');
                } else {
                    badge.classList.remove('bg-primary', 'bg-success');
                    badge.classList.add('bg-danger');
                }
            } else {
                badge.classList.remove('bg-danger', 'bg-success');
                badge.classList.add('bg-primary');
            }
        }

        if (sides.length === 0) {
            list.innerHTML = '<div class="text-center text-muted fst-italic" style="font-size:0.75rem;">Vacio</div>';
            return;
        }

        list.innerHTML = '';
        sides.forEach((s, i) => {
            const item = document.createElement('div');
            item.className = 'list-group-item p-1 d-flex justify-content-between align-items-center bg-light mb-1 border rounded';
            item.innerHTML = `
                <span class="text-truncate" style="max-width: 80%; font-size:0.75rem;" title="${s.item_name}">${s.item_name}</span>
                <button type="button" class="btn btn-link text-danger p-0 opacity-50 hover-opacity-100" onclick="removeSideFromCartModal(${idx}, ${i})">
                    <i class="fa fa-times small"></i>
                </button>
            `;
            list.appendChild(item);
        });
    }

    function validateSideLimit(idx) {
        const checks = document.querySelectorAll(`.side-chk[data-idx="${idx}"]`);
        if (checks.length === 0) return;

        const checked = document.querySelectorAll(`.side-chk[data-idx="${idx}"]:checked`);

        // Obtenemos el máximo del primer checkbox (todos tienen el data-max)
        const max = parseInt(checks[0].dataset.max);

        if (checked.length >= max) {
            checks.forEach(c => { if (!c.checked) c.disabled = true; });
        } else {
            checks.forEach(c => c.disabled = false);
        }
    }

    function submitModifiers() {
        const items = [];
        const count = document.getElementById('modalItemsContainer').children.length;

        for (let i = 0; i < count; i++) {
            const radio = document.querySelector(`input[name="cons_${i}"]:checked`);
            if (!radio) continue;

            const consumption = radio.value;
            const remove = [];
            const add = [];
            const sides = [];

            document.querySelectorAll(`.remove-chk[data-idx="${i}"]:checked`).forEach(el => remove.push(parseInt(el.value)));
            document.querySelectorAll(`.add-chk[data-idx="${i}"]:checked`).forEach(el => add.push({
                id: parseInt(el.value),
                type: el.dataset.type,
                qty: parseFloat(el.dataset.qty),
                price: parseFloat(el.dataset.price)
            }));
            
            // NUEVA LÓGICA: Recoger contornos desde currentCartSides
            if (currentCartSides[i]) {
                const logicData = currentCartLogics[i];
                
                // VALIDACIÓN: Solo alertar si el ítem está VISIBLE (no tiene d-none)
                // Esto evita que configurar un refresco sea bloqueado por la falta de contornos en la hamburguesa.
                const itemContainer = document.querySelector(`.accordion-item:has(#c${i})`);
                const isVisible = itemContainer && !itemContainer.classList.contains('d-none');

                if (isVisible && logicData && logicData.logic === 'standard' && currentCartSides[i].length < logicData.max) {
                    alert(`Debes seleccionar exactamente ${logicData.max} contornos para: ${logicData.name}`);
                    return; // Bloquear envío
                }

                currentCartSides[i].forEach(s => {
                    sides.push({
                        id: parseInt(s.component_id),
                        type: s.component_type,
                        qty: parseFloat(s.quantity),
                        price: parseFloat(s.price_override)
                    });
                });
            }

            items.push({ index: i, consumption: consumption, remove: remove, add: add, sides: sides });
        }

        document.getElementById('modalModifiersJson').value = JSON.stringify(items);
        document.getElementById('modalForm').submit();
    }
</script>

<?php require_once '../templates/footer.php'; ?>