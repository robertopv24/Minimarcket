<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../templates/autoload.php';

session_start();
// Seguridad: Solo Admin
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

require_once '../templates/header.php';
require_once '../templates/menu.php';

// Obtener parámetros de búsqueda y filtrado
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';

// Obtener las órdenes usando el Manager
$ventas = $orderManager->getOrdersBySearchAndFilter($search, $filter);
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📊 Gestión de Ventas</h2>
        <a href="../paginas/tienda.php" class="btn btn-success"><i class="fa fa-plus"></i> Nueva Venta (POS)</a>
    </div>

    <div class="card mb-4 bg-light">
        <div class="card-body">
            <form method="GET" action="">
                <div class="row g-2">
                    <div class="col-md-5">
                        <input type="text" name="search" class="form-control" placeholder="Buscar por ID o Cliente..."
                            value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-4">
                        <select name="filter" class="form-select">
                            <option value="">Todos los estados</option>
                            <option value="paid" <?= ($filter === 'paid') ? 'selected' : '' ?>>Pagado (Paid)</option>
                            <option value="pending" <?= ($filter === 'pending') ? 'selected' : '' ?>>Pendiente (Pending)
                            </option>
                            <option value="cancelled" <?= ($filter === 'cancelled') ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100"><i class="fa fa-search"></i> Buscar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Total (USD)</th>
                    <th>Info. Pago (Tesorería)</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($ventas)): ?>
                    <?php foreach ($ventas as $venta): ?>
                        <?php
                        // Lógica visual para el estado
                        $badgeClass = match ($venta['status']) {
                            'paid', 'delivered' => 'bg-success',
                            'pending' => 'bg-warning text-dark',
                            'cancelled' => 'bg-danger',
                            default => 'bg-secondary'
                        };

                        // --- CONSULTA RÁPIDA DE PAGOS ---
                        // Buscamos en la tabla 'transactions' cómo se pagó esta orden
                        $stmt = $db->prepare("SELECT pm.name, t.amount, t.currency
                                                  FROM transactions t
                                                  JOIN payment_methods pm ON t.payment_method_id = pm.id
                                                  WHERE t.reference_type = 'order'
                                                  AND t.reference_id = ?
                                                  AND t.type = 'income'"); // Solo ingresos, no vueltos
                        $stmt->execute([$venta['id']]);
                        $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <tr>
                            <td class="fw-bold">#<?= htmlspecialchars($venta['id']) ?></td>
                            <td>
                                <?= date('d/m/Y', strtotime($venta['created_at'])) ?><br>
                                <small class="text-muted"><?= date('h:i A', strtotime($venta['created_at'])) ?></small>
                            </td>
                            <td>
                                <?= htmlspecialchars($venta['customer_name'] ?? 'Cliente General') ?>
                                <?php if (!empty($venta['customer_note'])): ?>
                                    <br><small class="text-primary fw-bold"><i class="fa fa-sticky-note"></i>
                                        <?= htmlspecialchars($venta['customer_note']) ?></small>
                                <?php endif; ?>
                            </td>

                            <td class="fw-bold fs-5">$<?= number_format($venta['total_price'], 2) ?></td>

                            <td>
                                <?php if (empty($pagos)): ?>
                                    <span class="text-muted small">Sin registro contable</span>
                                <?php else: ?>
                                    <ul class="list-unstyled mb-0 small">
                                        <?php foreach ($pagos as $pago): ?>
                                            <li>
                                                <i class="fa fa-check-circle text-success"></i>
                                                <?= $pago['name'] ?>:
                                                <strong><?= number_format($pago['amount'], 2) ?>                 <?= $pago['currency'] ?></strong>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="badge <?= $badgeClass ?> rounded-pill px-3">
                                    <?= strtoupper($venta['status']) ?>
                                </span>
                            </td>

                            <td class="text-end">
                                <div class="btn-group">
                                    <a href="ver_venta.php?id=<?= $venta['id'] ?>" class="btn btn-sm btn-info"
                                        title="Ver Detalles">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <a href="editar_venta.php?id=<?= $venta['id'] ?>" class="btn btn-sm btn-warning"
                                        title="Editar Estado">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="fa fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No se encontraron ventas registradas.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>