<?php
// admin/compras.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../templates/autoload.php';

use Minimarcket\Core\Container;
use Minimarcket\Modules\User\Services\UserService;
use Minimarcket\Modules\SupplyChain\Services\PurchaseOrderService;
use Minimarcket\Modules\SupplyChain\Services\SupplierService;
use Minimarcket\Modules\Finance\Services\TransactionService;

$container = Container::getInstance();
$userService = $container->get(UserService::class);
$purchaseOrderService = $container->get(PurchaseOrderService::class);
$supplierService = $container->get(SupplierService::class);
$userService = $container->get(UserService::class);
$purchaseOrderService = $container->get(PurchaseOrderService::class);
$supplierService = $container->get(SupplierService::class);
$transactionService = $container->get(TransactionService::class);

// session_start(); // Handled by SessionManager in autoload.php
if (!isset($_SESSION['user_id']) || $userService->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

// Obtener todas las órdenes
$search = $_GET['search'] ?? '';
$purchaseOrders = $purchaseOrderService->searchPurchaseOrders($search);

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0"><i class="fa fa-boxes me-2"></i>Gestión de Compras</h2>
            <div class="text-muted small">Administra tus órdenes de compra a proveedores</div>
        </div>
        <a href="add_purchase_order.php" class="btn btn-success rounded-pill px-4 shadow-sm hover-lift">
            <i class="fa fa-plus-circle me-2"></i> Nueva Compra
        </a>
    </div>

    <!-- Barra de Búsqueda -->
    <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden">
        <div class="card-body p-2 bg-light">
            <form method="GET" action="" class="row g-2 align-items-center m-1">
                <div class="col-md-10">
                    <div class="input-group bg-white rounded-pill border">
                        <span class="input-group-text bg-white border-0 ps-3 text-muted"><i
                                class="fa fa-search"></i></span>
                        <input type="text" name="search" class="form-control border-0"
                            placeholder="Buscar por proveedor o ID de orden..."
                            value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill">Buscar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <?php if ($purchaseOrders): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light text-secondary small text-uppercase">
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Fecha Compra</th>
                                <th>Proveedor</th>
                                <th>Entrega Estimada</th>
                                <th>Total (USD)</th>
                                <th>Pago</th>
                                <th>Estado</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <?php foreach ($purchaseOrders as $order): ?>
                                <?php
                                $supplier = $supplierService->getSupplierById($order['supplier_id']);

                                // Info Financiera via Service
                                $pago = $transactionService->getTransactionByReference('purchase', $order['id']);

                                // Estilos
                                $badgeClass = match ($order['status']) {
                                    'received' => 'bg-success bg-opacity-10 text-success',
                                    'pending' => 'bg-warning bg-opacity-10 text-warning',
                                    'canceled' => 'bg-danger bg-opacity-10 text-danger',
                                    default => 'bg-secondary bg-opacity-10 text-secondary'
                                };
                                ?>
                                <tr class="hover-shadow-row transition-all">
                                    <td class="ps-4 fw-bold">#<?= $order['id'] ?></td>
                                    <td class="text-muted"><?= date('d/m/Y', strtotime($order['order_date'])) ?></td>
                                    <td>
                                        <div class="fw-bold text-dark">
                                            <?= htmlspecialchars($supplier['name'] ?? 'Desconocido') ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="small text-muted"><i
                                                class="fa fa-calendar-alt me-1"></i><?= date('d/m/Y', strtotime($order['expected_delivery_date'])) ?>
                                        </div>
                                    </td>

                                    <td class="fw-bold text-primary">
                                        $<?= number_format($order['total_amount'], 2) ?>
                                    </td>

                                    <td>
                                        <?php if ($pago): ?>
                                            <div class="small fw-bold text-dark"><?= $pago['method_name'] ?></div>
                                            <div class="small text-muted" style="font-size:0.7em;">Tx #<?= $pago['id'] ?></div>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">Pendiente</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="badge <?= $badgeClass ?> rounded-pill px-3 py-2">
                                            <?= strtoupper($order['status']) ?>
                                        </span>
                                    </td>

                                    <td class="text-end pe-4">
                                        <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                            <?php if ($order['status'] !== 'received'): ?>
                                                <a href="add_purchase_receipt.php?order_id=<?= $order['id'] ?>"
                                                    class="btn btn-sm btn-outline-success border-0" title="Registrar Recepción">
                                                    <i class="fa fa-box-open"></i>
                                                </a>
                                            <?php endif; ?>

                                            <a href="edit_purchase_order.php?id=<?= $order['id'] ?>"
                                                class="btn btn-sm btn-outline-primary border-0" title="Ver Detalles">
                                                <i class="fa fa-eye"></i>
                                            </a>

                                            <?php if ($order['status'] !== 'received'): ?>
                                                <a href="delete_purchase_order.php?id=<?= $order['id'] ?>"
                                                    class="btn btn-sm btn-outline-danger border-0" title="Eliminar"
                                                    onclick="return confirm('¿Eliminar esta orden?');">
                                                    <i class="fa fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-5 text-center">
                    <div class="mb-3">
                        <i class="fa fa-box-open fa-4x text-muted opacity-25"></i>
                    </div>
                    <h5 class="text-muted fw-bold">No hay órdenes de compra registradas</h5>
                    <p class="text-secondary small">Comienza creando una nueva orden para tus proveedores.</p>
                    <a href="add_purchase_order.php" class="btn btn-primary rounded-pill px-4 mt-2 shadow-sm">
                        Crear Primera Orden
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .hover-lift:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
        transition: all 0.2s;
    }

    .transition-all {
        transition: all 0.2s ease;
    }

    .hover-shadow-row:hover {
        background-color: #f8f9fa;
    }
</style>

<?php require_once '../templates/footer.php'; ?>