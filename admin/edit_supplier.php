<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../templates/autoload.php';

// session_start();
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

$supplierId = $_GET['id'] ?? 0;
$supplier = $supplierManager->getSupplierById($supplierId);

if (!$supplier) {
    echo '<div class="alert alert-danger m-5">Proveedor no encontrado. <a href="proveedores.php">Volver</a></div>';
    exit;
}

// Obtener historial de compras a este proveedor
$stmt = $db->prepare("SELECT * FROM purchase_orders WHERE supplier_id = ? ORDER BY order_date DESC LIMIT 10");
$stmt->execute([$supplierId]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5 mb-5">
    <div class="row">
        <div class="col-md-7">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Editar Proveedor</h4>
                </div>
                <div class="card-body">
                    <form method="post" action="process_supplier.php">
                        <?php $csrf = $container->get(\Minimarcket\Core\Security\CsrfToken::class); ?>
                        <input type="hidden" name="csrf_token" value="<?= $csrf->getToken() ?>">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= $supplier['id'] ?>">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Nombre Empresa</label>
                            <input type="text" name="name" class="form-control"
                                value="<?= htmlspecialchars($supplier['name']) ?>" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Contacto</label>
                                <input type="text" name="contact_person" class="form-control"
                                    value="<?= htmlspecialchars($supplier['contact_person']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="phone" class="form-control"
                                    value="<?= htmlspecialchars($supplier['phone']) ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                value="<?= htmlspecialchars($supplier['email']) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Dirección</label>
                            <textarea name="address" class="form-control"
                                rows="2"><?= htmlspecialchars($supplier['address']) ?></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Actualizar Datos</button>
                            <a href="proveedores.php" class="btn btn-outline-secondary">Volver</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card shadow border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fa fa-history"></i> Historial de Compras</h5>
                </div>
                <div class="card-body p-0">
                    <?php if ($history): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($history as $order): ?>
                                        <tr>
                                            <td><a
                                                    href="edit_purchase_order.php?id=<?= $order['id'] ?>">#<?= $order['id'] ?></a><br><small><?= date('d/m/y', strtotime($order['order_date'])) ?></small>
                                            </td>
                                            <td>
                                                <?php if ($order['status'] == 'received'): ?>
                                                    <span class="badge bg-success">Recibido</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Pendiente</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end fw-bold">$<?= number_format($order['total_amount'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-4 text-center text-muted">
                            <small>No hay compras registradas a este proveedor.</small>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white text-center">
                    <a href="add_purchase_order.php" class="btn btn-sm btn-outline-primary">Nueva Compra a este
                        Proveedor</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>