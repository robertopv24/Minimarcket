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
$methods = $transactionManager->getPaymentMethods(); // <--- NUEVO: Métodos de pago

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">🛒 Registrar Compra de Mercancía</h3>
        </div>
        <div class="card-body">
            <form method="post" action="process_purchase_order.php" id="purchaseForm">
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
                        <input type="date" name="expected_delivery_date" class="form-control" value="<?= date('Y-m-d', strtotime('+3 days')) ?>" required>
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
                        <h5 class="card-title text-warning text-dark"><i class="fa fa-wallet"></i> Información de Pago</h5>
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
                                <small class="text-muted">Si seleccionas "Efectivo", se descontará automáticamente de la Bóveda Central.</small>
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
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('items-container');
        const btnAdd = document.getElementById('addItem');
        const totalDisplay = document.getElementById('totalDisplay');
        let itemCount = 0;

        // Función para agregar línea
        function addItem() {
            itemCount++;
            const html = `
                <div class="row g-2 mb-2 align-items-end item-row" id="row-${itemCount}">
                    <div class="col-md-5">
                        <label class="small">Producto</label>
                        <select name="items[${itemCount}][product_id]" class="form-select form-select-sm" required>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small">Cantidad</label>
                        <input type="number" name="items[${itemCount}][quantity]" class="form-control form-control-sm quantity" min="1" required oninput="calcTotal()">
                    </div>
                    <div class="col-md-3">
                        <label class="small">Costo Unitario ($)</label>
                        <input type="number" name="items[${itemCount}][unit_price]" class="form-control form-control-sm price" step="0.01" required oninput="calcTotal()">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeRow(${itemCount})"><i class="fa fa-trash"></i></button>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        // Función para eliminar línea
        window.removeRow = function(id) {
            document.getElementById(`row-${id}`).remove();
            calcTotal();
        };

        // Función para calcular total visual
        window.calcTotal = function() {
            let total = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const q = parseFloat(row.querySelector('.quantity').value) || 0;
                const p = parseFloat(row.querySelector('.price').value) || 0;
                total += q * p;
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
