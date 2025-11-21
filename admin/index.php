<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


session_start();

if (!isset($_SESSION['user_name']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../paginas/login.php"); // Redirige a la página de login si no es admin
    exit();
}


require_once '../templates/autoload.php';



require_once '../templates/header.php';
require_once '../templates/menu.php';

// Obtener la conexión a la base de datos


// Consultar las ventas totales (total de ventas en USD)
$totalVentasTotales = $orderManager->getTotalVentas();

// Consultar total de pedidos completados
$totalPedidos = $orderManager->countOrdersByStatus('delivered');

// Consultar total de productos vendidos
$totalProductosVendidos = $orderManager->getTotalProductosVendidos();

// Consultar últimos pedidos
$ultimosPedidos = $orderManager->getUltimosPedidos();

// Consultar número de usuarios registrados
$totalUsuarios = $userManager->getTotalUsuarios();

// Consultar el total de productos
$totalProductos = $productManager->getTotalProduct();

// Consultar productos con bajo stock
$productosBajoStock = $productManager->getLowStockProducts();

// Consultar ventas totales del mes
$totalVentasMes = $orderManager->getTotalVentasMes();

// Consultar ventas totales de la semana
$totalVentasSemana = $orderManager->getTotalVentasSemana();

// Consultar ventas totales del día
$totalVentasDia = $orderManager->getTotalVentasDia();

$newExchangeRate = $GLOBALS['exchange_rate'];


// Procesar actualización de tasa de cambio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_exchange_rate'])) {
    $newExchangeRate = isset($_POST['new_exchange_rate']) ? floatval($_POST['new_exchange_rate']) : 0;

    if ($newExchangeRate <= 0) {
        $error_message = "Error: La tasa debe ser mayor a 0.";
    } else {
        // 1. Actualizar configuración global
        if ($config->update('exchange_rate', $newExchangeRate)) {

            // 2. [OPTIMIZACIÓN FASE 1] Ejecutar actualización masiva
            // Ya no usamos el foreach. Llamamos a la función SQL directa.
            if ($productManager->updateAllPricesBasedOnRate($newExchangeRate)) {
                $success_message = "Tasa actualizada y precios recalculados correctamente.";

                // Actualizar variable global para la vista actual
                $GLOBALS['exchange_rate'] = $newExchangeRate;
                $newExchangeRate = $GLOBALS['exchange_rate'];
            } else {
                $error_message = "La tasa se guardó, pero hubo un error recalculando precios.";
            }

        } else {
            $error_message = "Error al guardar la configuración.";
        }
    }
}

?>


<?php if (isset($success_message)): ?>
    <div class="alert alert-success align-items-center justify-content-between text-center"><?php echo $success_message; ?></div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger align-items-center justify-content-between text-center"><?php echo $error_message; ?></div>
<?php endif; ?>


