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

// Obtener Tenant Context
global $app;
$container = $app->getContainer();
/** @var \Minimarcket\Core\Tenant\TenantContext $tenantContext */
$tenantContext = $container->get(\Minimarcket\Core\Tenant\TenantContext::class);
$tenantId = $tenantContext->getTenantId();

// --- FILTROS DE FECHA ---
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // Principio de mes
$endDate = $_GET['end_date'] ?? date('Y-m-d');     // Hoy

// Ajustar hora para consultas SQL (00:00:00 a 23:59:59)
$startSql = $startDate . " 00:00:00";
$endSql = $endDate . " 23:59:59";

// =========================================================
// 1. MÉTRICAS FINANCIERAS (VENTAS Y COSTOS)
// =========================================================

// A. VENTAS TOTALES (Todas las órdenes pagadas/entregadas, independiente del método de pago)
$sqlSales = "SELECT SUM(total_price) as total FROM orders
             WHERE status IN ('paid', 'delivered') AND created_at BETWEEN ? AND ? AND tenant_id = ?";
$stmt = $db->prepare($sqlSales);
$stmt->execute([$startSql, $endSql, $tenantId]);
$ventasNetas = $stmt->fetchColumn() ?: 0;

// B. INGRESOS POR TRANSACCIONES (Para referencia de flujo de caja)
$sqlIncome = "SELECT SUM(amount_usd_ref) as total FROM transactions
              WHERE type = 'income' AND created_at BETWEEN ? AND ? AND tenant_id = ?";
$stmt = $db->prepare($sqlIncome);
$stmt->execute([$startSql, $endSql, $tenantId]);
$ingresosCaja = $stmt->fetchColumn() ?: 0;

// C. VUELTOS (Salidas de dinero por cambio a clientes)
$sqlChange = "SELECT SUM(amount_usd_ref) as total FROM transactions
              WHERE type = 'expense' AND reference_type = 'order' AND created_at BETWEEN ? AND ? AND tenant_id = ?";
$stmt = $db->prepare($sqlChange);
$stmt->execute([$startSql, $endSql, $tenantId]);
$vueltos = $stmt->fetchColumn() ?: 0;

// D. GASTOS OPERATIVOS (Compras a Proveedores / Retiros de Caja)
// D. GASTOS OPERATIVOS (Compras a Proveedores / Retiros de Caja / Nomina Admin)
// Nota: Excluiremos Nómina de Cocina aquí para ponerla como Costo Directo, 
// Pero primero obtenemos TODOS los gastos para luego restar si es necesario.
// Mejor estrategia: Consultar Payroll Separado.

// 1. Costo Mano de Obra Directa (Cocina)
$sqlLaborDirect = "SELECT SUM(t.amount_usd_ref) 
                   FROM transactions t 
                   JOIN payroll_payments p ON t.reference_id = p.id AND t.reference_type = 'adjustment'
                   JOIN users u ON p.user_id = u.id
                   WHERE t.type = 'expense' 
                   AND u.job_role = 'kitchen'
                   AND t.created_at BETWEEN ? AND ?
                   AND t.tenant_id = ?";
$stmt = $db->prepare($sqlLaborDirect);
$stmt->execute([$startSql, $endSql, $tenantId]);
$costoManoObraDirecta = $stmt->fetchColumn() ?: 0;

// 2. Costo Mano de Obra Administrativa (Gerente, Cajero, etc)
$sqlLaborAdmin = "SELECT SUM(t.amount_usd_ref) 
                  FROM transactions t 
                  JOIN payroll_payments p ON t.reference_id = p.id AND t.reference_type = 'adjustment'
                  JOIN users u ON p.user_id = u.id
                  WHERE t.type = 'expense' 
                  AND u.job_role != 'kitchen'
                  AND t.created_at BETWEEN ? AND ?
                  AND t.tenant_id = ?";
$stmt = $db->prepare($sqlLaborAdmin);
$stmt->execute([$startSql, $endSql, $tenantId]);
$gastosNominaAdmin = $stmt->fetchColumn() ?: 0;

