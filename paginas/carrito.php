<?php
session_start();
// -------------------------------------------------------------------------
// 1. LÓGICA Y CONTROLADOR
// -------------------------------------------------------------------------
require_once '../templates/autoload.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) { header("Location: login.php"); exit; }

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
                header("Location: carrito.php"); exit;
            } else {
                throw new Exception($result);
            }
        }

        // OTRAS ACCIONES
        if ($action === 'update_qty') $cartManager->updateCartQuantity($cartId, $_POST['quantity']);
        if ($action === 'remove') $cartManager->removeFromCart($cartId);
        if ($action === 'clear') $cartManager->emptyCart($userId);

        header("Location: carrito.php"); exit;

    } catch (Exception $e) {
        $error_msg = "Error: " . $e->getMessage();
    }
}

$cartItems = $cartManager->getCart($userId);
$total = $cartManager->calculateTotal($cartItems);

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
        <div class="card shadow">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 40%;">Producto</th>
                                <th style="width: 25%;">Detalles</th>
                                <th class="text-center" style="width: 15%;">Cant.</th>
                                <th class="text-end" style="width: 15%;">Precio</th>
                                <th style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold fs-5"><?= htmlspecialchars($item['name']) ?></div>
                                        <?php if($item['product_type']=='compound'): ?>
                                            <span class="badge bg-warning text-dark" style="font-size:0.7em">COMBO</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($item['product_type'] != 'simple'): ?>
                                            <button class="btn btn-sm btn-outline-primary w-100 mb-2"
                                                    onclick="openModifierModal(<?= $item['id'] ?>)">
                                                <i class="fa fa-sliders-h me-1"></i> Configurar
                                            </button>

                                            <?php if (!empty($item['modifiers_desc'])): ?>
                                                <div class="small text-muted bg-light p-2 rounded border" style="font-size: 0.85rem;">
                                                    <?php foreach($item['modifiers_desc'] as $d) echo $d; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <small class="text-muted fst-italic">Sin opciones</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <form method="post">
                                            <input type="hidden" name="action" value="update_qty">
                                            <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                            <input type="number" name="quantity" value="<?= $item['quantity'] ?>" class="form-control form-control-sm text-center fw-bold" style="width:60px; margin:auto;" onchange="this.form.submit()">
                                        </form>
                                    </td>
                                    <td class="text-end fw-bold text-success">$<?= number_format($item['total_price'], 2) ?></td>
                                    <td class="text-end">
                                        <form method="post">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                            <button class="btn btn-link text-danger p-0"><i class="fa fa-trash fa-lg"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <form method="post"><input type="hidden" name="action" value="clear"><button class="btn btn-outline-danger btn-sm">Vaciar</button></form>
                    <div class="text-end">
                        <h4 class="text-success fw-bold m-0">$<?= number_format($total['total_usd'], 2) ?></h4>
                        <a href="checkout.php" class="btn btn-success mt-2 px-4 shadow">Pagar</a>
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
                    <input type="text" name="general_note" id="modalGeneralNote" class="form-control" placeholder="Ej: Sin servilletas...">
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
    function openModifierModal(cartId) {
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
                if(data.error) { alert(data.error); return; }

                // Debug en consola para verificar qué llega
                console.log("Datos del Carrito:", data);

                // Restaurar Nota General
                if (data.saved_mods) {
                    const noteRow = data.saved_mods.find(m => m.sub_item_index == -1 && m.modifier_type == 'info');
                    if (noteRow) document.getElementById('modalGeneralNote').value = noteRow.note;
                }

                renderComboItems(data);

                document.getElementById('modalLoading').style.display = 'none';
                document.getElementById('modalItemsContainer').style.display = 'block';
            })
            .catch(err => {
                console.error(err);
                alert("Error de conexión al cargar datos.");
            });
    }

    function renderComboItems(data) {
        const container = document.getElementById('modalItemsContainer');
        container.innerHTML = '';

        // Esta es la clave: Usamos los datos guardados que vienen del AJAX
        const savedMods = data.saved_mods || [];

        data.sub_items.forEach((item, idx) => {
            const isExpanded = idx === 0 ? 'show' : '';
            const btnCollapsed = idx === 0 ? '' : 'collapsed';

            // 1. RECUPERAR ESTADO MESA/LLEVAR (Booleano)
            let isTakeaway = false;
            const infoMod = savedMods.find(m => m.sub_item_index == idx && m.modifier_type == 'info');
            if (infoMod) {
                isTakeaway = (infoMod.is_takeaway == 1);
            }

            const checkTak = isTakeaway ? 'checked' : '';
            const checkDin = !isTakeaway ? 'checked' : '';

            // GENERAR HTML
            let html = `
                <div class="accordion-item mb-2 border shadow-sm">
                    <h2 class="accordion-header">
                        <button class="accordion-button ${btnCollapsed} fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#c${idx}">
                            <span class="badge bg-secondary me-2">#${idx+1}</span> ${item.name}
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
                                <div class="col-md-6 border-end">
                                    <h6 class="text-danger small fw-bold border-bottom pb-1">❌ QUITAR</h6>
                                    <div class="mt-2">
                                        ${item.removables.map(ing => {
                                            // Lógica corregida: Busca en savedMods comparando IDs
                                            // Usamos == para que no falle si uno es string y otro int
                                            const isChecked = savedMods.some(m =>
                                                m.sub_item_index == idx &&
                                                m.modifier_type == 'remove' &&
                                                m.raw_material_id == ing.id
                                            );
                                            return `
                                            <div class="form-check mb-1">
                                                <input class="form-check-input remove-chk border-danger" type="checkbox" value="${ing.id}" data-idx="${idx}" ${isChecked?'checked':''}>
                                                <label class="form-check-label small">${ing.name}</label>
                                            </div>`;
                                        }).join('')}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <h6 class="text-success small fw-bold border-bottom pb-1">➕ EXTRAS</h6>
                                    <div class="mt-2" style="max-height:150px; overflow-y:auto;">
                                        ${
                                            (item.available_extras && item.available_extras.length > 0)
                                            ? item.available_extras.map(ext => {
                                                // Lógica corregida: Busca en savedMods
                                                const isChecked = savedMods.some(m =>
                                                    m.sub_item_index == idx &&
                                                    m.modifier_type == 'add' &&
                                                    m.raw_material_id == ext.id
                                                );
                                                return `
                                                <div class="form-check mb-1">
                                                    <input class="form-check-input add-chk border-success" type="checkbox" value="${ext.id}" data-price="${ext.price}" data-idx="${idx}" ${isChecked?'checked':''}>
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
                            </div>

                        </div>
                    </div>
                </div>`;
            container.innerHTML += html;
        });
    }

    function submitModifiers() {
        const items = [];
        const count = document.getElementById('modalItemsContainer').children.length;

        for(let i=0; i<count; i++) {
            const radio = document.querySelector(`input[name="cons_${i}"]:checked`);
            if(!radio) continue;

            const consumption = radio.value;
            const remove = [];
            const add = [];

            document.querySelectorAll(`.remove-chk[data-idx="${i}"]:checked`).forEach(el => remove.push(parseInt(el.value)));
            document.querySelectorAll(`.add-chk[data-idx="${i}"]:checked`).forEach(el => add.push({id: parseInt(el.value), price: parseFloat(el.dataset.price)}));

            items.push({ index: i, consumption: consumption, remove: remove, add: add });
        }

        document.getElementById('modalModifiersJson').value = JSON.stringify(items);
        document.getElementById('modalForm').submit();
    }
</script>

<?php require_once '../templates/footer.php'; ?>
