<?php
session_start();
require_once '../templates/autoload.php';
require_once '../templates/header.php';
require_once '../templates/menu.php';

$userId = $_SESSION['user_id'] ?? null;

if ($userId) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $cartId = $_POST['cart_id'] ?? 0;

        if ($action === 'update_qty') {
            $cartManager->updateCartQuantity($cartId, $_POST['quantity']);
        } elseif ($action === 'remove') {
            $cartManager->removeFromCart($cartId);
        } elseif ($action === 'clear') {
            $cartManager->emptyCart($userId);
        }
        // GUARDAR PERSONALIZACIÓN (Combo o Simple)
        elseif ($action === 'save_modifiers') {
            $json = $_POST['modifiers_json'];
            $data = json_decode($json, true); // Array de configuraciones

            // Usamos una función especial que maneja la estructura compleja
            // Nota: Para no cambiar el CartManager ahora, formateamos aquí para que él entienda.
            // CartManager espera ['add'=>[], 'remove'=>[], 'note'=>'']

            // Aplanamos la estructura para guardarla relacionalmente
            $flatMods = ['add'=>[], 'remove'=>[], 'note'=>''];
            $noteText = [];

            foreach ($data as $subItem) {
                // Guardamos la preferencia de consumo como nota de texto por ahora
                // "Hamburguesa 1: Comer Aquí"
                $noteText[] = $subItem['name'] . ": " . ($subItem['consumption']=='dine_in' ? 'Comer Aquí' : 'Llevar');

                // Unimos los arrays de IDs
                foreach($subItem['remove'] as $id) $flatMods['remove'][] = $id;
                foreach($subItem['add'] as $obj) $flatMods['add'][] = $obj; // obj tiene {id, price}
            }
            $flatMods['note'] = implode(" | ", $noteText) . " | Nota: " . $_POST['general_note'];

            $cartManager->updateItemModifiers($cartId, $flatMods);
        }
    }

    $cartItems = $cartManager->getCart($userId);
    $total = $cartManager->calculateTotal($cartItems);
    ?>

    <div class="container mt-5 mb-5">
        <h2 class="text-center mb-4"><i class="fa fa-shopping-cart text-primary"></i> Tu Pedido</h2>

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
                                    <th>Producto</th>
                                    <th>Detalles / Personalización</th>
                                    <th class="text-center">Cant.</th>
                                    <th class="text-end">Precio</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartItems as $item): ?>
                                    <tr>
                                        <td class="fw-bold">
                                            <?= htmlspecialchars($item['name']) ?>
                                            <?php if($item['product_type']=='compound'): ?>
                                                <br><span class="badge bg-warning text-dark" style="font-size:0.7em">COMBO</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php if ($item['product_type'] == 'prepared' || $item['product_type'] == 'compound'): ?>
                                                <button class="btn btn-sm btn-outline-primary mb-2" onclick="openModifierModal(<?= $item['id'] ?>)">
                                                    <i class="fa fa-sliders-h"></i> Personalizar
                                                </button>
                                                <?php if (!empty($item['modifiers_desc'])): ?>
                                                    <div class="small text-muted bg-light p-1 rounded">
                                                        <?php foreach($item['modifiers_desc'] as $d) echo "<div>$d</div>"; ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <small class="text-muted">Producto Simple</small>
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center">
                                            <form method="post" class="d-flex justify-content-center">
                                                <input type="hidden" name="action" value="update_qty">
                                                <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                                <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" class="form-control form-control-sm text-center" style="width: 70px;" onchange="this.form.submit()">
                                            </form>
                                        </td>

                                        <td class="text-end fw-bold text-success">$<?= number_format($item['total_price'], 2) ?></td>

                                        <td class="text-end">
                                            <form method="post">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                                <button class="btn btn-sm text-danger border-0"><i class="fa fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light text-end">
                    <h4>Total: $<?= number_format($total['total_usd'], 2) ?></h4>
                    <a href="checkout.php" class="btn btn-success btn-lg mt-2">Pagar <i class="fa fa-check"></i></a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="modifierModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form method="post" class="modal-content" id="modalForm">
                <input type="hidden" name="action" value="save_modifiers">
                <input type="hidden" name="cart_id" id="modalCartId">
                <input type="hidden" name="modifiers_json" id="modalModifiersJson">

                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Configurar: <span id="modalProductName"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <div id="modalLoading" class="text-center py-5"><div class="spinner-border text-primary"></div></div>

                    <div id="modalItemsContainer" class="accordion" style="display:none;"></div>

                    <div class="mt-3">
                        <label class="form-label fw-bold">Nota General:</label>
                        <input type="text" name="general_note" class="form-control" placeholder="Ej: Poca sal en todo...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="submitModifiers()">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentData = null;

        function openModifierModal(cartId) {
            document.getElementById('modalCartId').value = cartId;
            document.getElementById('modalLoading').style.display = 'block';
            document.getElementById('modalItemsContainer').style.display = 'none';

            const modal = new bootstrap.Modal(document.getElementById('modifierModal'));
            modal.show();

            fetch(`../ajax/get_cart_item.php?cart_id=${cartId}`)
                .then(r => r.json())
                .then(data => {
                    if(data.error) { alert(data.error); return; }
                    currentData = data;
                    document.getElementById('modalProductName').textContent = data.product_name;
                    renderComboItems(data);

                    document.getElementById('modalLoading').style.display = 'none';
                    document.getElementById('modalItemsContainer').style.display = 'block';
                });
        }

        function renderComboItems(data) {
            const container = document.getElementById('modalItemsContainer');
            container.innerHTML = '';

            data.sub_items.forEach((item, idx) => {
                const isExpanded = idx === 0 ? 'show' : ''; // Primer item abierto

                // Checkear si ya teníamos modificadores guardados (Lógica básica de recuperación)
                // Aquí podrías cruzar data.saved_mods con item.removables para pre-marcar checkboxes

                let html = `
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button ${idx!==0?'collapsed':''}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${idx}">
                                <strong>${item.name}</strong> <span class="badge bg-secondary ms-2">#${idx+1}</span>
                            </button>
                        </h2>
                        <div id="collapse${idx}" class="accordion-collapse collapse ${isExpanded}" data-bs-parent="#modalItemsContainer">
                            <div class="accordion-body">

                                <div class="d-flex justify-content-center gap-3 mb-3 border-bottom pb-3">
                                    <div class="form-check">
                                        <input class="form-check-input cons-radio" type="radio" name="cons_${idx}" value="takeaway" checked data-index="${idx}">
                                        <label class="form-check-label fw-bold">🥡 Para Llevar</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input cons-radio" type="radio" name="cons_${idx}" value="dine_in" data-index="${idx}">
                                        <label class="form-check-label fw-bold">🍽️ Comer Aquí</label>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <h6 class="text-danger small fw-bold">❌ QUITAR</h6>
                                        ${item.removables.length === 0 ? '<small class="text-muted">-</small>' : ''}
                                        ${item.removables.map(ing => `
                                            <div class="form-check">
                                                <input class="form-check-input remove-chk" type="checkbox" value="${ing.id}" data-index="${idx}">
                                                <label class="form-check-label small">${ing.name}</label>
                                            </div>
                                        `).join('')}
                                    </div>
                                    <div class="col-6 border-start">
                                        <h6 class="text-success small fw-bold">➕ EXTRAS</h6>
                                        <div style="max-height:150px; overflow-y:auto;">
                                            ${data.available_extras.map(ext => `
                                                <div class="form-check">
                                                    <input class="form-check-input add-chk" type="checkbox" value="${ext.id}" data-price="${ext.price}" data-index="${idx}">
                                                    <label class="form-check-label small d-flex justify-content-between">
                                                        <span>${ext.name}</span>
                                                        <span class="text-muted">+$${ext.price}</span>
                                                    </label>
                                                </div>
                                            `).join('')}
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
            let finalConfig = [];

            // Recorrer los datos originales para mantener el orden
            currentData.sub_items.forEach((item, idx) => {
                let config = {
                    name: item.name,
                    consumption: document.querySelector(`input[name="cons_${idx}"]:checked`).value,
                    remove: [],
                    add: []
                };

                // Recoger IDs de los checkboxes marcados EN ESTE índice
                document.querySelectorAll(`.remove-chk[data-index="${idx}"]:checked`).forEach(el => {
                    config.remove.push(parseInt(el.value));
                });
                document.querySelectorAll(`.add-chk[data-index="${idx}"]:checked`).forEach(el => {
                    config.add.push({ id: parseInt(el.value), price: parseFloat(el.dataset.price) });
                });

                finalConfig.push(config);
            });

            document.getElementById('modalModifiersJson').value = JSON.stringify(finalConfig);
            document.getElementById('modalForm').submit();
        }
    </script>

<?php
} else { header("Location: login.php"); }
require_once '../templates/footer.php';
?>