<div class="container-fluid mt-4">
    <div class="row">
      <!-- Total Sale -->
      <div class="col-sm-6 col-xl-3 mb-4">
          <div class="bg-secondary rounded d-flex align-items-center justify-content-between p-4">
              <i class="fa fa-chart-bar fa-3x text-primary"></i>
              <div class="ms-3">
                  <p class="mb-2">Total Sale</p>
                  <h6 class="mb-0">$<?php echo number_format($totalVentasTotales, 2); ?></h6>
              </div>
          </div>
      </div>

      <!-- Total Pedidos Completados -->
      <div class="col-sm-6 col-xl-3 mb-4">
          <div class="bg-secondary rounded d-flex align-items-center justify-content-between p-4">
              <i class="fa fa-check-circle fa-3x text-white"></i>
              <div class="ms-3">
                  <p class="mb-2">Total Orders</p>
                  <h6 class="mb-0"><?php echo $totalPedidos; ?></h6>
              </div>
          </div>
      </div>

      <!-- Total Productos Vendidos -->
      <div class="col-sm-6 col-xl-3 mb-4">
          <div class="bg-secondary rounded d-flex align-items-center justify-content-between p-4">
              <i class="fa fa-cogs fa-3x text-white"></i>
              <div class="ms-3">
                  <p class="mb-2">Total Products Sold</p>
                  <h6 class="mb-0"><?php echo $totalProductosVendidos; ?></h6>
              </div>
          </div>
      </div>

      <!-- Total Productos -->
      <div class="col-sm-6 col-xl-3 mb-4">
          <div class="bg-secondary rounded d-flex align-items-center justify-content-between p-4">
              <i class="fa fa-cogs fa-3x text-white"></i>
              <div class="ms-3">
                  <p class="mb-2">Total Products</p>
                  <h6 class="mb-0"><?php echo $totalProductos; ?></h6>
              </div>
          </div>
      </div>

      <!-- Total Usuarios Registrados -->
      <div class="col-sm-6 col-xl-3 mb-4">
          <div class="bg-secondary rounded d-flex align-items-center justify-content-between p-4">
              <i class="fa fa-users fa-3x text-white"></i>
              <div class="ms-3">
                  <p class="mb-2">Total Users</p>
                  <h6 class="mb-0"><?php echo $totalUsuarios; ?></h6>
              </div>
          </div>
      </div>

      <!-- Total Ventas del Mes -->
      <div class="col-sm-6 col-xl-3 mb-4">
          <div class="bg-secondary rounded d-flex align-items-center justify-content-between p-4">
              <i class="fa fa-calendar-alt fa-3x text-primary"></i>
              <div class="ms-3">
                  <p class="mb-2">Ventas del Mes</p>
                  <h6 class="mb-0">$<?php echo number_format($totalVentasMes, 2); ?></h6>
              </div>
          </div>
      </div>

      <!-- Total Ventas de la Semana -->
      <div class="col-sm-6 col-xl-3 mb-4">
          <div class="bg-secondary rounded d-flex align-items-center justify-content-between p-4">
              <i class="fa fa-calendar-week fa-3x text-info"></i>
              <div class="ms-3">
                  <p class="mb-2">Ventas de la Semana</p>
                  <h6 class="mb-0">$<?php echo number_format($totalVentasSemana, 2); ?></h6>
              </div>
          </div>
      </div>

      <!-- Total Ventas del Día -->
      <div class="col-sm-6 col-xl-3 mb-4">
          <div class="bg-secondary rounded d-flex align-items-center justify-content-between p-4">
              <i class="fa fa-calendar-day fa-3x text-warning"></i>
              <div class="ms-3">
                  <p class="mb-2">Ventas del Día</p>
                  <h6 class="mb-0">$<?php echo number_format($totalVentasDia, 2); ?></h6>
              </div>
          </div>
      </div>

      <!-- Otros elementos del dashboard... -->

      <div class="col-sm-6 col-xl-4 mb-4">
          <div class="bg-secondary rounded d-flex align-items-center justify-content-between p-4">
              <i class="fa fa-cogs fa-3x text-white"></i>



          <div class="ms-3">
          <h3>Actualizar Tasa de Cambio</h3>

          <form method="POST">
              <input type="hidden" name="update_exchange_rate" value="1">
              <div class="mb-3">
                  <label for="new_exchange_rate" class="form-label">Nueva Tasa de Cambio (USD a VES):</label>
                  <input type="number" name="new_exchange_rate" id="new_exchange_rate" step="0.01" class="form-control" required value="<?php echo htmlspecialchars($newExchangeRate); ?>">
              </div>
              <button type="submit" class="btn btn-warning">Actualizar Tasa y Recalcular Precios</button>
          </form>

        </div>
    </div>
</div>


          <!-- Últimos Pedidos -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning text-white">
                    <h5>Últimos Pedidos</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Pedido ID</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimosPedidos as $pedido): ?>
                                <tr>
                                    <td><?php echo $pedido['id']; ?></td>
                                    <td><?php echo date('d-m-Y H:i', strtotime($pedido['created_at'])); ?></td>
                                    <td><?php echo $pedido['name']; ?></td>
                                    <td>$<?php echo number_format($pedido['total_price'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Productos con Bajo Stock -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-dark">
                    <h5>Productos con Bajo Stock</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Producto ID</th>
                                <th>Nombre</th>
                                <th>Stock Actual</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productosBajoStock as $producto): ?>
                                <tr>
                                    <td><?php echo $producto['id']; ?></td>
                                    <td><?php echo $producto['name']; ?></td>
                                    <td><?php echo $producto['stock']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>
