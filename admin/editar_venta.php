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


// Obtener el ID de la venta de la URL
$ventaId = $_GET['id'] ?? 0;

// Crear una instancia de OrderManager
$orderManager = new OrderManager($db);

// Obtener la información de la venta
$venta = $orderManager->getOrderById($ventaId);

if (!$venta) {
    echo '<div class="alert alert-danger">Venta no encontrada.</div>';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevoEstado = $_POST['estado'];
    $orderManager->updateOrderStatus($ventaId, $nuevoEstado);
    header("Location: ventas.php"); // Redirigir a la lista de ventas
    exit();
}


require_once '../templates/header.php';
require_once '../templates/menu.php';



?>

<div class="container mt-5">
    <h2>Editar Estado de la Venta #<?= htmlspecialchars($venta['id']) ?></h2>

    <form method="post" action="">
        <div class="mb-3">
            <label for="estado" class="form-label">Estado Actual: <?= htmlspecialchars($venta['status']) ?></label>
            <select class="form-select" id="estado" name="estado">
                <option value="pending" <?= ($venta['status'] === 'pending') ? 'selected' : '' ?>>Pendiente</option>
                <option value="paid" <?= ($venta['status'] === 'paid') ? 'selected' : '' ?>>Pagada</option>
                <option value="shipped" <?= ($venta['status'] === 'shipped') ? 'selected' : '' ?>>Enviada</option>
                <option value="delivered" <?= ($venta['status'] === 'delivered') ? 'selected' : '' ?>>Entregada</option>
                <option value="cancelled" <?= ($venta['status'] === 'cancelled') ? 'selected' : '' ?>>Cancelada</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
    </form>
</div>

<?php require_once '../templates/footer.php'; ?>