// 3. Otros Gastos Operativos (Proveedores, Servicios, etc - Excluyendo Nómina, Vueltos y Transferencias Internas)
// Excluimos:
// - 'order' (vueltos a clientes)
// - 'adjustment' (nómina, ya contabilizada arriba)
// - 'manual' (transferencias entre cuentas, NO son gastos reales)
$sqlTotalExpense = "SELECT SUM(amount_usd_ref) FROM transactions 
                    WHERE type = 'expense' 
                    AND reference_type NOT IN ('order', 'adjustment', 'manual')
                    AND created_at BETWEEN ? AND ? AND tenant_id = ?";
$stmt = $db->prepare($sqlTotalExpense);
$stmt->execute([$startSql, $endSql, $tenantId]);
$gastosOperativosGenerales = $stmt->fetchColumn() ?: 0;

// =========================================================
// 2. COSTO DE VENTA (COGS) - LO QUE TE COSTÓ LA COMIDA
// =========================================================
// Esta es la métrica más importante. Calcula cuánto gastaste en materia prima
// para generar esas ventas.

// Consultamos todos los ítems vendidos en el rango
$sqlItems = "SELECT oi.product_id, SUM(oi.quantity) as qty_sold
             FROM order_items oi
             JOIN orders o ON oi.order_id = o.id
             JOIN products p ON oi.product_id = p.id
             WHERE o.status IN ('paid', 'delivered')
             AND o.created_at BETWEEN ? AND ? AND o.tenant_id = ?
             GROUP BY oi.product_id, p.name, p.price_usd";
$stmt = $db->prepare($sqlItems);
$stmt->execute([$startSql, $endSql, $tenantId]);
$soldItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

$costoMateriaPrima = 0; // Initialize for final result
$totalCOGS = 0; // New variable for the optimized calculation
$debugCOGS = []; // Debug array

// OPTIMIZACIÓN: Precargar TODOS los componentes de una vez (elimina N+1)
// Obtener IDs únicos de productos vendidos
$soldProductIds = array_unique(array_column($soldItems, 'product_id'));

// Precargar componentes de TODOS los productos vendidos en una sola query
$componentsMap = [];
if (!empty($soldProductIds)) {
    $placeholders = implode(',', array_fill(0, count($soldProductIds), '?'));
    $sql = "SELECT pc.product_id, pc.component_type, pc.component_id, pc.quantity,
            CASE
                WHEN pc.component_type = 'raw' THEN rm.name
                WHEN pc.component_type = 'manufactured' THEN mp.name
                WHEN pc.component_type = 'product' THEN p.name
            END as item_name,
            CASE
                WHEN pc.component_type = 'raw' THEN rm.cost_per_unit
                WHEN pc.component_type = 'manufactured' THEN mp.unit_cost_average
                WHEN pc.component_type = 'product' THEN 0
            END as item_cost
            FROM product_components pc
            LEFT JOIN raw_materials rm ON pc.component_id = rm.id AND pc.component_type = 'raw'
            LEFT JOIN manufactured_products mp ON pc.component_id = mp.id AND pc.component_type = 'manufactured'
            LEFT JOIN products p ON pc.component_id = p.id AND pc.component_type = 'product'
            WHERE pc.product_id IN ($placeholders)";

    $stmt = $db->prepare($sql);
    $stmt->execute($soldProductIds);
    $allComponents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organizar componentes por product_id
    foreach ($allComponents as $comp) {
        $productId = $comp['product_id'];
        if (!isset($componentsMap[$productId])) {
            $componentsMap[$productId] = [];
        }

        // Calcular costo recursivo para componentes de tipo 'product'
        if ($comp['component_type'] == 'product' && $comp['component_id']) {
            // Ensure productManager is available and has calculateProductCost method
            $comp['item_cost'] = $productManager->calculateProductCost($comp['component_id']);
        }

        $componentsMap[$productId][] = $comp;
    }
}

