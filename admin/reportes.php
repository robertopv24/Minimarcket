<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../templates/autoload.php';

session_start();
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

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
$sqlSales = "SELECT SUM(total_price) as total_usd, SUM(total_price * exchange_rate) as total_ves FROM orders
             WHERE status IN ('paid', 'delivered') AND created_at BETWEEN ? AND ?";
$stmt = $db->prepare($sqlSales);
$stmt->execute([$startSql, $endSql]);
$salesData = $stmt->fetch(PDO::FETCH_ASSOC);
$ventasNetasUsd = $salesData['total_usd'] ?: 0;
$ventasNetasVes = $salesData['total_ves'] ?: 0;

// B. INGRESOS POR TRANSACCIONES (Para referencia de flujo de caja)
$sqlIncome = "SELECT SUM(amount_usd_ref) as total FROM transactions
              WHERE type = 'income' AND created_at BETWEEN ? AND ?";
$stmt = $db->prepare($sqlIncome);
$stmt->execute([$startSql, $endSql]);
$ingresosCaja = $stmt->fetchColumn() ?: 0;

// C. VUELTOS (Salidas de dinero por cambio a clientes)
$sqlChange = "SELECT SUM(amount_usd_ref) as total FROM transactions
              WHERE type = 'expense' AND reference_type = 'order' AND created_at BETWEEN ? AND ?";
$stmt = $db->prepare($sqlChange);
$stmt->execute([$startSql, $endSql]);
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
                   AND t.created_at BETWEEN ? AND ?";
$stmt = $db->prepare($sqlLaborDirect);
$stmt->execute([$startSql, $endSql]);
$costoManoObraDirecta = $stmt->fetchColumn() ?: 0;

// 2. Costo Mano de Obra Administrativa (Gerente, Cajero, etc)
$sqlLaborAdmin = "SELECT SUM(t.amount_usd_ref) 
                  FROM transactions t 
                  JOIN payroll_payments p ON t.reference_id = p.id AND t.reference_type = 'adjustment'
                  JOIN users u ON p.user_id = u.id
                  WHERE t.type = 'expense' 
                  AND u.job_role != 'kitchen'
                  AND t.created_at BETWEEN ? AND ?";
$stmt = $db->prepare($sqlLaborAdmin);
$stmt->execute([$startSql, $endSql]);
$gastosNominaAdmin = $stmt->fetchColumn() ?: 0;

// 3. Inversión en Inventario (Compras a Proveedores con referencia 'purchase')
$sqlInventory = "SELECT SUM(amount_usd_ref) FROM transactions 
                 WHERE type = 'expense' 
                 AND reference_type = 'purchase'
                 AND created_at BETWEEN ? AND ?";
$stmt = $db->prepare($sqlInventory);
$stmt->execute([$startSql, $endSql]);
$inversionInventario = $stmt->fetchColumn() ?: 0;

// 4. Otros Gastos Operativos (Servicios, Local, etc. - Excluyendo Nómina, Compras, Vueltos y Internos)
$sqlTotalExpense = "SELECT SUM(amount_usd_ref) FROM transactions 
                    WHERE type = 'expense' 
                    AND reference_type NOT IN ('order', 'adjustment', 'manual', 'purchase')
                    AND created_at BETWEEN ? AND ?";
$stmt = $db->prepare($sqlTotalExpense);
$stmt->execute([$startSql, $endSql]);
$gastosOperativosGenerales = $stmt->fetchColumn() ?: 0;

// =========================================================
// 2. COSTO DE VENTA (COGS) - LO QUE TE COSTÓ LA COMIDA
// =========================================================
// Esta es la métrica más importante. Calcula cuánto gastaste en materia prima
// para generar esas ventas.

// Consultamos todos los ítems vendidos en el rango (Ahora traemos cost_at_sale)
$sqlItems = "SELECT oi.product_id, oi.cost_at_sale, SUM(oi.quantity) as qty_sold
             FROM order_items oi
             JOIN orders o ON oi.order_id = o.id
             JOIN products p ON oi.product_id = p.id
             WHERE o.status IN ('paid', 'delivered')
             AND o.created_at BETWEEN ? AND ?
             GROUP BY oi.product_id, oi.cost_at_sale, p.name, p.price_usd";
