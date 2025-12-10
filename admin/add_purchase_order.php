<?php
// admin/add_purchase_order.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../templates/autoload.php';

session_start();
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Obtener datos necesarios
$suppliers = $supplierManager->getAllSuppliers();
$products = $productManager->getAllProducts();
$methods = $transactionManager->getPaymentMethods();

// Obtener materias primas (incluye ingredientes, empaques e insumos)
$sqlRaw = "SELECT id, name, unit, stock_quantity as stock, cost_per_unit, category, is_cooking_supply FROM raw_materials ORDER BY category, name";
$stmtRaw = $db->query($sqlRaw);
$rawMaterials = $stmtRaw->fetchAll(PDO::FETCH_ASSOC);

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<style>
    /* Mejorar legibilidad de textos para tema oscuro */
    label,
    .form-label {
        font-weight: 600 !important;
        color: var(--text-main, #f8fafc) !important;
        font-size: 15px !important;
        margin-bottom: 0.5rem;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    }

    .small,
    small {
        font-size: 13px !important;
        font-weight: 500;
        color: var(--text-muted, #94a3b8) !important;
    }

    .form-select,
    .form-control {
        font-size: 14px;
        font-weight: 500;
    }

    .btn {
        font-weight: 600;
        font-size: 14px;
    }

    .card-header {
        font-weight: 700;
    }

    .item-info {
        display: block;
        margin-top: 0.25rem;
        font-weight: 500 !important;
        color: var(--text-muted, #94a3b8) !important;
    }

    #totalDisplay {
        font-size: 1.5rem;
        font-weight: 700;
    }
</style>

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">🛒 Registrar Compra de Mercancía</h3>
        </div>
        <div class="card-body">
            <form method="post" action="process_purchase_order.php" id="purchaseForm">
                <input type="hidden" name="csrf_token" value="<?= Csrf::getToken() ?>">
                <input type="hidden" name="action" value="add">

                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Proveedor</label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Fecha de Compra</label>
                        <input type="date" name="order_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Entrega Estimada</label>
                        <input type="date" name="expected_delivery_date" class="form-control"
                            value="<?= date('Y-m-d', strtotime('+3 days')) ?>" required>
                    </div>
                </div>

                <hr>

                <h5 class="mb-3">📦 Productos a Comprar</h5>
                <div id="items-container">
                </div>

                <div class="d-flex justify-content-between mt-3 mb-4">
                    <button type="button" id="addItem" class="btn btn-outline-primary">
                        <i class="fa fa-plus-circle"></i> Agregar Producto
                    </button>
                    <h4 class="text-end">Total Estimado: <span id="totalDisplay" class="text-success">$0.00</span></h4>
                </div>

                <hr>

                <div class="card bg-light border-warning mb-4">
                    <div class="card-body">
                        <h5 class="card-title text-warning text-dark"><i class="fa fa-wallet"></i> Información de Pago
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Método de Pago (Origen de Fondos)</label>
                                <select name="payment_method_id" class="form-select" required>
                                    <option value="">Seleccione cómo pagó...</option>
                                    <?php foreach ($methods as $method): ?>
                                        <option value="<?= $method['id'] ?>">
                                            <?= $method['name'] ?> (<?= $method['currency'] ?>)
                                            <?= $method['type'] == 'cash' ? '- [Sale de Caja Chica]' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Si seleccionas "Efectivo", se descontará automáticamente de la
                                    Bóveda Central.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estado del Pago</label>
                                <select name="payment_status" class="form-select" disabled>
                                    <option value="paid" selected>Pagado de Contado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-success btn-lg">Confirmar Compra y Registrar Gasto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('items-container');
        const btnAdd = document.getElementById('addItem');
        const totalDisplay = document.getElementById('totalDisplay');
        let itemCount = 0;

        // Función para agregar línea
        function addItem() {
            itemCount++;
            const html = `
                <div class="row g-2 mb-3 align-items-end item-row border-bottom pb-2" id="row-${itemCount}">
                    <div class="col-md-2">
                        <label class="small fw-bold">Tipo de Ítem</label>
                        <select name="items[${itemCount}][item_type]" class="form-select form-select-sm item-type" required onchange="updateItemOptions(${itemCount})">
                            <option value="">Seleccionar...</option>
                            <option value="product">Producto Reventa</option>
                            <option value="raw_material">Materia Prima/Insumo</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold">Ítem</label>
                        <select name="items[${itemCount}][item_id]" class="form-select form-select-sm item-select" required disabled>
                            <option value="">Primero seleccione tipo...</option>
                        </select>
                        <small class="text-muted item-info"></small>
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold">Cantidad</label>
                        <input type="number" name="items[${itemCount}][quantity]" class="form-control form-control-sm quantity" min="0.01" step="0.01" required oninput="calcTotal()">
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold">Costo Unit. ($)</label>
                        <input type="number" name="items[${itemCount}][unit_price]" class="form-control form-control-sm price" step="0.000001" required oninput="calcTotal()">
                    </div>
                    <div class="col-md-1">
                        <label class="small fw-bold">Subtotal</label>
                        <div class="fw-bold text-success subtotal">$0.00</div>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeRow(${itemCount})"><i class="fa fa-trash"></i></button>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        // Función para eliminar línea
        window.removeRow = function (id) {
            document.getElementById(`row-${id}`).remove();
            calcTotal();
        };

        // Función para actualizar opciones según tipo seleccionado
        window.updateItemOptions = function (rowId) {
            const row = document.getElementById(`row-${rowId}`);
            const typeSelect = row.querySelector('.item-type');
            const itemSelect = row.querySelector('.item-select');
            const itemInfo = row.querySelector('.item-info');
            const priceInput = row.querySelector('.price');

            const type = typeSelect.value;
            itemSelect.innerHTML = '<option value="">Seleccionar...</option>';
            itemInfo.textContent = '';
            priceInput.value = '';

            if (!type) {
                itemSelect.disabled = true;
                return;
            }

            itemSelect.disabled = false;

            // Datos de PHP convertidos a JavaScript
            const products = <?= json_encode($products) ?>;
            const rawMaterials = <?= json_encode($rawMaterials) ?>;

            let items = [];
            if (type === 'product') {
                items = products;
            } else if (type === 'raw_material') {
                items = rawMaterials;
            }

            items.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;

                // Mostrar categoría para materias primas
                if (type === 'raw_material' && item.category) {
                    const catLabel = {
                        'ingredient': '🥘',
                        'packaging': '📦',
                        'supply': '🧹'
                    }[item.category] || '';
                    option.textContent = `${catLabel} ${item.name}`;
                } else {
                    option.textContent = item.name;
                }

                option.dataset.stock = item.stock || 0;
                option.dataset.unit = item.unit || 'unidad';
                option.dataset.cost = item.cost_per_unit || item.price_usd || 0;
                option.dataset.category = item.category || '';
                itemSelect.appendChild(option);
            });

            // Evento para mostrar info al seleccionar ítem
            itemSelect.onchange = function () {
                const selected = itemSelect.options[itemSelect.selectedIndex];
                if (selected.value) {
                    const stock = selected.dataset.stock;
                    const unit = selected.dataset.unit;
                    const cost = selected.dataset.cost;
                    const category = selected.dataset.category;

                    let info = `Stock actual: ${stock} ${unit}`;
                    if (category) {
                        const catNames = {
                            'ingredient': 'Ingrediente',
                            'packaging': 'Empaque',
                            'supply': 'Insumo'
                        };
                        info += ` | Tipo: ${catNames[category] || category}`;
                    }
                    itemInfo.textContent = info;

                    // Sugerir costo si existe
                    if (cost > 0 && !priceInput.value) {
                        priceInput.value = cost;
                        calcTotal();
                    }
                }
            };
        };

        // Función para calcular total visual
        window.calcTotal = function () {
            let total = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const q = parseFloat(row.querySelector('.quantity').value) || 0;
                const p = parseFloat(row.querySelector('.price').value) || 0;
                const subtotal = q * p;
                row.querySelector('.subtotal').textContent = '$' + subtotal.toFixed(2);
                total += subtotal;
            });
            totalDisplay.textContent = '$' + total.toFixed(2);
        };

        // Agregar primer item al cargar
        addItem();

        // Evento click
        btnAdd.addEventListener('click', addItem);
    });
</script>

<?php require_once '../templates/footer.php'; ?>