// Ahora calcular COGS usando los componentes precargados
foreach ($soldItems as $item) {
    $productId = $item['product_id'];
    $qtySold = $item['qty_sold'];

    // Obtener componentes del mapa (ya precargados)
    $components = $componentsMap[$productId] ?? [];

    $costPerUnit = 0;
    if (empty($components)) {
        // If no recipe, it's a simple product. Assume 0 cost for now as per original logic.
        // Or, if products have a 'cost_price' field, use that here.
        $costPerUnit = 0;
    } else {
        foreach ($components as $comp) {
            $itemCost = floatval($comp['item_cost'] ?? 0);
            $costPerUnit += ($comp['quantity'] * $itemCost);
        }
    }

    $totalCostThisProduct = $costPerUnit * $qtySold;
    $totalCOGS += $totalCostThisProduct;

    // Debug info
    $debugCOGS[] = [
        'product' => $productManager->getProductById($productId)['name'] ?? 'Unknown',
        'qty' => $qtySold,
        'unit_cost' => $costPerUnit,
        'total' => $totalCostThisProduct
    ];
}

// =========================================================
// 3. RESULTADOS FINALES
// =========================================================

// UTILIDAD BRUTA (Ventas - Costo Comida)
// UTILIDAD BRUTA (Ventas - Costo Comida - Mano de Obra Directa)
// Algunos prefieren poner Mano de Obra abajo, pero en manufactura suele ser Costo Directo.
// Lo pondremos separado para visibilidad.

$costoProduccionTotal = $totalCOGS + $costoManoObraDirecta;
$utilidadBruta = $ventasNetas - $costoProduccionTotal;
$margenBruto = ($ventasNetas > 0) ? ($utilidadBruta / $ventasNetas) * 100 : 0;

// UTILIDAD NETA (Utilidad Bruta - Gastos Op - Nomina Admin)
$totalGastosOp = $gastosOperativosGenerales + $gastosNominaAdmin;
$utilidadNeta = $utilidadBruta - $totalGastosOp;
$margenNeto = ($ventasNetas > 0) ? ($utilidadNeta / $ventasNetas) * 100 : 0;

// =========================================================
// 4. ANÁLISIS ADICIONAL - PRODUCTOS MÁS VENDIDOS
// =========================================================
$sqlTopProducts = "SELECT p.name, p.kitchen_station, SUM(oi.quantity) as qty_sold, 
                   SUM(oi.quantity * oi.price) as revenue_usd
                   FROM order_items oi
                   JOIN orders o ON oi.order_id = o.id
                   JOIN products p ON oi.product_id = p.id
                   WHERE o.status IN ('paid', 'delivered')
                   AND o.created_at BETWEEN ? AND ? AND o.tenant_id = ?
                   GROUP BY p.id, p.name, p.kitchen_station
                   ORDER BY revenue_usd DESC
                   LIMIT 10";
$stmt = $db->prepare($sqlTopProducts);
$stmt->execute([$startSql, $endSql, $tenantId]);
$topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =========================================================
// 5. TENDENCIA DIARIA (Para gráfica de línea)
// =========================================================
$sqlDailyTrend = "SELECT DATE(o.created_at) as date, 
                  SUM(o.total_price) as daily_revenue,
                  COUNT(o.id) as order_count
                  FROM orders o
                  WHERE o.status IN ('paid', 'delivered')
                  AND o.created_at BETWEEN ? AND ? AND o.tenant_id = ?
                  GROUP BY DATE(o.created_at)
                  ORDER BY date ASC";
$stmt = $db->prepare($sqlDailyTrend);
$stmt->execute([$startSql, $endSql, $tenantId]);
$dailyTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =========================================================
// 6. ANÁLISIS POR CATEGORÍA
// =========================================================
$sqlByCategory = "SELECT p.kitchen_station, 
                  SUM(oi.quantity * oi.price) as revenue,
                  COUNT(DISTINCT oi.order_id) as orders
                  FROM order_items oi
                  JOIN orders o ON oi.order_id = o.id
                  JOIN products p ON oi.product_id = p.id
                  WHERE o.status IN ('paid', 'delivered')
                  AND o.created_at BETWEEN ? AND ? AND o.tenant_id = ?
                  GROUP BY p.kitchen_station
                  ORDER BY revenue DESC";