$stmt = $db->prepare($sqlItems);
$stmt->execute([$startSql, $endSql]);
$soldItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

$costoMateriaPrima = 0; // Initialize for final result
$totalCOGS = 0; // New variable for the optimized calculation
$debugCOGS = []; // Debug array

// 2. COSTO DE VENTA (COGS) - UNIFICADO
foreach ($soldItems as $item) {
    $productId = $item['product_id'];
    $qtySold = $item['qty_sold'];

    // PRIORIDAD: Usar el costo guardado al momento de la venta si existe
    if (floatval($item['cost_at_sale']) > 0) {
        $costPerUnit = floatval($item['cost_at_sale']);
    } else {
        // Fallback para ventas antiguas sin costo guardado
        $costPerUnit = $productManager->calculateProductCost($productId);
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
$utilidadBruta = $ventasNetasUsd - $costoProduccionTotal;
$margenBruto = ($ventasNetasUsd > 0) ? ($utilidadBruta / $ventasNetasUsd) * 100 : 0;

// UTILIDAD NETA (Utilidad Bruta - Gastos Op - Nomina Admin)
$totalGastosOp = $gastosOperativosGenerales + $gastosNominaAdmin;
$utilidadNeta = $utilidadBruta - $totalGastosOp;
$margenNeto = ($ventasNetasUsd > 0) ? ($utilidadNeta / $ventasNetasUsd) * 100 : 0;

// =========================================================
// 4. ANÁLISIS ADICIONAL - PRODUCTOS MÁS VENDIDOS
// =========================================================
$sqlTopProducts = "SELECT p.name, p.kitchen_station, SUM(oi.quantity) as qty_sold, 
                   SUM(oi.quantity * oi.price) as revenue_usd
                   FROM order_items oi
                   JOIN orders o ON oi.order_id = o.id
                   JOIN products p ON oi.product_id = p.id
                   WHERE o.status IN ('paid', 'delivered')
                   AND o.created_at BETWEEN ? AND ?
                   GROUP BY p.id, p.name, p.kitchen_station
                   ORDER BY revenue_usd DESC
                   LIMIT 10";
$stmt = $db->prepare($sqlTopProducts);
$stmt->execute([$startSql, $endSql]);
$topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =========================================================
// 5. TENDENCIA DIARIA (Para gráfica de línea)
// =========================================================
$sqlDailyTrend = "SELECT DATE(o.created_at) as date, 
                  SUM(o.total_price) as daily_revenue,
                  COUNT(o.id) as order_count
                  FROM orders o
                  WHERE o.status IN ('paid', 'delivered')
                  AND o.created_at BETWEEN ? AND ?
                  GROUP BY DATE(o.created_at)
                  ORDER BY date ASC";
$stmt = $db->prepare($sqlDailyTrend);
$stmt->execute([$startSql, $endSql]);
$dailyTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =========================================================
// 6. ANÁLISIS POR CATEGORÍA
// =========================================================
$sqlByCategory = "SELECT 
                    CASE 
                        WHEN COALESCE(NULLIF(p.kitchen_station,''), c.kitchen_station) IS NULL 
                             OR COALESCE(NULLIF(p.kitchen_station,''), c.kitchen_station) = ''
                             OR COALESCE(NULLIF(p.kitchen_station,''), c.kitchen_station) = 'none'
                        THEN 'Otros'
                        ELSE COALESCE(NULLIF(p.kitchen_station,''), c.kitchen_station) 
                    END as station_name,
                    SUM(oi.quantity * oi.price) as revenue,
                    COUNT(DISTINCT oi.order_id) as orders
                    FROM order_items oi
                    JOIN orders o ON oi.order_id = o.id
                    JOIN products p ON oi.product_id = p.id
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE o.status IN ('paid', 'delivered')
                    AND o.created_at BETWEEN ? AND ?
                    GROUP BY station_name
                    ORDER BY revenue DESC";
$stmt = $db->prepare($sqlByCategory);
$stmt->execute([$startSql, $endSql]);
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
                      WHERE t.type = 'income' AND t.created_at BETWEEN ? AND ?
                      GROUP BY pm.id, pm.name, pm.currency
                      ORDER BY total_usd DESC";
$stmt = $db->prepare($sqlPaymentMethods);
$stmt->execute([$startSql, $endSql]);
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
             AND o.created_at BETWEEN ? AND ?";
$stmt = $db->prepare($sqlStats);
$stmt->execute([$startSql, $endSql]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// =========================================================
// 9. ANÁLISIS HORARIO (Horas pico)
// =========================================================
$sqlHourly = "SELECT HOUR(o.created_at) as hour, 
              COUNT(o.id) as order_count,
              SUM(o.total_price) as revenue
              FROM orders o
              WHERE o.status IN ('paid', 'delivered')
              AND o.created_at BETWEEN ? AND ?
              GROUP BY HOUR(o.created_at)
              ORDER BY hour ASC";
$stmt = $db->prepare($sqlHourly);
$stmt->execute([$startSql, $endSql]);
$rawHourly = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Normalizar a 24 horas
$hourlyData = [];
for ($i = 0; $i < 24; $i++) {
    $hourlyData[$i] = ['hour' => $i, 'order_count' => 0, 'revenue' => 0];
}
foreach ($rawHourly as $row) {
    $hourlyData[intval($row['hour'])] = [
        'hour' => intval($row['hour']),
        'order_count' => intval($row['order_count']),
        'revenue' => floatval($row['revenue'])
    ];
}

// =========================================================
// 10. MÉTRICAS DE EFICIENCIA OPERATIVA
// =========================================================

// A. Tiempo Promedio de Preparación (Creación -> Listo en KDS)
$sqlAvgPrep = "SELECT AVG(prep_seconds) FROM (
                SELECT o.id, TIMESTAMPDIFF(SECOND, o.created_at, MAX(l.created_at)) as prep_seconds
                FROM orders o
                JOIN order_time_log l ON o.id = l.order_id
                WHERE o.status IN ('paid', 'delivered')
                AND l.event_type = 'ready'
                AND o.created_at BETWEEN ? AND ?
                GROUP BY o.id
               ) as sub";
$stmt = $db->prepare($sqlAvgPrep);
$stmt->execute([$startSql, $endSql]);
$avgPrepSeconds = (float) ($stmt->fetchColumn() ?: 0);

// B. Tiempo Promedio de Entrega (Listo -> Entregado)
$sqlAvgDelivery = "SELECT AVG(diff_seconds) FROM (
                    SELECT o.id, TIMESTAMPDIFF(SECOND, MAX(l.created_at), o.delivered_at) as diff_seconds
                    FROM orders o
                    JOIN order_time_log l ON o.id = l.order_id
                    WHERE o.status = 'delivered'
                    AND l.event_type = 'ready'
                    AND o.created_at BETWEEN ? AND ?
                    GROUP BY o.id, o.delivered_at
                  ) as sub";
$stmt = $db->prepare($sqlAvgDelivery);
$stmt->execute([$startSql, $endSql]);
$avgDeliverySeconds = (float) ($stmt->fetchColumn() ?: 0);

// C. Índice de Eficiencia (Órdenes listas en menos de 15 min)
$threshold = 900; // 15 minutos en segundos
$sqlEfficiency = "SELECT 
                    COUNT(CASE WHEN prep_seconds <= $threshold THEN 1 END) as on_time,
                    COUNT(*) as total
                  FROM (
                    SELECT o.id, TIMESTAMPDIFF(SECOND, o.created_at, MAX(l.created_at)) as prep_seconds
                    FROM orders o
                    JOIN order_time_log l ON o.id = l.order_id
                    WHERE o.status IN ('paid', 'delivered')
                    AND l.event_type = 'ready'
                    AND o.created_at BETWEEN ? AND ?
                    GROUP BY o.id
                  ) as sub";
$stmt = $db->prepare($sqlEfficiency);
$stmt->execute([$startSql, $endSql]);
$effData = $stmt->fetch(PDO::FETCH_ASSOC);
$efficiencyIndex = ($effData['total'] > 0) ? ($effData['on_time'] / $effData['total']) * 100 : 0;

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<style>
    :root {
        --glass-bg: rgba(30, 41, 59, 0.7);
        --glass-border: rgba(255, 255, 255, 0.1);
        --glass-blur: blur(12px);
        --accent-info: #0ea5e9;
        --accent-success: #10b981;
        --accent-warning: #f59e0b;
        --accent-danger: #ef4444;
    }

    body {
        background-color: #0f172a !important;
        color: #f8fafc !important;
    }

    .main-content {
        min-height: 100vh;
        padding-top: 2rem;
    }

    .glass-card {
        background: var(--glass-bg);
        backdrop-filter: var(--glass-blur);
        -webkit-backdrop-filter: var(--glass-blur);
        border: 1px solid var(--glass-border);
        border-radius: 16px;
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        overflow: hidden;
    }

    .glass-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 48px 0 rgba(0, 0, 0, 0.5);
        border-color: rgba(255, 255, 255, 0.2);
    }

    .metric-card {
        position: relative;
        padding: 1.5rem;
    }

    .metric-icon {
        position: absolute;
        top: 1rem;
        right: 1.5rem;
        font-size: 2.5rem;
        opacity: 0.15;
        transition: opacity 0.3s ease;
    }

    .glass-card:hover .metric-icon {
        opacity: 0.3;
    }

    .filter-panel {
        background: rgba(15, 23, 42, 0.6);
        border: 1px solid var(--glass-border);
        border-radius: 12px;
        padding: 1rem;
        backdrop-filter: blur(8px);
    }

    .table-glass {
        background: transparent !important;
    }

    .table-glass th {
        background: rgba(255, 255, 255, 0.05);
        border-bottom: 2px solid var(--glass-border);
        color: #94a3b8;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .table-glass td {
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        vertical-align: middle;
    }

    .badge-soft {
        background: rgba(255, 255, 255, 0.1);
        color: #f8fafc;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    canvas {
        filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
    }

    .animated-pulse {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            opacity: 1;
        }

        50% {
            opacity: 0.6;
        }

        100% {
            opacity: 1;
        }
    }
