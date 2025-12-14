<?php
// delete_supplier.php
require_once '../templates/autoload.php';

// session_start();
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
    <h2>Eliminar Proveedor</h2>
    <p>¿Estás seguro de que quieres eliminar el proveedor "<?= htmlspecialchars($supplier['name']) ?>"?</p>
    <form method="post" action="process_supplier.php">
        <?php $csrf = $container->get(\Minimarcket\Core\Security\CsrfToken::class); ?>
        <input type="hidden" name="csrf_token" value="<?= $csrf->getToken() ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= $supplier['id'] ?>">
        <button type="submit" class="btn btn-danger">Eliminar Proveedor</button>
        <a href="proveedores.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php require_once '../templates/footer.php'; ?>