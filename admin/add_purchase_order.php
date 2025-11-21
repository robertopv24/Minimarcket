<?php
// add_purchase_order.php
require_once '../templates/autoload.php';

session_start();
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$suppliers = $supplierManager->getAllSuppliers();
$products = $productManager->getAllProducts();

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5">
    <h2>Crear Nueva Orden de Compra</h2>
    <form method="post" action="process_purchase_order.php">
        <input type="hidden" name="action" value="add">
        <div class="mb-3">
            <label for="supplier_id" class="form-label">Proveedor:</label>
            <select name="supplier_id" id="supplier_id" class="form-select" required>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?= $supplier['id'] ?>"><?= $supplier['name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="order_date" class="form-label">Fecha de Orden:</label>
            <input type="date" name="order_date" id="order_date" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="expected_delivery_date" class="form-label">Fecha de Entrega Esperada:</label>
            <input type="date" name="expected_delivery_date" id="expected_delivery_date" class="form-control" required>
        </div>
        <div id="items">
            </div>
        <button type="button" id="addItem" class="btn btn-secondary mb-3">Agregar Ítem</button>
        <button type="submit" class="btn btn-success">Crear Orden de Compra</button>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const addItemButton = document.getElementById('addItem');
        const itemsContainer = document.getElementById('items');
        let itemCount = 0;

        addItemButton.addEventListener('click', function() {
            itemCount++;

            const itemDiv = document.createElement('div');
            itemDiv.innerHTML = `
                <div class="border p-3 mb-3">
                    <h4>Ítem ${itemCount}</h4>
                    <div class="mb-3">
                        <label for="product_id_${itemCount}" class="form-label">Producto:</label>
                        <select name="items[${itemCount}][product_id]" id="product_id_${itemCount}" class="form-select" required>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quantity_${itemCount}" class="form-label">Cantidad:</label>
                        <input type="number" name="items[${itemCount}][quantity]" id="quantity_${itemCount}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="unit_price_${itemCount}" class="form-label">Precio Unitario:</label>
                        <input type="number" name="items[${itemCount}][unit_price]" id="unit_price_${itemCount}" class="form-control" step="0.01" required>
                    </div>
                    <button type="button" class="btn btn-danger removeItem">Eliminar Ítem</button>
                </div>
            `;

            itemsContainer.appendChild(itemDiv);

            const removeItemButtons = document.querySelectorAll('.removeItem');
            removeItemButtons.forEach(button => {
                button.addEventListener('click', function() {
                    this.parentElement.remove();
                });
            });
        });
    });
</script>

<?php require_once '../templates/footer.php'; ?>
