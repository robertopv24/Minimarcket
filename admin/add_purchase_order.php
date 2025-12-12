<?php
// admin/add_purchase_order.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../templates/autoload.php';

use Minimarcket\Core\Container;
use Minimarcket\Modules\User\Services\UserService;
use Minimarcket\Modules\SupplyChain\Services\SupplierService;
use Minimarcket\Modules\Inventory\Services\ProductService;
use Minimarcket\Modules\Finance\Services\TransactionService;
use Minimarcket\Modules\SupplyChain\Services\RawMaterialService;
use Minimarcket\Core\Security\CsrfToken;

$container = Container::getInstance();
$userService = $container->get(UserService::class);
$supplierService = $container->get(SupplierService::class);
$productService = $container->get(ProductService::class);
$transactionService = $container->get(TransactionService::class);
$rawMaterialService = $container->get(RawMaterialService::class);
$csrfToken = $container->get(CsrfToken::class);

session_start();
if (!isset($_SESSION['user_id']) || $userService->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

// Obtener datos necesarios
$suppliers = $supplierService->getAllSuppliers();
$products = $productService->getAllProducts();
$methods = $transactionService->getPaymentMethods();
$rawMaterials = $rawMaterialService->getAllMaterials();

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5 mb-5 align-items-center">
    <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
        <div class="card-header bg-primary text-white py-3 px-4">
            <h3 class="mb-0 fw-bold"><i class="fa fa-shopping-cart me-2"></i>Registrar Compra de Mercancía</h3>
        </div>
        <div class="card-body p-4 p-md-5 bg-white">
            <form method="post" action="process_purchase_order.php" id="purchaseForm">
                <?= $csrfToken->insertTokenField() ?>
                <input type="hidden" name="action" value="add">

                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-secondary">Proveedor</label>
                        <select name="supplier_id" class="form-select form-select-lg shadow-sm border-0 bg-light"
                            required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-secondary">Fecha de Compra</label>
                        <input type="date" name="order_date"
                            class="form-control form-control-lg shadow-sm border-0 bg-light"
                            value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-secondary">Entrega Estimada</label>
                        <input type="date" name="expected_delivery_date"
                            class="form-control form-control-lg shadow-sm border-0 bg-light"
                            value="<?= date('Y-m-d', strtotime('+3 days')) ?>" required>
                    </div>
                </div>

                <hr class="text-muted opacity-25 my-4">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-secondary"><i class="fa fa-boxes me-2"></i>Productos a Comprar</h5>
                </div>

                <div id="items-container">
                </div>

                <div class="d-flex justify-content-between mt-4 mb-5 align-items-center">
                    <button type="button" id="addItem" class="btn btn-outline-primary rounded-pill px-4 hover-float">
                        <i class="fa fa-plus-circle me-1"></i> Agregar Producto
                    </button>
                    <h4 class="text-end m-0">Total Estimado: <span id="totalDisplay"
                            class="text-success fw-bold ms-2">$0.00</span></h4>
                </div>

                <div class="card bg-warning bg-opacity-10 border-warning border-opacity-25 rounded-4 mb-4">
                    <div class="card-body p-4">
                        <h5 class="card-title text-dark fw-bold mb-3"><i
                                class="fa fa-wallet me-2 text-warning"></i>Información de Pago</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary small text-uppercase">Método de
                                    Pago</label>
                                <select name="payment_method_id" class="form-select border-warning border-opacity-25"
                                    required>
                                    <option value="">Seleccione cómo pagó...</option>
                                    <?php foreach ($methods as $method): ?>
                                        <option value="<?= $method['id'] ?>">
                                            <?= $method['name'] ?> (<?= $method['currency'] ?>)
                                            <?= $method['type'] == 'cash' ? '- [Sale de Caja Chica]' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted d-block mt-1"><i class="fa fa-info-circle me-1"></i>Si
                                    seleccionas "Efectivo", se descontará de Bóveda.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary small text-uppercase">Estado del
                                    Pago</label>
                                <select name="payment_status"
                                    class="form-select border-warning border-opacity-25 bg-light" disabled>
                                    <option value="paid" selected>Pagado de Contado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-success btn-lg py-3 rounded-pill shadow hover-float">
                        <i class="fa fa-check-circle me-2"></i> Confirmar Compra y Registrar Gasto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .hover-float:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.2s;
    }

    .item-row {
        transition: background-color 0.2s;
    }

    .item-row:hover {
        background-color: #f8f9fa;
        border-radius: 8px;
    }
</style>

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
                <div class="row g-2 mb-3 align-items-end item-row p-2" id="row-${itemCount}">
                    <div class="col-md-2">
                        <label class="small fw-bold text-muted mb-1">Tipo de Ítem</label>
                        <select name="items[${itemCount}][item_type]" class="form-select form-select-sm item-type shadow-sm border-0 bg-light" required onchange="updateItemOptions(${itemCount})">
                            <option value="">Seleccionar...</option>
                            <option value="product">Producto Reventa</option>
                            <option value="raw_material">Materia Prima/Insumo</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted mb-1">Ítem</label>
                        <select name="items[${itemCount}][item_id]" class="form-select form-select-sm item-select shadow-sm border-0 bg-light" required disabled>
                            <option value="">Primero seleccione tipo...</option>
                        </select>
                        <small class="text-muted item-info fst-italic" style="font-size: 0.75rem;"></small>
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold text-muted mb-1">Cantidad</label>
                        <input type="number" name="items[${itemCount}][quantity]" class="form-control form-control-sm quantity shadow-sm border-0 bg-light" min="0.01" step="0.01" required oninput="calcTotal()">
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold text-muted mb-1">Costo Unit. ($)</label>
                        <input type="number" name="items[${itemCount}][unit_price]" class="form-control form-control-sm price shadow-sm border-0 bg-light" step="0.000001" required oninput="calcTotal()">
                    </div>
                    <div class="col-md-1">
                        <label class="small fw-bold text-muted mb-1">Subtotal</label>
                        <div class="fw-bold text-success subtotal">$0.00</div>
                    </div>
                    <div class="col-md-1 text-end">
                        <button type="button" class="btn btn-outline-danger btn-sm border-0 rounded-circle w-100" onclick="removeRow(${itemCount})"><i class="fa fa-trash"></i></button>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        // Función para eliminar línea
        window.removeRow = function (id) {
            const row = document.getElementById(`row-${id}`);
            if (row) row.remove();
            calcTotal();
        };

        // Función para actualizar opciones según tipo seleccionado
        window.updateItemOptions = function (rowId) {
            const row = document.getElementById(`row-${rowId}`);
            if (!row) return;

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

                option.dataset.stock = item.stock || item.stock_quantity || 0;
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

                    let info = `Stock: ${stock} ${unit}`;
                    if (category) {
                        const catNames = {
                            'ingredient': 'Ingrediente',
                            'packaging': 'Empaque',
                            'supply': 'Insumo'
                        };
                        info += ` | ${catNames[category] || category}`;
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