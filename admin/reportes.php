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
// 1. MÉTRICAS FINANCIERAS (CAJA Y FLUJO)
// =========================================================

// A. INGRESOS BRUTOS (Ventas Totales + Entradas Manuales)
$sqlIncome = "SELECT SUM(amount_usd_ref) as total FROM transactions
              WHERE type = 'income' AND created_at BETWEEN ? AND ?";
$stmt = $db->prepare($sqlIncome);
$stmt->execute([$startSql, $endSql]);
$ingresosBrutos = $stmt->fetchColumn() ?: 0;

// B. VUELTOS (Salidas de dinero por cambio a clientes)
$sqlChange = "SELECT SUM(amount_usd_ref) as total FROM transactions
              WHERE type = 'expense' AND reference_type = 'order' AND created_at BETWEEN ? AND ?";
$stmt = $db->prepare($sqlChange);
$stmt->execute([$startSql, $endSql]);
$vueltos = $stmt->fetchColumn() ?: 0;

// C. VENTAS NETAS REALES (Lo que realmente entró por venta de comida)
$ventasNetas = $ingresosBrutos - $vueltos;

// D. GASTOS OPERATIVOS (Compras a Proveedores / Retiros de Caja)
$sqlExpenses = "SELECT SUM(amount_usd_ref) as total FROM transactions
                WHERE type = 'expense' AND reference_type != 'order' AND created_at BETWEEN ? AND ?";
$stmt = $db->prepare($sqlExpenses);
$stmt->execute([$startSql, $endSql]);
$gastosOperativos = $stmt->fetchColumn() ?: 0;

// =========================================================
// 2. COSTO DE VENTA (COGS) - LO QUE TE COSTÓ LA COMIDA
// =========================================================
// Esta es la métrica más importante. Calcula cuánto gastaste en materia prima
// para generar esas ventas.

// Consultamos todos los ítems vendidos en el rango
$sqlItems = "SELECT oi.product_id, SUM(oi.quantity) as qty_sold
             FROM order_items oi
             JOIN orders o ON oi.order_id = o.id
             WHERE o.status IN ('paid', 'delivered')
             AND o.created_at BETWEEN ? AND ?
             GROUP BY oi.product_id";
$stmt = $db->prepare($sqlItems);
$stmt->execute([$startSql, $endSql]);
$soldItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

$costoMateriaPrima = 0;

foreach ($soldItems as $item) {
    $pid = $item['product_id'];
    $qty = $item['qty_sold'];

    // Usamos la lógica del ProductManager para saber el costo de la receta
    // Nota: Esto usa el costo ACTUAL de los ingredientes.
    $components = $productManager->getProductComponents($pid);
    $unitCost = 0;

    foreach ($components as $comp) {
        // Sumar costo de materia prima
        $unitCost += ($comp['quantity'] * $comp['item_cost']);
    }

    // Si es un producto simple (revendedor), usamos un costo estimado o 0 si no se definió
    if (empty($components)) {
        $prod = $productManager->getProductById($pid);
        // Si tienes un campo 'cost_price' en productos simples úsalo, si no, estimamos margen 30%
        // Para este ejemplo asumiremos que el sistema está bien configurado con recetas.
    }

    $costoMateriaPrima += ($unitCost * $qty);
}

// =========================================================
// 3. RESULTADOS FINALES
// =========================================================

// UTILIDAD BRUTA (Ventas - Costo Comida)
$utilidadBruta = $ventasNetas - $costoMateriaPrima;
$margenBruto = ($ventasNetas > 0) ? ($utilidadBruta / $ventasNetas) * 100 : 0;

// UTILIDAD NETA (Ventas - Costo Comida - Gastos Operativos)
$utilidadNeta = $utilidadBruta - $gastosOperativos;
$margenNeto = ($ventasNetas > 0) ? ($utilidadNeta / $ventasNetas) * 100 : 0;

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container-fluid mt-4 px-4">

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
                            <h6 class="text-uppercase mb-1 opacity-75">Costo de Insumos (COGS)</h6>
                            <h2 class="mb-0 fw-bold text-danger">$<?= number_format($costoMateriaPrima, 2) ?></h2>
                        </div>
                        <i class="fa fa-box-open fa-3x opacity-25"></i>
                    </div>
                    <div class="small mt-3">
                        Valor de ingredientes consumidos
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
                        Después de gastos operativos ($<?= number_format($gastosOperativos, 2) ?>)
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
                                usort($soldItems, function($a, $b) { return $b['qty_sold'] - $a['qty_sold']; });
                                $top5 = array_slice($soldItems, 0, 5);

                                foreach($top5 as $item):
                                    $p = $productManager->getProductById($item['product_id']);
                                    $totalRow = $item['qty_sold'] * $p['price_usd'];
                                ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($p['name']) ?></td>
                                    <td class="text-center"><span class="badge bg-secondary rounded-pill"><?= $item['qty_sold'] ?></span></td>
                                    <td class="text-end text-success fw-bold">$<?= number_format($totalRow, 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($top5)): ?>
                                    <tr><td colspan="3" class="text-center py-4">No hay datos en este periodo.</td></tr>
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
                                       WHERE t.type = 'income' AND t.created_at BETWEEN ? AND ?
                                       GROUP BY pm.name, pm.currency";
                        $stmtM = $db->prepare($sqlMethods);
                        $stmtM->execute([$startSql, $endSql]);
                        $methods = $stmtM->fetchAll(PDO::FETCH_ASSOC);

                        foreach($methods as $m):
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

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('financeChart').getContext('2d');
    const financeChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Ganancia Neta', 'Costo Insumos', 'Gastos Operativos'],
            datasets: [{
                data: [<?= $utilidadNeta ?>, <?= $costoMateriaPrima ?>, <?= $gastosOperativos ?>],
                backgroundColor: [
                    '#0dcaf0', // Info (Ganancia)
                    '#ffc107', // Warning (Insumos)
                    '#dc3545'  // Danger (Gastos)
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right' }
            }
        }
    });
</script>

<?php require_once '../templates/footer.php'; ?>
