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

// 2. Procesar Costo de Delivery
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_delivery_cost'])) {
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
        die("Error de seguridad: Token CSRF inválido");
    }
    $newCost = floatval($_POST['delivery_base_cost']);
    if ($newCost >= 0) {
        if ($config->update('delivery_base_cost', $newCost)) {
            $success_message = "Costo base de delivery actualizado a <strong>$newCost</strong>.";
        } else {
            $error_message = "Error al guardar configuración de delivery.";
        }
    } else {
        $error_message = "El costo debe ser mayor o igual a 0.";
    }
}

// 3. OBTENER TODAS LAS MÉTRICAS
$currentRate = $config->get('exchange_rate');
$deliveryBaseCost = $config->get('delivery_base_cost', 0.00);
$vaultBalance = $vaultManager->getBalance();

// Métricas de Ventas (Ingresos)
$ventasDia = $orderManager->getTotalVentasDia();
$countVentasDia = $orderManager->getCountVentasDia(); // Nuevo conteo
$ventasSemana = $orderManager->getTotalVentasSemana();
$ventasMes = $orderManager->getTotalVentasMes();
$ventasAnio = $orderManager->getTotalVentasAnio();

// Métricas Operativas
$ordenesPendientes = $orderManager->countOrdersByStatus('pending');

// Stock Crítico Unificado (3 Inventarios)
$lowProducts = $productManager->getLowStockProducts();
$lowMaterials = $rawMaterialManager->getLowStockMaterials();
$lowManufactured = $productionManager->getLowStockManufactured();
$stockCritico = count($lowProducts) + count($lowMaterials) + count($lowManufactured);

// Listas Recientes
$ultimasVentas = $orderManager->getUltimosPedidos(5);
$stmt = $db->query("SELECT * FROM vault_movements ORDER BY created_at DESC LIMIT 5");
$ultimosMovimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../templates/header.php';
?>