</style>

<div class="main-content container-fluid px-4">

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

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 gap-3">
        <div>
            <h1 class="text-white fw-bold mb-1">
                <i class="fa-solid fa-chart-mixed text-info me-2"></i>Reporte de Rentabilidad
            </h1>
            <p class="text-muted mb-0">Análisis financiero y forense del rendimiento del negocio.</p>
        </div>
        <form class="filter-panel d-flex gap-3 align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="fa fa-calendar text-muted"></i>
                <input type="date" name="start_date"
                    class="form-control form-control-sm bg-transparent border-0 text-white" value="<?= $startDate ?>">
                <span class="text-muted">→</span>
                <input type="date" name="end_date"
                    class="form-control form-control-sm bg-transparent border-0 text-white" value="<?= $endDate ?>">
            </div>
            <button type="submit" class="btn btn-info btn-sm px-4 rounded-pill">
                <i class="fa fa-filter me-1"></i> Filtrar
            </button>
        </form>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-xl-3 col-md-6">
            <div class="glass-card metric-card">
                <i class="fa fa-cash-register metric-icon text-primary"></i>
                <h6 class="text-uppercase text-muted mb-2 small fw-bold">Ventas Netas</h6>
                <div class="d-flex align-items-baseline gap-2">
                    <h2 class="mb-0 fw-bold text-white">$<?= number_format($ventasNetasUsd, 2) ?></h2>
                    <span class="text-muted small">USD</span>
                </div>
                <div class="mt-2 text-info small">
                    <i class="fa fa-coins me-1"></i> <?= number_format($ventasNetasVes, 2) ?> VES
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="glass-card metric-card">
                <i class="fa fa-box-open metric-icon text-warning"></i>
                <h6 class="text-uppercase text-muted mb-2 small fw-bold">Costo Insumos (Directo)</h6>
                <div class="d-flex align-items-baseline gap-2">
                    <h2 class="mb-0 fw-bold text-warning">$<?= number_format($totalCOGS, 2) ?></h2>
                    <span class="text-muted small">USD</span>
                </div>
                <div class="mt-2 text-muted small">
                    M. Obra: <span class="text-white">$<?= number_format($costoManoObraDirecta, 2) ?></span>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="glass-card metric-card" style="border-left: 3px solid var(--accent-success);">
                <i class="fa fa-chart-line metric-icon text-success"></i>
                <h6 class="text-uppercase text-muted mb-2 small fw-bold">Ganancia Bruta</h6>
                <h2 class="mb-0 fw-bold text-success">$<?= number_format($utilidadBruta, 2) ?></h2>
                <div class="mt-2">
                    <span class="badge badge-soft">Margen: <?= number_format($margenBruto, 1) ?>%</span>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="glass-card metric-card" style="border-left: 3px solid var(--accent-info);">
                <i class="fa fa-wallet metric-icon text-info"></i>
                <h6 class="text-uppercase text-info mb-2 small fw-bold">Utilidad Neta</h6>
                <h2 class="mb-0 fw-bold text-info">$<?= number_format($utilidadNeta, 2) ?></h2>
                <div class="mt-2 text-muted small">
                    Gastos Op: $<?= number_format($totalGastosOp, 2) ?>
                </div>
            </div>
        </div>

        <!-- MÉTODOS DE EFICIENCIA -->
        <div class="col-xl-12 mb-4">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="glass-card p-4 text-center border-bottom border-3 border-info">
                        <i class="fa fa-fire-burner text-info mb-2 fs-3"></i>
                        <h6 class="text-muted small text-uppercase fw-bold">T. Promedio Preparación</h6>
                        <h3 class="text-white fw-bold mb-0">
                            <?= (int) floor($avgPrepSeconds / 60) ?>m <?= ((int) $avgPrepSeconds % 60) ?>s
                        </h3>
                        <div class="small text-info mt-1">Creación → Listo KDS</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card p-4 text-center border-bottom border-3 border-success">
                        <i class="fa fa-moped text-success mb-2 fs-3"></i>
                        <h6 class="text-muted small text-uppercase fw-bold">T. Promedio Entrega</h6>
                        <h3 class="text-white fw-bold mb-0">
                            <?= (int) floor($avgDeliverySeconds / 60) ?>m <?= ((int) $avgDeliverySeconds % 60) ?>s
                        </h3>
                        <div class="small text-success mt-1">Despacho → Cliente</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card p-4 text-center border-bottom border-3 border-warning">
                        <i class="fa fa-bolt text-warning mb-2 fs-3"></i>
                        <h6 class="text-muted small text-uppercase fw-bold">Índice de Eficiencia</h6>
                        <h3 class="text-white fw-bold mb-0"><?= number_format($efficiencyIndex, 1) ?>%</h3>
                        <div class="progress mt-2" style="height: 6px; background: rgba(255,255,255,0.1);">
                            <div class="progress-bar bg-warning" style="width: <?= $efficiencyIndex ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ACCESO A REPORTE DE PRODUCTIVIDAD -->
        <div class="col-xl-12">
            <a href="productivity_report.php" class="text-decoration-none">
                <div class="glass-card p-3 d-flex justify-content-between align-items-center"
                    style="background: rgba(14, 165, 233, 0.1);">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-info bg-opacity-20 p-3 rounded-circle">
                            <i class="fa fa-stopwatch text-info fs-4"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 text-white fw-bold">Auditoría Detallada de Productividad</h5>
                            <p class="text-muted small mb-0">Rastreo forense de tiempos por orden y estación.</p>
                        </div>
                    </div>
                    <span class="btn btn-info btn-sm px-4 rounded-pill">Ver Métricas KDS <i
                            class="fa fa-arrow-right ms-2"></i></span>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-6">
            <div class="glass-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="text-white fw-bold mb-0">
                        <i class="fa fa-chart-pie text-info me-2"></i>Distribución Financiera
                    </h5>
                </div>
                <div style="height: 300px;">
                    <canvas id="financeChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="glass-card p-0 h-100">
                <div class="p-4 border-bottom border-secondary border-opacity-25">
                    <h5 class="text-white fw-bold mb-0">
                        <i class="fa fa-trophy text-warning me-2"></i>Top Productos (Volumen)
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-glass mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Producto</th>
                                <th class="text-center">Cant.</th>
                                <th class="text-end pe-4">Ingreso</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            usort($soldItems, function ($a, $b) {
                                return $b['qty_sold'] - $a['qty_sold'];
                            });
                            $top5 = array_slice($soldItems, 0, 5);

                            foreach ($top5 as $item):
                                $p = $productManager->getProductById($item['product_id']);
                                $totalRow = $item['qty_sold'] * ($p['price_usd'] ?? 0);
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-white"><?= htmlspecialchars($p['name'] ?? 'N/A') ?></div>
                                    </td>
                                    <td class="text-center">
                                        <span
                                            class="badge bg-info bg-opacity-10 text-info px-3"><?= $item['qty_sold'] ?></span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <span class="text-success fw-bold">$<?= number_format($totalRow, 2) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-12">
            <div class="glass-card p-4">
                <h5 class="text-white fw-bold mb-4">
                    <i class="fa fa-credit-card text-success me-2"></i>Flujo por Método de Pago
                </h5>
                <div class="row g-3">
                    <?php
                    $sqlMethods = "SELECT pm.name, pm.currency, SUM(t.amount) as total_nominal, SUM(t.amount_usd_ref) as total_usd
                                   FROM transactions t
                                   JOIN payment_methods pm ON t.payment_method_id = pm.id
                                   WHERE t.type = 'income' AND t.created_at BETWEEN ? AND ?
                                   GROUP BY pm.name, pm.currency";
                    $stmtM = $db->prepare($sqlMethods);
                    $stmtM->execute([$startSql, $endSql]);
                    $methods = $stmtM->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($methods as $m):
                        ?>
                        <div class="col-md-3">
                            <div class="p-3 border border-secondary border-opacity-25 rounded-3 bg-white bg-opacity-5">
                                <div class="text-muted small text-uppercase mb-1"><?= $m['name'] ?></div>
                                <div class="fs-5 fw-bold text-white">
                                    <?= number_format($m['total_nominal'], 2) ?> <span
                                        class="small opacity-50"><?= $m['currency'] ?></span>
                                </div>
                                <div class="text-info small mt-1">
                                    ≈ $<?= number_format($m['total_usd'], 2) ?> USD
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- NUEVAS SECCIONES PROFESIONALES -->

    <!-- Estadísticas Rápidas Glassmorphic -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="glass-card p-4 text-center">
                <i class="fa fa-shopping-cart fa-2x text-primary mb-3"></i>
                <h2 class="fw-bold mb-0 text-white"><?= number_format($stats['total_orders'] ?? 0) ?></h2>
                <p class="text-muted small mb-0">Total Órdenes</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card p-4 text-center">
                <i class="fa fa-dollar-sign fa-2x text-success mb-3"></i>
                <h2 class="fw-bold mb-0 text-white">$<?= number_format($stats['avg_order_value'] ?? 0, 2) ?></h2>
                <p class="text-muted small mb-0">Ticket Promedio</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card p-4 text-center">
                <i class="fa fa-arrow-up fa-2x text-info mb-3"></i>
                <h2 class="fw-bold mb-0 text-white">$<?= number_format($stats['max_order_value'] ?? 0, 2) ?></h2>
                <p class="text-muted small mb-0">Venta Máxima</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card p-4 text-center">
                <i class="fa fa-percentage fa-2x text-warning mb-3"></i>
                <h2 class="fw-bold mb-0 text-white"><?= number_format($margenNeto, 1) ?>%</h2>
                <p class="text-muted small mb-0">Margen Neto</p>
            </div>
        </div>
    </div>

    <!-- Gráficas Principales Glass -->
    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="glass-card p-4 h-100">
                <h5 class="text-white fw-bold mb-4">
                    <i class="fa fa-chart-line text-primary me-2"></i>Tendencia de Ventas (30 Días)
                </h5>
                <div style="height: 350px;">
                    <canvas id="dailyTrendChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="glass-card p-4 h-100">
                <h5 class="text-white fw-bold mb-4">
                    <i class="fa fa-chart-pie text-success me-2"></i>Por Estación
                </h5>
                <div style="height: 350px;">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Productos y Métodos de Pago -->
    <div class="row g-4 mb-5">
        <div class="col-lg-7">
            <div class="glass-card p-0">
                <div class="p-4 border-bottom border-secondary border-opacity-25">
                    <h5 class="text-white fw-bold mb-0">
                        <i class="fa fa-trophy text-warning me-2"></i>Top 10 Productos Más Vendidos
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-glass mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Rank</th>
                                <th>Producto</th>
                                <th>Estación</th>
                                <th class="text-end">Cant.</th>
                                <th class="text-end pe-4">Ingresos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rank = 1;
                            foreach ($topProducts as $prod): ?>
                                <tr>
                                    <td class="ps-4 text-center">
                                        <span class="badge border border-info border-opacity-25 text-info rounded-circle"
                                            style="width: 25px; height: 25px; display: inline-flex; align-items: center; justify-content: center;">
                                            <?= $rank ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold text-white"><?= htmlspecialchars($prod['name']) ?></td>
                                    <td><span
                                            class="badge badge-soft opacity-75"><?= ucfirst($prod['kitchen_station'] ?? '') ?></span>
                                    </td>
                                    <td class="text-end"><?= number_format($prod['qty_sold']) ?></td>
                                    <td class="text-end text-success fw-bold pe-4">
                                        $<?= number_format($prod['revenue_usd'], 2) ?></td>
                                </tr>
                                <?php $rank++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="glass-card p-4">
                <h5 class="text-white fw-bold mb-4">
                    <i class="fa fa-clock text-danger me-2"></i>Análisis Horario
                </h5>
                <div style="height: 350px;">
                    <canvas id="hourlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Configuración global de Chart.js Premium
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#94a3b8';
    Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.05)';

    // 1. Gráfica de Distribución Financiera (Doughnut)
    new Chart(document.getElementById('financeChart'), {
        type: 'doughnut',
        data: {
            labels: ['Ganancia Neta', 'Costo Insumos', 'Inversión Stock', 'Nómina Admin', 'Gastos Op'],
            datasets: [{
                data: [
                    <?= max(0, $utilidadNeta) ?>,
                    <?= $totalCOGS ?>,
                    <?= $inversionInventario ?>,
                    <?= $gastosNominaAdmin ?>,
                    <?= $gastosOperativosGenerales ?>
                ],
                backgroundColor: [
                    '#10b981', // Ganancia
                    '#f59e0b', // COGS
                    '#0ea5e9', // Inventario
                    '#8b5cf6', // Nomina
                    '#ef4444'  // Gastos
                ],
                borderWidth: 0,
                hoverOffset: 20
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '75%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#f8fafc', padding: 20, font: { size: 11, weight: '500' } }
                }
            }
        }
    });

    // 2. Tendencia Diaria (Line)
    new Chart(document.getElementById('dailyTrendChart'), {
        type: 'line',
        data: {
            labels: [<?php foreach ($dailyTrend as $d)
                echo "'" . date('d/m', strtotime($d['date'])) . "',"; ?>],
            datasets: [{
                label: 'Ventas ($)',
                data: [<?php foreach ($dailyTrend as $d)
                    echo $d['daily_revenue'] . ','; ?>],
                borderColor: '#0ea5e9',
                backgroundColor: 'rgba(14, 165, 233, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#0ea5e9',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#64748b', font: { size: 10 } }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#64748b', font: { size: 10 } }
                }
            }
        }
    });

    // 3. Ventas por Categoría (Pie)
    new Chart(document.getElementById('categoryChart'), {
        type: 'pie',
        data: {
            labels: [<?php foreach ($categoryData as $c)
                echo "'" . (strtoupper($c['station_name']) === 'OTROS' ? 'Otros' : ucfirst($c['station_name'])) . "',"; ?>],
            datasets: [{
                data: [<?php foreach ($categoryData as $c)
                    echo $c['revenue'] . ','; ?>],
                backgroundColor: ['#ef4444', '#0ea5e9', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4'],
                borderWidth: 2,
                borderColor: '#1e293b'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#f8fafc', font: { size: 10 } }
                }
            }
        }
    });

    // 4. Análisis Horario (Bar)
    new Chart(document.getElementById('hourlyChart'), {
        type: 'bar',
        data: {
            labels: [<?php foreach ($hourlyData as $h)
                echo "'" . str_pad($h['hour'], 2, '0', STR_PAD_LEFT) . ":00',"; ?>],
            datasets: [{
                label: 'Órdenes',
                data: [<?php foreach ($hourlyData as $h)
                    echo $h['order_count'] . ','; ?>],
                backgroundColor: '#ef4444',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255, 255, 255, 0.05)' }
                },
                x: { grid: { display: false } }
            }
        }
    });
</script>

<?php require_once '../templates/footer.php'; ?>