<?php
session_start();

require_once '../templates/autoload.php';
require_once '../templates/pos_check.php'; // SEGURIDAD POS

// --- LÓGICA DE CONTROL DE CAJA ---
$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user_role'] ?? null;
$hasOpenSession = false;
$cajaAlert = "";

if ($userId) {
    $hasOpenSession = $cashRegisterManager->hasOpenSession($userId);

    // REGLA 1: Si es Cajero y NO tiene caja, ¡FUERA! A abrir caja.
    if ($userRole === 'user' && !$hasOpenSession) {
        header("Location: apertura_caja.php");
        exit;
    }

    // REGLA 2: Si es Admin y NO tiene caja, advertencia.
    if ($userRole === 'admin' && !$hasOpenSession) {
        $cajaAlert = '<div class="alert alert-warning text-center m-3">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>Atención Admin:</strong> No tienes una caja abierta.
                        <a href="apertura_caja.php" class="btn btn-sm btn-dark ms-2">Abrir Caja para Vender</a>
                      </div>';
    }
}
// ----------------------------------

// Procesar "Agregar al Carrito"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (!$userId) {
        SessionHelper::setFlash('warning', 'Debes iniciar sesión para realizar esa acción.');
        header('Location: login.php');
        exit;
    }

    // REGLA 3: Nadie puede vender sin caja abierta (ni siquiera el admin si intenta agregar)
    if (!$hasOpenSession) {
        SessionHelper::setFlash('error', '⚠️ ERROR: Debes abrir una caja (Turno) antes de realizar ventas.');
        header('Location: apertura_caja.php');
        exit;
    }

    $productId = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $sides = isset($_POST['sides']) ? $_POST['sides'] : [];

    // Convertir sides al formato de modificadores esperado por CartManager
    $modifiers = [];
    if (!empty($sides)) {
        // Asumimos que los contornos se aplican al primer ítem (o a todos si qty > 1)
        // Para simplificar, si hay qty > 1, aplicamos mismos contornos a todos
        $modifiers['items'] = [];
        for ($i = 0; $i < $quantity; $i++) {
            $modifiers['items'][$i] = [
                'index' => $i,
                'consumption' => 'dine_in', // Default
                'sides' => []
            ];
            foreach ($sides as $s) {
                if (isset($s['id'])) {
                    $modifiers['items'][$i]['sides'][] = [
                        'type' => $s['type'],
                        'id' => $s['id'],
                        'qty' => $s['qty'],
                        'price' => $s['price']
                    ];
                }
            }
        }
    }

    $result = $cartManager->addToCart($userId, $productId, $quantity, $modifiers);

    if ($result === true) {
        SessionHelper::setFlash('success', 'Producto agregado al carrito.');
        header('Location: carrito.php');
        exit;
    } else {
        // El resultado es un string de error (ej: "Stock insuficiente")
        SessionHelper::setFlash('error', $result);
        header('Location: tienda.php'); // Volver a la tienda para ver el error
        exit;
    }
}

$search = $_GET['search'] ?? '';
$catId = $_GET['cat'] ?? null;

if (!empty($search)) {
    $products = $productManager->searchProducts($search, $catId);
} else {
    $products = $productManager->getAllProducts($catId);
}

$categories = $productManager->getCategories();

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<?= $cajaAlert ?>

