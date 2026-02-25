<?php
require_once '../funciones/conexion.php';
require_once '../funciones/Config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

require_once '../templates/header.php';
?>

<div class="container-fluid pt-4 px-4">
    <div class="row g-4">
        <div class="col-12">
            <div class="bg-secondary rounded h-100 p-4">
                <h6 class="mb-4">Planillas Administrativas (Soporte Físico)</h6>
                <p class="text-muted">Seleccione una planilla para previsualizar e imprimir. Estas planillas sirven como respaldo físico para auditar el sistema.</p>
                
                <div class="row g-4">
                    <!-- Planilla 1: Recepción -->
                    <div class="col-md-4">
                        <div class="card bg-dark border-primary h-100">
                            <div class="card-body text-center">
                                <i class="fa fa-truck fa-3x text-primary mb-3"></i>
                                <h5>Recepción de Mercancía</h5>
                                <p class="small">Control de entrada de proveedores.</p>
                                <a href="imprimir_planilla.php?tipo=recepcion" target="_blank" class="btn btn-primary w-100">
                                    <i class="fa fa-print me-2"></i>Generar PDF
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Planilla 2: Producción -->
                    <div class="col-md-4">
                        <div class="card bg-dark border-success h-100">
                            <div class="card-body text-center">
                                <i class="fa fa-utensils fa-3x text-success mb-3"></i>
                                <h5>Control de Producción</h5>
                                <p class="small">Registro de transformaciones y lotes.</p>
                                <a href="imprimir_planilla.php?tipo=produccion" target="_blank" class="btn btn-success w-100">
                                    <i class="fa fa-print me-2"></i>Generar PDF
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Planilla 3: Toma Física -->
                    <div class="col-md-4">
                        <div class="card bg-dark border-info h-100">
                            <div class="card-body text-center">
                                <i class="fa fa-boxes fa-3x text-info mb-3"></i>
                                <h5>Inventario Físico</h5>
                                <p class="small">Hoja de conteo para auditoría semanal.</p>
                                <a href="imprimir_planilla.php?tipo=inventario" target="_blank" class="btn btn-info w-100">
                                    <i class="fa fa-print me-2"></i>Generar PDF
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Planilla 4: Mermas -->
                    <div class="col-md-4">
                        <div class="card bg-dark border-danger h-100">
                            <div class="card-body text-center">
                                <i class="fa fa-trash-alt fa-3x text-danger mb-3"></i>
                                <h5>Mermas y Averías</h5>
                                <p class="small">Reporte de daños y vencimientos.</p>
                                <a href="imprimir_planilla.php?tipo=mermas" target="_blank" class="btn btn-danger w-100">
                                    <i class="fa fa-print me-2"></i>Generar PDF
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Planilla 5: Arqueo -->
                    <div class="col-md-4">
                        <div class="card bg-dark border-warning h-100">
                            <div class="card-body text-center">
                                <i class="fa fa-cash-register fa-3x text-warning mb-3"></i>
                                <h5>Arqueo de Caja Diario</h5>
                                <p class="small">Soporte físico para cierre de turno.</p>
                                <a href="imprimir_planilla.php?tipo=arqueo" target="_blank" class="btn btn-warning w-100 text-dark">
                                    <i class="fa fa-print me-2"></i>Generar PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>