<div class="container-fluid mt-4 px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>🚀 Dashboard Principal</h2>
            <p class="text-muted">Resumen de actividad y operaciones</p>
        </div>
        <!-- Configuraciones -->
        <div class="d-flex gap-3">
            <!-- Tasa de Cambio -->
            <form method="POST" class="d-flex gap-2 bg-white p-2 rounded shadow-sm align-items-center">
                <?= Csrf::insertTokenField() ?>
                <span class="fw-bold text-muted small">TASA:</span>
                <div class="input-group input-group-sm" style="width: 150px;">
                    <span class="input-group-text bg-light border-0">$1 =</span>
                    <input type="number" step="0.01" name="new_exchange_rate"
                        class="form-control border-0 bg-light fw-bold text-center" value="<?= $currentRate ?>">
                </div>
                <button type="submit" name="update_exchange_rate" class="btn btn-sm btn-primary">
                    <i class="fa fa-save"></i>
                </button>
            </form>

            <!-- Costo Delivery -->
            <form method="POST" class="d-flex gap-2 bg-white p-2 rounded shadow-sm align-items-center">
                <?= Csrf::insertTokenField() ?>
                <span class="fw-bold text-muted small">DELIVERY (BASE):</span>
                <div class="input-group input-group-sm" style="width: 150px;">
                    <span class="input-group-text bg-light border-0">$</span>
                    <input type="number" step="0.01" name="delivery_base_cost"
                        class="form-control border-0 bg-light fw-bold text-center" value="<?= $deliveryBaseCost ?>">
                </div>
                <button type="submit" name="update_delivery_cost" class="btn btn-sm btn-warning">
                    <i class="fa fa-truck"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Mensajes de Alerta -->
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle me-2"></i> <?= $success_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-exclamation-circle me-2"></i> <?= $error_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- 1. METRICAS PRINCIPALES (Estilo Reportes.php) -->
    <div class="row g-4 mb-4">
        <!-- Ventas Hoy -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white h-100 shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1 opacity-75">Ventas Hoy</h6>
                            <h2 class="mb-0 fw-bold">$<?= number_format($ventasDia, 2) ?></h2>
                        </div>
                        <i class="fa fa-calendar-day fa-3x opacity-25"></i>
                    </div>
                    <div class="small mt-3 opacity-75">
                        <i class="fa fa-chart-line"></i> <?= $countVentasDia ?> Órdenes Completadas
                    </div>
                </div>
            </div>
        </div>

        <!-- Ventas Semana -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white h-100 shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1 opacity-75">Últimos 7 Días</h6>
                            <h2 class="mb-0 fw-bold">$<?= number_format($ventasSemana, 2) ?></h2>
                        </div>
                        <i class="fa fa-calendar-week fa-3x opacity-25"></i>
                    </div>
                    <div class="small mt-3 opacity-75">
                        <i class="fa fa-level-up-alt"></i> Tendencia Semanal
                    </div>
                </div>
            </div>
        </div>

        <!-- Ventas Mes -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white h-100 shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1 opacity-75">Este Mes</h6>
                            <h2 class="mb-0 fw-bold">$<?= number_format($ventasMes, 2) ?></h2>
                        </div>
                        <i class="fa fa-chart-bar fa-3x opacity-25"></i>
                    </div>
                    <div class="small mt-3 opacity-75">
                        <i class="fa fa-dollar-sign"></i> Ingresos Totales
                    </div>
                </div>
            </div>
        </div>

        <!-- Ventas Año -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-secondary text-white h-100 shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1 opacity-75">Acumulado Año</h6>
                            <h2 class="mb-0 fw-bold">$<?= number_format($ventasAnio, 2) ?></h2>
                        </div>
                        <i class="fa fa-wallet fa-3x opacity-25"></i>
                    </div>
                    <div class="small mt-3 opacity-75">
                        <i class="fa fa-globe"></i> Ejercicio Fiscal
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. SECCIÓN OPERATIVA (Bóveda + KDS / Ventas Hoy Count) -->
    <div class="row g-4 mb-4">
        <!-- Bóveda Central -->
        <div class="col-xl-6 col-md-6">
            <div class="card bg-dark text-white h-100 shadow border-2 border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1 text-warning">Bóveda Central</h6>
                            <h2 class="mb-0 fw-bold text-white">$<?= number_format($vaultBalance['balance_usd'], 2) ?>
                            </h2>
                            <div class="mt-2">
                                <span class="badge bg-light text-dark rounded-pill">
                                    <?= number_format($vaultBalance['balance_ves'], 2) ?> Bs
                                </span>
                            </div>
                        </div>
                        <i class="fa fa-university fa-3x text-warning opacity-50"></i>
                    </div>
                    <div class="mt-3">
                        <a href="caja_chica.php" class="btn btn-sm btn-outline-warning rounded-pill px-3">
                            Ver Movimientos <i class="fa fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ventas Hoy (KDS Replacement) - Estilo Tarjeta Reportes (Blanco/Shadow) -->
        <div class="col-xl-6 col-md-6">
            <div class="card bg-white h-100 shadow border-0 border-start border-4 border-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-uppercase text-muted fw-bold mb-1">Ventas Hoy (Operaciones)</h6>
                            <h1 class="display-4 fw-bold text-dark mb-0"><?= $countVentasDia ?></h1>
                            <span class="text-success small fw-bold"><i class="fa fa-check-circle"></i> Procesadas
                                Correctamente</span>
                        </div>

                        <!-- Dropdown KDS -->
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm rounded-circle shadow-sm" type="button"
                                data-bs-toggle="dropdown">
                                <i class="fa fa-tv text-secondary"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                <li>
                                    <h6 class="dropdown-header">Pantallas KDS</h6>
                                </li>
                                <li><a class="dropdown-item" href="../paginas/kds_tv.php" target="_blank"><i
                                            class="fa fa-tv me-2"></i>Monitor General</a></li>
                                <li><a class="dropdown-item" href="../paginas/kds_pizza_tv.php" target="_blank"><i
                                            class="fa fa-pizza-slice me-2"></i>Monitor Pizza</a></li>
                                <li><a class="dropdown-item" href="../paginas/kds_cocina_tv.php" target="_blank"><i
                                            class="fa fa-fire me-2"></i>Monitor Cocina</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-3 text-end">
                        <i class="fa fa-receipt fa-4x text-danger opacity-10 position-absolute"
                            style="bottom: 10px; right: 20px;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. LISTAS Y TABLAS -->
    <div class="row g-4 mb-5">
        <!-- Últimas Ventas -->
        <div class="col-lg-8">
            <div class="card shadow h-100 border-0">
                <div class="card-header bg-white fw-bold py-3 border-bottom">
                    <i class="fa fa-receipt text-primary me-2"></i> Últimas Ventas
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0 ps-3">ID</th>
                                <th class="border-0">Total</th>
                                <th class="border-0">Método</th>
                                <th class="border-0">Estado</th>
                                <th class="border-0 text-end pe-3">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimasVentas as $venta): ?>
                                <tr>
                                    <td class="fw-bold ps-3">#<?= $venta['id'] ?></td>
                                    <td class="text-success fw-bold">$<?= number_format($venta['total_price'], 2) ?></td>
                                    <td><span
                                            class="badge bg-light text-dark border"><?= $venta['payment_method'] ?? 'N/A' ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = match ($venta['status']) {
                                            'paid' => 'bg-success',
                                            'pending' => 'bg-warning text-dark',
                                            'cancelled' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                        $statusLabel = match ($venta['status']) {
                                            'paid' => 'Pagado',
                                            'pending' => 'Pendiente',
                                            'cancelled' => 'Cancelado',
                                            default => ucfirst($venta['status'])
                                        };
                                        ?>
                                        <span class="badge <?= $statusClass ?> rounded-pill"><?= $statusLabel ?></span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="ver_venta.php?id=<?= $venta['id'] ?>"
                                            class="btn btn-sm btn-light text-primary rounded-circle">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Alertas y Resumen -->
        <div class="col-lg-4">
            <!-- Detailed Stock Analysis -->
            <div class="card shadow mb-4 border-0">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-danger"><i class="fa fa-exclamation-triangle me-2"></i> Estado Crítico del Inventario</h6>
                    <span class="badge bg-danger rounded-pill"><?= $stockCritico ?> Alertas</span>
                </div>
                <div class="card-body p-0">
                    <div class="accordion accordion-flush" id="accordionStock">
                        
                        <!-- 1. MATERIAS PRIMAS -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fw-bold text-muted small text-uppercase" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne">
                                    <i class="fa fa-carrot me-2 text-success"></i> Insumos (Stock Físico)
                                    <span class="badge bg-success-subtle text-success ms-auto"><?= count($lowMaterials) ?></span>
                                </button>
                            </h2>
                            <div id="flush-collapseOne" class="accordion-collapse collapse show" data-bs-parent="#accordionStock">
                                <div class="accordion-body p-0">
                                    <?php if (empty($lowMaterials)): ?>
                                        <div class="p-3 text-center small text-muted">Todo en orden</div>
                                    <?php else: ?>
                                        <ul class="list-group list-group-flush small">
                                            <?php foreach ($lowMaterials as $m): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-3">
                                                    <div>
                                                        <div class="fw-bold text-dark"><?= htmlspecialchars($m['name']) ?></div>
                                                        <div class="text-muted" style="font-size: 0.75rem;">Min: <?= floatval($m['min_stock']) ?> <?= $m['unit'] ?></div>
                                                    </div>
                                                    <div class="badge bg-danger-subtle text-danger rounded-pill">
                                                        <?= floatval($m['stock_quantity']) ?> <?= $m['unit'] ?>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                    <div class="p-2 text-center bg-light border-top">
                                        <a href="insumos.php" class="small fw-bold text-decoration-none">Gestionar Insumos</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. PRODUCCIÓN (Manufacturados) -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fw-bold text-muted small text-uppercase" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseTwo">
                                    <i class="fa fa-blender me-2 text-warning"></i> Producción (Cuellos de Botella)
                                    <span class="badge bg-warning-subtle text-warning ms-auto"><?= count($lowManufactured) ?></span>
                                </button>
                            </h2>
                            <div id="flush-collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionStock">
                                <div class="accordion-body p-0">
                                    <?php if (empty($lowManufactured)): ?>
                                        <div class="p-3 text-center small text-muted">Producción óptima</div>
                                    <?php else: ?>
                                        <ul class="list-group list-group-flush small">
                                            <?php foreach ($lowManufactured as $m): ?>
                                                <li class="list-group-item px-3 bg-light-subtle">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span class="fw-bold text-dark"><?= htmlspecialchars($m['name']) ?></span>
                                                        <span class="badge bg-warning text-dark border border-warning rounded-pill">Posible: <?= floatval($m['display_stock']) ?> <?= $m['unit'] ?></span>
                                                    </div>
                                                    <!-- Alert Limiting Ingredient -->
                                                    <?php if (!empty($m['limiting_ingredient'])): ?>
                                                        <div class="alert alert-danger p-1 mb-0 d-flex align-items-center border-0" style="font-size: 0.7rem; background-color: rgba(220, 53, 69, 0.1); color: #dc3545;">
                                                            <i class="fa fa-exclamation-circle me-1"></i>
                                                            <span>Falta: <strong><?= htmlspecialchars($m['limiting_ingredient']['name']) ?></strong> (<?= floatval($m['limiting_ingredient']['available']) ?> <?= $m['limiting_ingredient']['unit'] ?>)</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                    <div class="p-2 text-center bg-light border-top">
                                        <a href="manufactura.php" class="small fw-bold text-decoration-none">Ir a Producción</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. PRODUCTOS VENTA -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fw-bold text-muted small text-uppercase" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseThree">
                                    <i class="fa fa-pizza-slice me-2 text-danger"></i> Productos Ventas
                                    <span class="badge bg-danger-subtle text-danger ms-auto"><?= count($lowProducts) ?></span>
                                </button>
                            </h2>
                            <div id="flush-collapseThree" class="accordion-collapse collapse" data-bs-parent="#accordionStock">
                                <div class="accordion-body p-0">
                                    <?php if (empty($lowProducts)): ?>
                                        <div class="p-3 text-center small text-muted">Stock de venta OK</div>
                                    <?php else: ?>
                                        <ul class="list-group list-group-flush small">
                                            <?php foreach ($lowProducts as $p): ?>
                                                <li class="list-group-item px-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span class="fw-bold text-dark"><?= htmlspecialchars($p['name']) ?></span>
                                                        <span class="badge bg-danger text-white rounded-pill">Disp: <?= floatval($p['stock']) ?></span>
                                                    </div>
                                                    
                                                    <?php if (($p['stock_source'] ?? '') === 'virtual' && !empty($p['limiting_component'])): ?>
                                                        <div class="alert alert-warning p-1 mb-0 d-flex align-items-center border-0" style="font-size: 0.7rem; background-color: #fff3cd; color: #856404;">
                                                            <i class="fa fa-link me-1"></i>
                                                            <span>Limita: <strong><?= htmlspecialchars($p['limiting_component']['name']) ?></strong> (<?= floatval($p['limiting_component']['available']) ?> <?= $p['limiting_component']['unit'] ?>)</span>
                                                        </div>
                                                    <?php elseif (($p['stock_source'] ?? '') === 'physical'): ?>
                                                        <div class="text-muted" style="font-size: 0.7rem;">
                                                            <i class="fa fa-box-open me-1"></i> Stock Físico Bajo (Min: <?= $p['min_stock'] ?>)
                                                        </div>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                    <div class="p-2 text-center bg-light border-top">
                                        <a href="productos.php" class="small fw-bold text-decoration-none">Catálogo de Productos</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Accesos Rápidos -->
            <div class="card shadow border-0">
                <div class="card-header bg-white fw-bold py-3 border-bottom">
                    <i class="fa fa-bolt text-warning me-2"></i> Accesos Rápidos
                </div>
                <div class="list-group list-group-flush">
                    <a href="ventas.php"
                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-3 py-3 border-bottom icon-link-hover">
                        <span><i class="fa fa-cash-register me-2 text-primary"></i> Nueva Venta</span>
                        <i class="fa fa-chevron-right small text-muted"></i>
                    </a>
                    <a href="compras.php"
                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-3 py-3 border-bottom icon-link-hover">
                        <span><i class="fa fa-truck me-2 text-info"></i> Registrar Compra</span>
                        <i class="fa fa-chevron-right small text-muted"></i>
                    </a>
                    <a href="reportes.php"
                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-3 py-3 icon-link-hover">
                        <span><i class="fa fa-chart-pie me-2 text-success"></i> Ver Reportes</span>
                        <i class="fa fa-chevron-right small text-muted"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>