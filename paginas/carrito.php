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
require_once '../templates/menu.php';
?>

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
                        foreach ($cartItems as $item) {
                            $cId = $item['id'];

                            if ($item['product_type'] == 'compound') {
                                $components = $productManager->getProductComponents($item['product_id']);
                                $idx = 0;
                                foreach ($components as $comp) {
                                    $qty = intval($comp['quantity']);
                                    // Determinar datos del sub-producto
                                    // OJO: Si es 'manufactured', deberíamos buscar en su tabla,
                                    // pero por ahora asumimos que el PM lo maneja o usaremos un placeholder si falta.
                    
                                    if ($comp['component_type'] == 'product') {
                                        $subP = $productManager->getProductById($comp['component_id']);
                                        $name = $subP['name'];
                                        $img = !empty($subP['image_url']) ? $subP['image_url'] : 'img/no-image.png';
                                    } elseif ($comp['component_type'] == 'manufactured') {
                                        // Hack rápido para obtener nombre/img de manufacturados
                                        // (Idealmente PM debería tener getManufacturedById)
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
                                                <div class="d-flex align-items-center h-100">
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
                                        <?php foreach ($cartItems as $item): ?>
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
                            <a href="checkout.php" class="btn btn-success px-4 fw-bold shadow">
                                Pagar <i class="fa fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="modifierModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
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

    function renderComboItems(data, targetIndex = null) {
        const container = document.getElementById('modalItemsContainer');
        container.innerHTML = '';

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

            // CALCULAR LAYOUT DINÁMICO
            // Si una columna no tiene datos, la ocultamos y las demás crecen.
            const hasRemovables = item.removables.length > 0;
            const hasExtras = item.available_extras && item.available_extras.length > 0;
            const hasSides = true; // Siempre mostramos, aunque diga "Sin opciones"

            let activeCols = 1; // Sides siempre está
            if (hasRemovables) activeCols++;
            if (hasExtras) activeCols++;

            let colClass = 'col-md-4'; // Default (3 cols)
            if (activeCols === 2) colClass = 'col-md-6';
            if (activeCols === 1) colClass = 'col-md-12';

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
                                <div class="${colClass} border-end ${displayRem}">
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

                                <div class="${colClass} border-end ${displayExt}">
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

                                <div class="${colClass}">
                                    <h6 class="text-primary small fw-bold border-bottom pb-1">🍱 CONTORNOS</h6>
                                    <div class="mt-2">
                                        ${(item.available_sides && item.available_sides.length > 0)
                    ? `
                                            <div class="alert alert-light border small py-1 mb-2 d-flex justify-content-between align-items-center">
                                                <span>Máximo Permitido:</span>
                                                <span class="badge bg-primary fs-6">${item.max_sides}</span>
                                            </div>
                                            ` + item.available_sides.map(side => {
                        const isChecked = savedMods.some(m =>
                            m.sub_item_index == idx &&
                            m.modifier_type == 'side' &&
                            m.component_id == side.component_id &&
                            m.component_type == side.component_type
                        );
                        return `
                                                <div class="form-check mb-1">
                                                    <input class="form-check-input side-chk border-info" type="checkbox" 
                                                        value="${side.component_id}" 
                                                        data-type="${side.component_type}" 
                                                        data-qty="${side.quantity}" 
                                                        data-price="${side.price_override}" 
                                                        data-idx="${idx}" 
                                                        data-max="${item.max_sides}"
                                                        onchange="validateSideLimit(${idx})"
                                                        ${isChecked ? 'checked' : ''}>
                                                    <label class="form-check-label small d-flex justify-content-between pe-2">
                                                        <span>${side.item_name}</span>
                                                        ${parseFloat(side.price_override) > 0 ? `<span class="text-info fw-bold">+$${side.price_override}</span>` : ''}
                                                    </label>
                                                </div>`;
                    }).join('')
                    : '<div class="alert alert-secondary p-1 small text-center">Sin opciones</div>'
                }
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>`;
            container.innerHTML += html;

            // VALIDACIÓN INICIAL: Correr validación para deshabilitar si ya se cumplió el máximo
            setTimeout(() => validateSideLimit(idx), 100);
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
            document.querySelectorAll(`.side-chk[data-idx="${i}"]:checked`).forEach(el => sides.push({
                id: parseInt(el.value),
                type: el.dataset.type,
                qty: parseFloat(el.dataset.qty),
                price: parseFloat(el.dataset.price)
            }));

            items.push({ index: i, consumption: consumption, remove: remove, add: add, sides: sides });
        }

        document.getElementById('modalModifiersJson').value = JSON.stringify(items);
        document.getElementById('modalForm').submit();
    }
</script>

<?php require_once '../templates/footer.php'; ?>