<div class="container mt-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>🛍️ Catálogo</h2>
        <div class="d-flex gap-2">
            <!-- Botón Ver Carrito -->
            <!-- Botón Ver Carrito (Con Validación) -->
            <button class="btn btn-primary position-relative shadow-sm" onclick="goToCart()">
                <i class="fa fa-shopping-cart"></i> Ver Carrito
                <?php
                // Un pequeño badge si hay items
                $count = 0;
                ?>
            </button>

            <!-- Botón Nuevo Cliente (Insertado al medio) -->
            <button class="btn btn-outline-success shadow-sm fw-bold" type="button" onclick="openNewClientModal()">
                <i class="fa fa-user-plus"></i> Nuevo Cliente
            </button>

            <!-- Botón Abonar Crédito -->
            <button class="btn btn-success shadow-sm" onclick="openDebtModal()">
                <i class="fa fa-hand-holding-usd"></i> Abonar Crédito
            </button>
        </div>
    </div>

    <!-- Layout de Buscadores: 50/50 -->
    <div class="row mb-4 g-3">

        <!-- 1. Buscador de Clientes -->
        <div class="col-md-6" data-no-search="true">
            <div class="card shadow-sm border-primary h-100">
                <div class="card-header bg-primary text-white py-1">
                    <small><i class="fa fa-user-circle"></i> Datos del Cliente</small>
                </div>
                <div class="card-body py-2 d-flex flex-column justify-content-center">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-0 fw-bold">Cliente:</span>
                        <!-- Select2 para buscar cliente AJAX -->
                        <select id="posClientSelect" class="form-select" style="width: 100%"></select>
                    </div>
                    <!-- Info Cliente Activo -->
                    <div id="posClientInfo" class="mt-2 text-center small fw-bold text-success border-top pt-1"
                        style="display:none;">
                        <i class="fa fa-check-circle"></i> Cliente Asignado: <span id="posClientNameDisplay"></span>
                        <button class="btn btn-link btn-sm text-danger p-0 ms-2" onclick="clearPosClient()"
                            title="Desvincular"><i class="fa fa-times"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Buscador de Productos -->
        <div class="col-md-6" data-no-search="true">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body py-3 bg-light rounded d-flex align-items-center">
                    <form method="GET" action="tienda.php" class="w-100">
                        <?php if ($catId): ?>
                            <input type="hidden" name="cat" value="<?= htmlspecialchars($catId) ?>">
                        <?php endif; ?>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white text-muted border-end-0"><i
                                    class="fa fa-search"></i></span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0 text-center"
                                placeholder="🔍 Buscar producto..." value="<?= htmlspecialchars($search) ?>"
                                autocomplete="off">
                            <?php if (!empty($search) || $catId): ?>
                                <a href="tienda.php" class="btn btn-secondary border-start-0" title="Limpiar"><i
                                        class="fa fa-times"></i></a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-dark">Buscar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <!-- Pestañas de Categoría -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="nav nav-pills justify-content-center overflow-auto flex-nowrap pb-2 gap-2 border-bottom">
                <a href="tienda.php<?= !empty($search) ? '?search=' . urlencode($search) : '' ?>"
                    class="nav-link border round-pill <?= !$catId ? 'active bg-primary text-white' : 'text-dark bg-light' ?>">
                    <i class="fa fa-th-large me-1"></i> TODOS
                </a>
                <?php foreach ($categories as $cat): ?>
                    <a href="tienda.php?cat=<?= $cat['id'] ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"
                        class="nav-link border round-pill <?= ($catId == $cat['id']) ? 'active bg-primary text-white' : 'text-dark bg-light' ?>">
                        <i class="fa <?= htmlspecialchars($cat['icon'] ?? '') ?> me-1"></i>
                        <?= htmlspecialchars($cat['name'] ?? '') ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="row mt-4 align-items-center">
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $product): ?>
                <div class="col-md-3 my-3">
                    <div class="card h-100 shadow-sm bg-secondary">
                        <div class="text-center p-3">
                            <img src="../<?= htmlspecialchars($product['image_url']) ?>" class="card-img-top rounded"
                                alt="<?= htmlspecialchars($product['name']) ?>" style="max-height: 150px; object-fit: contain;">
                        </div>

                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-center"><?= htmlspecialchars($product['name']) ?></h5>
                            <p class="card-text small text-center flex-grow-1">
                                <?= htmlspecialchars($product['description'] ?? '') ?>
                            </p>

                            <?php
                            $isSimple = ($product['product_type'] === 'simple');
                            $displayStock = $isSimple ? $product['stock'] : $productManager->getVirtualStock($product['id']);
                            $stockBadgeClass = ($displayStock > 0) ? 'bg-info text-dark' : 'bg-danger text-white';
                            ?>
                            <div class="text-center mb-2">
                                <span class="badge <?= $stockBadgeClass ?>">Stock: <?= $displayStock ?></span>
                            </div>

                            <div class="text-center mb-3">
                                <h5 class="text-success">$<?= number_format($product['price_usd'], 2) ?></h5>
                                <small class="text-white"><?= number_format($product['price_ves'], 2) ?> VES</small>
                            </div>

                            <div class="mt-auto">
                                <div class="d-grid">
                                    <form action="#" method="post" class="add-to-cart-form">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fa fa-cart-plus me-2"></i> Agregar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center">No hay productos disponibles.</p>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL DE SELECCIÓN DE CONTORNOS -->
