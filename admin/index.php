<?php
// Production settings applied via autoload

require_once '../templates/autoload.php';
require_once '../funciones/Csrf.php';

session_start();
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

$success_message = '';
$error_message = '';

// 1. Procesar Tasa de Cambio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_exchange_rate'])) {
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
        die("CSRF Error");
    }
    $newRate = floatval($_POST['new_exchange_rate']);
    if ($newRate > 0) {
        if ($config->update('exchange_rate', $newRate)) {
            if ($productManager->updateAllPricesBasedOnRate($newRate)) {
                $success_message = "Tasa actualizada a <strong>$newRate</strong>. Precios recalculados.";
            } else {
                $error_message = "Tasa guardada, pero error al recalcular precios.";
            }
        } else {
            $error_message = "Error al guardar configuración.";
        }
    } else {
        $error_message = "La tasa debe ser mayor a 0.";
    }
}

// 2. OBTENER TODAS LAS MÉTRICAS
$currentRate = $config->get('exchange_rate');
$vaultBalance = $vaultManager->getBalance();

// Métricas de Ventas (Ingresos)
$ventasDia = $orderManager->getTotalVentasDia();
$ventasSemana = $orderManager->getTotalVentasSemana();
$ventasMes = $orderManager->getTotalVentasMes();
$ventasAnio = $orderManager->getTotalVentasAnio();

// Métricas Operativas
$ordenesPendientes = $orderManager->countOrdersByStatus('pending');
$stockCritico = count($productManager->getLowStockProducts(5));

// Listas Recientes
$ultimasVentas = $orderManager->getUltimosPedidos(5);
$stmt = $db->query("SELECT * FROM vault_movements ORDER BY created_at DESC LIMIT 5");
$ultimosMovimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5 mb-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>🚀 Dashboard General</h2>
            <p class="text-muted mb-0">Resumen del negocio al <?= date('d/m/Y h:i A') ?></p>
        </div>

        <div class="card border-primary shadow-sm" style="width: 280px;">
            <div class="card-body p-2">
                <form method="POST" class="row g-1 align-items-center">
                    <div class="col-7">
                        <label class="small fw-bold text-primary">Tasa BCV/Paralelo</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Bs</span>
                            <input type="number" step="0.01" name="new_exchange_rate" class="form-control fw-bold"
                                value="<?= $currentRate ?>">
                        </div>
                    </div>
                    <div class="col-5">
                        <button type="submit" name="update_exchange_rate" class="btn btn-primary btn-sm w-100 h-100"
                            onclick="return confirm('¿Actualizar precios?');">
                            <i class="fa fa-sync"></i> Fijar
                        </button>
                        <?= Csrf::insertTokenField(); ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= $success_message ?> <button type="button"
                class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <h5 class="text-muted mb-3"><i class="fa fa-chart-line me-2"></i>Ingresos por Ventas (POS)</h5>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-white border-start border-4 border-success shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted text-uppercase">Ventas Hoy</small>
                    <h3 class="fw-bold text-dark mb-0">$<?= number_format($ventasDia, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-white border-start border-4 border-info shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted text-uppercase">Últimos 7 Días</small>
                    <h3 class="fw-bold text-dark mb-0">$<?= number_format($ventasSemana, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-white border-start border-4 border-primary shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted text-uppercase">Este Mes</small>
                    <h3 class="fw-bold text-dark mb-0">$<?= number_format($ventasMes, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-white border-start border-4 border-dark shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted text-uppercase">Acumulado Año</small>
                    <h3 class="fw-bold text-dark mb-0">$<?= number_format($ventasAnio, 2) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <h5 class="text-muted mb-3"><i class="fa fa-cogs me-2"></i>Estado Operativo</h5>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card bg-secondary text-white shadow h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase opacity-75">Bóveda Central (Caja Chica)</h6>
                        <h2 class="fw-bold mb-0">$<?= number_format($vaultBalance['balance_usd'], 2) ?></h2>
                        <span class="badge bg-dark text-white"><?= number_format($vaultBalance['balance_ves'], 2) ?>
                            Bs</span>
                    </div>
                    <div class="text-end">
                        <i class="fa fa-vault fa-3x opacity-25 mb-2"></i><br>
                        <a href="caja_chica.php" class="btn btn-sm btn-light text-dark">Ver Movimientos</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-warning text-dark shadow h-100">
                <div class="card-body text-center">
                    <h1 class="fw-bold display-4 mb-0"><?= $ordenesPendientes ?></h1>
                    <small class="text-uppercase fw-bold">Pedidos Pendientes</small>
                    <a href="ventas.php?filter=pending" class="stretched-link"></a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-danger text-white shadow h-100">
                <div class="card-body text-center">
                    <h1 class="fw-bold display-4 mb-0"><?= $stockCritico ?></h1>
                    <small class="text-uppercase fw-bold">Stock Bajo</small>
                    <a href="productos.php?filter=stock_bajo" class="stretched-link"></a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow h-100">
                <div class="card-header bg-white fw-bold">🛒 Últimas Ventas</div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimasVentas as $v):
                                $badge = $v['status'] == 'paid' ? 'bg-success' : 'bg-secondary';
                                ?>
                                <tr>
                                    <td class="fw-bold">#<?= $v['id'] ?></td>
                                    <td><?= htmlspecialchars($v['name'] ?? 'Consumidor') ?></td>
                                    <td class="fw-bold text-success">$<?= number_format($v['total_price'], 2) ?></td>
                                    <td><span class="badge <?= $badge ?>"><?= $v['status'] ?></span></td>
                                    <td class="text-end"><a href="ver_venta.php?id=<?= $v['id'] ?>"
                                            class="btn btn-sm btn-outline-primary"><i class="fa fa-eye"></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white text-center">
                    <a href="ventas.php" class="text-decoration-none small">Ver todas las ventas</a>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow h-100">
                <div class="card-header bg-white fw-bold">💰 Actividad Reciente (Bóveda)</div>
                <div class="list-group list-group-flush">
                    <?php foreach ($ultimosMovimientos as $mov):
                        $color = $mov['type'] == 'deposit' ? 'text-success' : 'text-danger';
                        $sign = $mov['type'] == 'deposit' ? '+' : '-';
                        ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <small class="d-block fw-bold text-truncate"
                                    style="max-width: 150px;"><?= htmlspecialchars($mov['origin']) ?></small>
                                <small class="text-muted" style="font-size: 0.75rem;"><?= $mov['description'] ?></small>
                            </div>
                            <div class="text-end">
                                <span class="fw-bold <?= $color ?>"><?= $sign ?>     <?= number_format($mov['amount'], 2) ?>
                                    <?= $mov['currency'] ?></span><br>
                                <small class="text-muted"
                                    style="font-size: 0.7rem;"><?= date('d/m H:i', strtotime($mov['created_at'])) ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>