$stmt = $db->prepare($sqlByCategory);
$stmt->execute([$startSql, $endSql, $tenantId]);
$categoryData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =========================================================
// 7. MÉTODOS DE PAGO (Para gráfica)
// =========================================================
$sqlPaymentMethods = "SELECT pm.name, pm.currency, 
                      SUM(t.amount) as total_nominal, 
                      SUM(t.amount_usd_ref) as total_usd,
                      COUNT(t.id) as transaction_count
                      FROM transactions t
                      JOIN payment_methods pm ON t.payment_method_id = pm.id
                      WHERE t.type = 'income' AND t.created_at BETWEEN ? AND ? AND t.tenant_id = ?
                      GROUP BY pm.id, pm.name, pm.currency
                      ORDER BY total_usd DESC";
$stmt = $db->prepare($sqlPaymentMethods);
$stmt->execute([$startSql, $endSql, $tenantId]);
$paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =========================================================
// 8. ESTADÍSTICAS GENERALES
// =========================================================
$sqlStats = "SELECT 
             COUNT(DISTINCT o.id) as total_orders,
             AVG(o.total_price) as avg_order_value,
             MAX(o.total_price) as max_order_value,
             MIN(o.total_price) as min_order_value
             FROM orders o
             WHERE o.status IN ('paid', 'delivered')
             AND o.created_at BETWEEN ? AND ? AND o.tenant_id = ?";