<div class="modal fade" id="modalSides" tabindex="-1">
    <div class="modal-dialog">
        <form id="formSides" method="POST" class="modal-content">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" id="side-product-id">
            <input type="hidden" name="quantity" value="1">

            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fa fa-utensils me-2"></i> Seleccionar Opciones</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border small py-2 mb-3">
                    <i class="fa fa-info-circle text-info"></i> Puedes elegir hasta <strong
                        id="side-max-count"></strong> opciones.
                </div>
                <div id="sides-container" class="list-group">
                    <!-- Dinámico -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-info text-white fw-bold" id="btnConfirmSides">
                    Confirmar y Agregar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DE ABONO DE CRÉDITO -->
<div class="modal fade" id="modalDebtPOS" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">💵 Abonar a Crédito</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- PASO 1: BUSCAR CLIENTE -->
                <div id="step-search">
                    <label class="form-label fw-bold">Buscar Cliente</label>
                    <div class="input-group mb-2">
                        <input type="text" id="debt-client-search" class="form-control"
                            placeholder="Nombre o Cédula...">
                        <button class="btn btn-outline-secondary" onclick="searchDebtClient()">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                    <div id="debt-client-results" class="list-group"></div>
                </div>

                <!-- PASO 2: FORMULARIO PAGO -->
                <div id="step-pay" style="display:none;">
                    <div class="alert alert-info py-2 mb-3">
                        <h6 class="mb-0" id="selected-client-name"></h6>
                        <small>Deuda Total: <strong id="selected-client-debt" class="text-danger"></strong></small>
                    </div>

                    <form id="form-debt-pos">
                        <input type="hidden" id="p_client_id">

                        <div class="mb-3">
                            <label class="form-label">Monto a Abonar ($ USD)</label>
                            <input type="number" step="0.01" id="p_amount_usd" class="form-control form-control-lg"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Método de Pago</label>
                            <select id="p_payment_method" class="form-select" required>
                                <option value="">-- Seleccionar --</option>
                                <?php
                                // Reutilizamos transactionManager? No está instanciado aquí explícitamente pero está en autoload.
                                // Si no, hacemos query manual o instanciamos.
                                // Autoload ya instanció $transactionManager
                                $methods = $transactionManager->getPaymentMethods();
                                foreach ($methods as $m): ?>
                                    <option value="<?= $m['id'] ?>"><?= $m['name'] ?> (<?= $m['currency'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="button" class="btn btn-secondary btn-sm" onclick="resetDebtModal()">Atrás</button>
                        <button type="submit" class="btn btn-success w-100 mt-2">✅ Procesar Abono</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // --- LÓGICA DE CONTORNOS POS ---
    let sidesModal;
    let maxAllowedSides = 0;

    document.addEventListener('DOMContentLoaded', function () {
        debtModal = new bootstrap.Modal(document.getElementById('modalDebtPOS'));
        sidesModal = new bootstrap.Modal(document.getElementById('modalSides'));
    });

    function openSidesModal(productId, maxSides) {
        maxAllowedSides = maxSides;
        document.getElementById('side-product-id').value = productId;
        document.getElementById('side-max-count').textContent = maxSides;
        const container = document.getElementById('sides-container');
        container.innerHTML = '<div class="text-center py-4"><i class="fa fa-spinner fa-spin fa-2x text-muted"></i></div>';

        sidesModal.show();

        fetch(`ajax/get_product_options.php?product_id=${productId}`)
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    sidesModal.hide();
                    return;
                }

                let html = '';
                data.sides.forEach((s, index) => {
                    const priceLabel = parseFloat(s.price_override) > 0 ? ` (+ $${parseFloat(s.price_override).toFixed(2)})` : '';
                    html += `
                        <label class="list-group-item d-flex justify-content-between align-items-center" style="cursor:pointer">
                            <div>
                                <input class="form-check-input side-checkbox me-2" type="checkbox" 
                                    name="sides[${index}][id]" value="${s.component_id}" 
                                    data-type="${s.component_type}" 
                                    data-price="${s.price_override}"
                                    data-qty="${s.quantity}"
                                    onchange="validateMaxSides()">
                                <span>${s.item_name} ${priceLabel}</span>
                                <input type="hidden" name="sides[${index}][type]" value="${s.component_type}">
                                <input type="hidden" name="sides[${index}][qty]" value="${s.quantity}">
                                <input type="hidden" name="sides[${index}][price]" value="${s.price_override}">
                            </div>
                        </label>
                    `;
                });

                if (data.sides.length === 0) {
                    html = '<div class="alert alert-warning">No hay opciones configuradas.</div>';
                }

                container.innerHTML = html;
                validateMaxSides();
            })
            .catch(err => {
                console.error('Error cargando opciones:', err);
                container.innerHTML = '<div class="alert alert-danger">Error de conexión al cargar las opciones.</div>';
            });
    }

    function validateMaxSides() {
        const checked = document.querySelectorAll('.side-checkbox:checked');
        const checkboxes = document.querySelectorAll('.side-checkbox');

        if (checked.length >= maxAllowedSides) {
            checkboxes.forEach(cb => {
                if (!cb.checked) cb.disabled = true;
            });
        } else {
            checkboxes.forEach(cb => cb.disabled = false);
        }

        // El botón solo se activa si ha seleccionado el máximo o al menos uno? 
        // Depende de si es obligatorio. Asumiremos opcional hasta el máximo.
        // document.getElementById('btnConfirmSides').disabled = (checked.length === 0);
    }

    // --- LÓGICA DE ABONO POS ---
    let debtModal;

    document.addEventListener('DOMContentLoaded', function () {
        debtModal = new bootstrap.Modal(document.getElementById('modalDebtPOS'));
    });

    function openDebtModal() {
        resetDebtModal();
        debtModal.show();
    }

    function resetDebtModal() {
        document.getElementById('step-search').style.display = 'block';
        document.getElementById('step-pay').style.display = 'none';
        document.getElementById('debt-client-results').innerHTML = '';
        document.getElementById('debt-client-search').value = '';
        document.getElementById('form-debt-pos').reset();
    }

    function searchDebtClient() {
        const query = document.getElementById('debt-client-search').value;
        if (query.length < 2) return;

        fetch(`ajax/search_clients.php?q=${encodeURIComponent(query)}`)
            .then(r => r.json())
            .then(data => {
                const list = document.getElementById('debt-client-results');
                list.innerHTML = '';

                if (data.length === 0) {
                    list.innerHTML = '<div class="list-group-item text-muted">No encontrado</div>';
                    return;
                }

                data.forEach(c => {
                    const item = document.createElement('button');
                    item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                    item.innerHTML = `
                        <div>
                            <strong>${c.name}</strong><br>
                            <small class="text-muted">${c.document_id}</small>
                        </div>
                        <span class="badge bg-danger">$${parseFloat(c.current_debt).toFixed(2)}</span>
                    `;
                    item.onclick = () => selectDebtClient(c);
                    list.appendChild(item);
                });
            });
    }

    // Búsqueda al presionar Enter
    document.getElementById('debt-client-search').addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault(); // Evitar submit de forms si los hubiera
            searchDebtClient();
        }
    });

    function selectDebtClient(client) {
        document.getElementById('p_client_id').value = client.id;
        document.getElementById('selected-client-name').textContent = client.name;
        document.getElementById('selected-client-debt').textContent = '$' + parseFloat(client.current_debt).toFixed(2);

        document.getElementById('step-search').style.display = 'none';
        document.getElementById('step-pay').style.display = 'block';
        document.getElementById('p_amount_usd').focus();
    }

    document.getElementById('form-debt-pos').addEventListener('submit', function (e) {
        e.preventDefault();

        const data = {
            client_id: document.getElementById('p_client_id').value,
            amount_usd: document.getElementById('p_amount_usd').value,
            payment_method_id: document.getElementById('p_payment_method').value
        };

        if (confirm(`¿Confirmar abono de $${data.amount_usd}?`)) {
            fetch('ajax/process_debt_payment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        alert(res.message);
                        debtModal.hide();
                        // Opcional: Recargar o actualizar UI
                    } else {
                        alert('Error: ' + res.message);
                    }
                })
                .catch(err => alert('Error de conexión'));
        }
    });
    // --- LÓGICA DE GESTIÓN DE CLIENTES POS ---
    $(document).ready(function () {
        // 1. Inicializar Select2 para Clientes
        $('#posClientSelect').select2({
            placeholder: 'Buscar Cliente (Nombre/Cédula)...',
            allowClear: true,
            ajax: {
                url: 'ajax/search_clients.php',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    return {
                        results: $.map(data, function (item) {
                            return {
                                text: item.name + ' (' + item.document_id + ')',
                                id: item.id
                            }
                        })
                    };
                },
                cache: true
            }
        });

        // 2. Cargar cliente pre-seleccionado (si hay en sesión)
        <?php if (isset($_SESSION['pos_client_id'])): ?>
            let option = new Option('<?= $_SESSION['pos_client_name'] ?>', '<?= $_SESSION['pos_client_id'] ?>', true, true);
            $('#posClientSelect').append(option).trigger('change');
            updateClientUI('<?= $_SESSION['pos_client_name'] ?>');
        <?php endif; ?>

        // 3. Evento Selección
        $('#posClientSelect').on('select2:select', function (e) {
            var data = e.params.data;
            setPosClient(data.id);
            updateClientUI(data.text);
        });

        $('#posClientSelect').on('select2:unselect', function (e) {
            setPosClient(null); // Quitar cliente
            $('#posClientInfo').hide();
        });
    });

    function setPosClient(id) {
        fetch('ajax/set_pos_client.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ client_id: id })
        });
    }

    function updateClientUI(name) {
        if (name) {
            $('#posClientNameDisplay').text(name);
            $('#posClientInfo').fadeIn();
        } else {
            $('#posClientInfo').hide();
        }
    }

    function clearPosClient() {
        $('#posClientSelect').val(null).trigger('change');
        setPosClient(null);
        $('#posClientInfo').hide();
    }

    // --- NUEVO CLIENTE EXPRESS ---
    // Exponer globalmente para evitar problemas de scope
    window.openNewClientModal = function () {
        console.log('Intentando abrir modal de cliente...');
        try {
            // Intento 1: jQuery (Estándar BS4/Mixed)
            $('#formNewClientPOS')[0].reset();
            $('#modalNewClientPOS').modal('show');
            console.log('Modal abierto vía jQuery');
        } catch (e) {
            console.error('Fallo jQuery modal:', e);
            try {
                // Intento 2: Bootstrap 5 Nativo (Fallback)
                var myModal = new bootstrap.Modal(document.getElementById('modalNewClientPOS'));
                myModal.show();
                console.log('Modal abierto vía Bootstrap 5 nativo');
            } catch (e2) {
                console.error('Fallo total modal:', e2);
                alert('Error técnico: No se pudo abrir el modal. Revise la consola (F12).');
            }
        }
    };

    function saveNewClientPC() {
        const form = document.getElementById('formNewClientPOS');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const data = {
            name: document.getElementById('nc_name').value,
            document_id: document.getElementById('nc_doc').value,
            phone: document.getElementById('nc_phone').value,
            address: document.getElementById('nc_address').value
        };

        fetch('ajax/create_client_pos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    Swal.fire('Registrado', 'Cliente creado correctamente', 'success');
                    newClientModal.hide();

                    // Auto-seleccionar en Select2
                    let option = new Option(res.client.text, res.client.id, true, true);
                    $('#posClientSelect').append(option).trigger('change');
                    // Al triggerear change, Select2 lanzará evento 'select' o podemos llamar setPosClient manual si no queremos depender del evento
                    setPosClient(res.client.id);
                    updateClientUI(res.client.text);
                } else {
                    Swal.fire('Error', res.error, 'error');
                }
            })
            .catch(err => Swal.fire('Error', 'Fallo de conexión', 'error'));
    }
    // --- LÓGICA AJAX PARA AGREGAR AL CARRITO (SIN RECARGA) ---
    document.addEventListener('DOMContentLoaded', function () {
        const forms = document.querySelectorAll('.add-to-cart-form');

        forms.forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault(); // Detener recarga

                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalContent = submitBtn.innerHTML;

                // Feedback visual en botón
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> ...';

                fetch('ajax/add_to_cart.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Toast de Éxito
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: '✅ Agregado',
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true
                            });
                        } else {
                            // Alerta de Error
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: data.message
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de Red',
                            text: 'No se pudo conectar con el servidor.'
                        });
                    })
                    .finally(() => {
                        // Restaurar botón
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalContent;
                    });
            });
        });
    });
    // --- VALIDACIÓN DE ACCESO AL CARRITO ---
    function goToCart() {
        const clientId = $('#posClientSelect').val();
        if (!clientId) {
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: 'Debes seleccionar un CLIENTE antes de ir al carrito.'
            });
            return;
        }
        window.location.href = 'carrito.php';
    }
</script>

<!-- MODAL NUEVO CLIENTE EXPRESS -->
<div class="modal fade" id="modalNewClientPOS" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fa fa-user-plus"></i> Registro Rápido Cliente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNewClientPOS">
                    <div class="mb-3">
                        <label class="form-label">Nombre Completo *</label>
                        <input type="text" class="form-control" id="nc_name" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Cédula/RIF *</label>
                            <input type="text" class="form-control" id="nc_doc" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="nc_phone">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dirección (Opcional)</label>
                        <input type="text" class="form-control" id="nc_address">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveNewClientPC()">Guardar Cliente</button>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>