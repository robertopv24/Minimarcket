<?php
// edit_supplier.php
require_once '../templates/autoload.php';

session_start();
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$supplierId = $_GET['id'] ?? 0;
$supplier = $supplierManager->getSupplierById($supplierId);

if (!$supplier) {
    echo "Proveedor no encontrado.";
    exit;
}

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5">
    <h2>Editar Proveedor</h2>
    <form method="post" action="process_supplier.php">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" value="<?= $supplier['id'] ?>">
        <div class="mb-3">
            <label for="name" class="form-label">Nombre:</label>
            <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($supplier['name']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="contact_person" class="form-label">Persona de Contacto:</label>
            <input type="text" name="contact_person" id="contact_person" class="form-control" value="<?= htmlspecialchars($supplier['contact_person']) ?>">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Correo Electrónico:</label>
            <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($supplier['email']) ?>">
        </div>
        <div class="mb-3">
            <label for="phone" class="form-label">Teléfono:</label>
            <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($supplier['phone']) ?>">
        </div>
        <div class="mb-3">
            <label for="address" class="form-label">Dirección:</label>
            <textarea name="address" id="address" class="form-control"><?= htmlspecialchars($supplier['address']) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Actualizar Proveedor</button>
    </form>
</div>

<?php require_once '../templates/footer.php'; ?>