$stmt = $db->prepare($sqlStats);
$stmt->execute([$startSql, $endSql, $tenantId]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// =========================================================
// 9. ANÁLISIS HORARIO (Horas pico)
// =========================================================
$sqlHourly = "SELECT HOUR(o.created_at) as hour, 
              COUNT(o.id) as order_count,
              SUM(o.total_price) as revenue
              FROM orders o
              WHERE o.status IN ('paid', 'delivered')
              AND o.created_at BETWEEN ? AND ? AND o.tenant_id = ?
              GROUP BY HOUR(o.created_at)
              ORDER BY hour ASC";
$stmt = $db->prepare($sqlHourly);
$stmt->execute([$startSql, $endSql, $tenantId]);
$hourlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container-fluid mt-4 px-4">

    <?php if (isset($_GET['debug'])): ?>
        <div class="alert alert-info">
            <h5>🔍 Debug COGS Breakdown:</h5>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cant</th>
                        <th>Costo Unit</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($debugCOGS as $d): ?>
                        <tr>
                            <td><?= $d['product'] ?></td>
                            <td><?= $d['qty'] ?></td>
                            <td>$<?= number_format($d['unit_cost'], 4) ?></td>
                            <td>$<?= number_format($d['total'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>📊 Reporte de Rentabilidad</h2>
            <p class="text-muted">Análisis financiero del negocio</p>
        </div>
        <form class="d-flex gap-2 bg-white p-2 rounded shadow-sm">
            <div class="input-group">
                <span class="input-group-text bg-light border-0"><i class="fa fa-calendar"></i></span>
                <input type="date" name="start_date" class="form-control border-0 bg-light" value="<?= $startDate ?>">
            </div>
            <div class="input-group">
                <span class="input-group-text bg-light border-0">a</span>
                <input type="date" name="end_date" class="form-control border-0 bg-light" value="<?= $endDate ?>">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Filtrar</button>
        </form>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white h-100 shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1 opacity-75">Ventas Netas (Ingresos)</h6>
                            <h2 class="mb-0 fw-bold">$<?= number_format($ventasNetas, 2) ?></h2>
                        </div>
                        <i class="fa fa-cash-register fa-3x opacity-25"></i>
                    </div>
                    <div class="small mt-3 opacity-75">
                        <i class="fa fa-arrow-up"></i> Ingresos menos vueltos
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-dark h-100 shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1 opacity-75">Costo Producción (Directo)</h6>
                            <h2 class="mb-0 fw-bold text-danger">$<?= number_format($costoProduccionTotal, 2) ?></h2>
                        </div>
                        <i class="fa fa-box-open fa-3x opacity-25"></i>
                    </div>
                    <div class="small mt-3">
                        Insumos: $<?= number_format($costoMateriaPrima, 2) ?>
                        <br>
                        M. Obra: $<?= number_format($costoManoObraDirecta, 2) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white h-100 shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1 opacity-75">Ganancia Bruta</h6>
                            <h2 class="mb-0 fw-bold">$<?= number_format($utilidadBruta, 2) ?></h2>
                        </div>
                        <i class="fa fa-chart-line fa-3x opacity-25"></i>
                    </div>
                    <div class="small mt-3 opacity-75">
                        <i class="fa fa-percentage"></i> Margen: <?= number_format($margenBruto, 1) ?>%
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-dark text-white h-100 shadow border-2 border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1 text-info">Utilidad Neta (Bolsillo)</h6>
                            <h2 class="mb-0 fw-bold text-info">$<?= number_format($utilidadNeta, 2) ?></h2>
                        </div>
                        <i class="fa fa-wallet fa-3x text-info opacity-50"></i>
                    </div>
                    <div class="small mt-3 text-muted">
                        Después de gastos ($<?= number_format($totalGastosOp, 2) ?>)
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-white fw-bold">
                    <i class="fa fa-chart-pie me-2"></i> Distribución del Dinero
                </div>
                <div class="card-body">
                    <canvas id="financeChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-white fw-bold d-flex justify-content-between">
                    <span><i class="fa fa-trophy me-2"></i> Top Productos Vendidos</span>
                    <span class="badge bg-primary">Por Volumen</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Cant.</th>
                                    <th class="text-end">Ingreso Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Ordenar items vendidos por cantidad (ya los tenemos en $soldItems)
                                usort($soldItems, function ($a, $b) {
                                    return $b['qty_sold'] - $a['qty_sold'];
                                });
                                $top5 = array_slice($soldItems, 0, 5);

                                foreach ($top5 as $item):
                                    $p = $productManager->getProductById($item['product_id']);
                                    $totalRow = $item['qty_sold'] * $p['price_usd'];
                                    ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($p['name']) ?></td>
                                        <td class="text-center"><span
                                                class="badge bg-secondary rounded-pill"><?= $item['qty_sold'] ?></span></td>
                                        <td class="text-end text-success fw-bold">$<?= number_format($totalRow, 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($top5)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4">No hay datos en este periodo.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-white fw-bold">
                    <i class="fa fa-credit-card me-2"></i> Entradas por Método de Pago
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php
                        $sqlMethods = "SELECT pm.name, pm.currency, SUM(t.amount) as total_nominal, SUM(t.amount_usd_ref) as total_usd
                                       FROM transactions t
                                       JOIN payment_methods pm ON t.payment_method_id = pm.id
                                       WHERE t.type = 'income' AND t.created_at BETWEEN ? AND ? AND t.tenant_id = ?
                                       GROUP BY pm.name, pm.currency";
                        $stmtM = $db->prepare($sqlMethods);
                        $stmtM->execute([$startSql, $endSql, $tenantId]);
                        $methods = $stmtM->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($methods as $m):
                            ?>
                            <div class="col-md-3 mb-3">
                                <div class="border rounded p-3 text-center bg-light h-100">
                                    <div class="text-muted small text-uppercase"><?= $m['name'] ?></div>
                                    <div class="fs-5 fw-bold text-dark">
                                        <?= number_format($m['total_nominal'], 2) ?> <small><?= $m['currency'] ?></small>
                                    </div>
                                    <div class="small text-success">
                                        ≈ $<?= number_format($m['total_usd'], 2) ?> USD
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- NUEVAS SECCIONES PROFESIONALES -->

    <!-- Estadísticas Rápidas -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fa fa-shopping-cart fa-2x text-primary mb-2"></i>
                    <h3 class="fw-bold mb-0"><?= number_format($stats['total_orders'] ?? 0) ?></h3>
                    <p class="text-muted small mb-0">Total Órdenes</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fa fa-dollar-sign fa-2x text-success mb-2"></i>
                    <h3 class="fw-bold mb-0">$<?= number_format($stats['avg_order_value'] ?? 0, 2) ?></h3>
                    <p class="text-muted small mb-0">Ticket Promedio</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fa fa-arrow-up fa-2x text-info mb-2"></i>
                    <h3 class="fw-bold mb-0">$<?= number_format($stats['max_order_value'] ?? 0, 2) ?></h3>
                    <p class="text-muted small mb-0">Venta Máxima</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fa fa-percentage fa-2x text-warning mb-2"></i>
                    <h3 class="fw-bold mb-0"><?= number_format($margenNeto, 1) ?>%</h3>
                    <p class="text-muted small mb-0">Margen Neto</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficas Principales -->
    <div class="row g-4 mb-4">
        <!-- Tendencia Diaria -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="fa fa-chart-line text-primary"></i> Tendencia de Ventas Diarias</h5>
                </div>
                <div class="card-body">
                    <canvas id="dailyTrendChart" height="80"></canvas>
                </div>
            </div>
        </div>

        <!-- Categorías -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="fa fa-chart-pie text-success"></i> Ventas por Categoría</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Productos Más Vendidos y Métodos de Pago -->
    <div class="row g-4 mb-4">
        <!-- Top Productos -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="fa fa-trophy text-warning"></i> Top 10 Productos Más Vendidos</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Producto</th>
                                    <th>Categoría</th>
                                    <th class="text-end">Cantidad</th>
                                    <th class="text-end">Ingresos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1;
                                foreach ($topProducts as $prod): ?>
                                    <tr>
                                        <td>
                                            <span class="badge <?= $rank <= 3 ? 'bg-warning' : 'bg-secondary' ?>">
                                                <?= $rank ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold"><?= htmlspecialchars($prod['name']) ?></td>
                                        <td><span class="badge bg-info"><?= ucfirst($prod['kitchen_station']) ?></span></td>
                                        <td class="text-end"><?= number_format($prod['qty_sold']) ?></td>
                                        <td class="text-end text-success fw-bold">
                                            $<?= number_format($prod['revenue_usd'], 2) ?></td>
                                    </tr>
                                    <?php $rank++; endforeach; ?>
                                <?php if (empty($topProducts)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No hay datos en este período
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Métodos de Pago -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="fa fa-credit-card text-info"></i> Métodos de Pago</h5>
                </div>
                <div class="card-body">
                    <canvas id="paymentMethodChart" height="200"></canvas>
                </div>
                <div class="card-footer bg-white border-0">
                    <div class="row g-2">
                        <?php foreach ($paymentMethods as $pm): ?>
                            <div class="col-6">
                                <div class="border rounded p-2 text-center">
                                    <div class="small text-muted"><?= htmlspecialchars($pm['name']) ?></div>
                                    <div class="fw-bold text-primary">$<?= number_format($pm['total_usd'], 2) ?></div>
                                    <div class="small text-muted"><?= $pm['transaction_count'] ?> trans.</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Análisis Horario -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="fa fa-clock text-danger"></i> Patrón de Ventas por Hora (Horas Pico)</h5>
                </div>
                <div class="card-body">
                    <canvas id="hourlyChart" height="60"></canvas>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Configuración global de Chart.js
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#6c757d';

    // 1. Gráfica de Distribución de Gastos (Doughnut - Original mejorada)
    const ctx = document.getElementById('financeChart').getContext('2d');
    const financeChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Ganancia Neta', 'Costo Producción', 'Mano Obra (Cocina)', 'Nómina Admin', 'Otros Gastos'],
            datasets: [{
                data: [<?= max(0, $utilidadNeta) ?>, <?= $totalCOGS ?>, <?= $costoManoObraDirecta ?>, <?= $gastosNominaAdmin ?>, <?= $gastosOperativosGenerales ?>],
                backgroundColor: [
                    '#198754', // Verde - Ganancia
                    '#ffc107', // Amarillo - MP
                    '#fd7e14', // Naranja - MO Directa
                    '#6610f2', // Púrpura - Nomina Admin
                    '#dc3545'  // Rojo - Otros
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { padding: 15, font: { size: 12 } }
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            let label = context.label || '';
                            let value = context.parsed || 0;
                            let total = context.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = ((value / total) * 100).toFixed(1);
                            return label + ': $' + value.toFixed(2) + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });

    // 2. Tendencia Diaria (Line Chart)
    const dailyCtx = document.getElementById('dailyTrendChart').getContext('2d');
    const dailyTrendChart = new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: [<?php foreach ($dailyTrend as $d)
                echo "'" . date('d/m', strtotime($d['date'])) . "',"; ?>],
            datasets: [{
                label: 'Ventas Diarias ($)',
                data: [<?php foreach ($dailyTrend as $d)
                    echo $d['daily_revenue'] . ','; ?>],
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            return 'Ventas: $' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return '$' + value;
                        }
                    }
                }
            }
        }
    });

    // 3. Ventas por Categoría (Pie Chart)
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryChart = new Chart(categoryCtx, {
        type: 'pie',
        data: {
            labels: [<?php foreach ($categoryData as $c)
                echo "'" . ucfirst($c['kitchen_station']) . "',"; ?>],
            datasets: [{
                data: [<?php foreach ($categoryData as $c)
                    echo $c['revenue'] . ','; ?>],
                backgroundColor: [
                    '#0dcaf0', '#198754', '#ffc107', '#dc3545',
                    '#6610f2', '#fd7e14', '#20c997', '#d63384'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 10, font: { size: 11 } }
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            let value = context.parsed || 0;
                            return context.label + ': $' + value.toFixed(2);
                        }
                    }
                }
            }
        }
    });

    // 4. Métodos de Pago (Doughnut Chart)
    const paymentCtx = document.getElementById('paymentMethodChart').getContext('2d');
    const paymentMethodChart = new Chart(paymentCtx, {
        type: 'doughnut',
        data: {
            labels: [<?php foreach ($paymentMethods as $pm)
                echo "'" . $pm['name'] . "',"; ?>],
            datasets: [{
                data: [<?php foreach ($paymentMethods as $pm)
                    echo $pm['total_usd'] . ','; ?>],
                backgroundColor: [
                    '#0d6efd', '#198754', '#ffc107', '#dc3545',
                    '#0dcaf0', '#6610f2', '#fd7e14', '#d63384'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 8, font: { size: 10 } }
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            let value = context.parsed || 0;
                            return context.label + ': $' + value.toFixed(2);
                        }
                    }
                }
            }
        }
    });

    // 5. Análisis Horario (Bar Chart)
    const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
    const hourlyChart = new Chart(hourlyCtx, {
        type: 'bar',
        data: {
            labels: [<?php foreach ($hourlyData as $h)
                echo "'" . sprintf('%02d:00', $h['hour']) . "',"; ?>],
            datasets: [{
                label: 'Órdenes',
                data: [<?php foreach ($hourlyData as $h)
                    echo $h['order_count'] . ','; ?>],
                backgroundColor: 'rgba(13, 110, 253, 0.7)',
                borderColor: '#0d6efd',
                borderWidth: 1,
                yAxisID: 'y'
            }, {
                label: 'Ingresos ($)',
                data: [<?php foreach ($hourlyData as $h)
                    echo $h['revenue'] . ','; ?>],
                backgroundColor: 'rgba(25, 135, 84, 0.7)',
                borderColor: '#198754',
                borderWidth: 1,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: { padding: 15, font: { size: 12 } }
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            let label = context.dataset.label || '';
                            let value = context.parsed.y || 0;
                            if (label.includes('$')) {
                                return label + ': $' + value.toFixed(2);
                            }
                            return label + ': ' + value;
                        }
                    }
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: { display: true, text: 'Número de Órdenes' },
                    beginAtZero: true
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: { display: true, text: 'Ingresos ($)' },
                    beginAtZero: true,
                    grid: { drawOnChartArea: false },
                    ticks: {
                        callback: function (value) {
                            return '$' + value;
                        }
                    }
                }
            }
        }
    });
</script>

<?php require_once '../templates/footer.php'; ?>