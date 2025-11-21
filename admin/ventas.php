<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_name']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../paginas/login.php");
    exit();
}

require_once '../templates/autoload.php';
require_once '../templates/header.php';
require_once '../templates/menu.php';

// Obtener parámetros de búsqueda y filtrado
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';

// Crear una instancia de OrderManager
$orderManager = new OrderManager($db);

// Obtener las órdenes
$ventas = $orderManager->getOrdersBySearchAndFilter($search, $filter);

?>

<div class="container mt-5">
    <h2>Gestión de Órdenes</h2>

    <form method="GET" action="">
        <div class="row mb-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Buscar por ID o cliente" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-4">
                <select name="filter" class="form-select">
                    <option value="">Todas las órdenes</option>
                    <option value="pending" <?= ($filter === 'pending') ? 'selected' : '' ?>>Pendientes</option>
                    <option value="paid" <?= ($filter === 'paid') ? 'selected' : '' ?>>Pagadas</option>
                    <option value="shipped" <?= ($filter === 'shipped') ? 'selected' : '' ?>>Enviadas</option>
                    <option value="delivered" <?= ($filter === 'delivered') ? 'selected' : '' ?>>Entregadas</option>
                    <option value="cancelled" <?= ($filter === 'cancelled') ? 'selected' : '' ?>>Canceladas</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Buscar</button>
            </div>
        </div>
    </form>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Total (USD)</th>
                <th>Estado</th>
                <th>Dirección de Envío</th>
                <th>Método de Envío</th>
                <th>Número de Seguimiento</th>
                <th>Fecha de Creación</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($ventas)): ?>
                <?php foreach ($ventas as $venta): ?>
                    <tr>
                        <td><?= htmlspecialchars($venta['id']) ?></td>
                        <td><?= htmlspecialchars($venta['customer_name']) ?></td>
                        <td>$<?= number_format($venta['total_price'], 2) ?></td>
                        <td><?= htmlspecialchars($venta['status']) ?></td>
                        <td><?= htmlspecialchars($venta['shipping_address']) ?></td>
                        <td><?= htmlspecialchars($venta['shipping_method']) ?></td>
                        <td><?= htmlspecialchars($venta['tracking_number']) ?></td>
                        <td><?= htmlspecialchars($venta['created_at']) ?></td>
                        <td>
                            <a href="ver_venta.php?id=<?= $venta['id'] ?>" class="btn btn-sm btn-primary">Ver</a>
                            <a href="editar_venta.php?id=<?= $venta['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center">No hay órdenes registradas.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../templates/footer.php'; ?>
