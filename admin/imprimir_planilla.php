<?php
require_once '../funciones/conexion.php';
require_once '../funciones/Config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die("Acceso denegado.");
}

$tipo = $_GET['tipo'] ?? 'inventario';
$titulo = "";
$columnas = [];
$instrucciones = "";
$numFilas = 18; // Default number of rows

switch ($tipo) {
    case 'recepcion':
        $titulo = "Planilla de Recepción de Mercancía";
        $columnas = ["Producto / Insumo", "Cant. Recibida", "Costo Unit. ($)", "Fecha Venc.", "Firma Recibido"];
        $instrucciones = "Anote cada item recibido del proveedor. Verifique que el estado del empaque sea óptimo.";
        $labelIdentificacion = "Proveedor / Nro. Factura:";
        break;
    case 'produccion':
        $titulo = "Planilla de Control de Producción";
        $columnas = ["Producto Preparado", "Cant. Producida", "Lote / Hora", "Merma Proc.", "Responsable"];
        $instrucciones = "Registre la transformación de materia prima en productos terminados o semielaborados.";
        $labelIdentificacion = "Lote / Producto Preparado:";
        break;
    case 'inventario':
        $titulo = "Planilla de Toma Física de Inventario";
        $columnas = ["Código / Producto", "Ubicación", "Stock Sistema", "Conteo Real", "Diferencia"];
        $instrucciones = "Realice el conteo a ciegas (sin mirar el sistema primero) para mayor precisión.";
        $labelIdentificacion = "Área / Sector de Conteo:";
        break;
    case 'mermas':
        $titulo = "Planilla de Mermas y Averías";
        $columnas = ["Producto", "Cantidad", "Motivo (Daño/Vencido)", "Destino Final", "Autorización"];
        $instrucciones = "Reporte toda pérdida de stock que no sea por venta directa.";
        $labelIdentificacion = "Responsable / Turno:";
        break;
    case 'arqueo':
        $titulo = "Planilla de Arqueo de Caja Diario";
        $columnas = ["Denominación", "Cant. Billetes", "Subtotal (Cash)", "Ventas POS", "Observaciones"];
        $instrucciones = "Cierre de turno. Debe coincidir el efectivo físico con el reporte de cierre del sistema.";
        $labelIdentificacion = "Caja Nro / Nombre Cajero:";
        break;
    case 'materias_primas':
        $titulo = "Levantamiento de Materias Primas e Insumos";
        $columnas = ["Insumo / Materia Prima", "Categoría", "Unidad (Kg/Lt/Und)", "Stock Mín.", "Stock Máx.", "Costo Ref."];
        $instrucciones = "Use esta planilla para listar todos los ingredientes y suministros necesarios para la operación antes de cargarlos al sistema.";
        $labelIdentificacion = "Área (Cocina/Bar/Depósito):";
        break;
    case 'manufactura':
        $titulo = "Planilla de Estandarización de Recetas (Prueba)";
        $columnas = ["Ingrediente / Insumo", "Cantidad Usada", "Unidad (Kg/Gr/Und)", "Peso Unitario (Kg)", "Observaciones"];
        $instrucciones = "Use esta planilla durante una prueba de producción. Pese todos los ingredientes antes de mezclarlos y luego anote el rendimiento final al pie de la tabla.";
        $labelIdentificacion = "Producto Final / Receta:";
        $numFilas = 12; // Reducido para que quepa el cuadro de rendimiento
        break;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?php echo $titulo; ?></title>
    <link href="../css/plantillas_print.css" rel="stylesheet">
    <style>
        @page {
            size: auto;
            margin: 10mm;
        }
    </style>
</head>

<body onload="window.print()">
    <!-- Actions Bar -->
    <div class="no-print"
        style="background: #111; color: #fff; padding: 12px 24px; display: flex; justify-content: space-between; align-items: center; font-family: 'Segoe UI', sans-serif; border-bottom: 2.5px solid #28a745; box-shadow: 0 4px 15px rgba(0,0,0,0.4);">
        <div style="display: flex; align-items: center; gap: 12px;">
            <i class="fa fa-file-text-o" style="color: #28a745; font-size: 1.25rem;"></i>
            <span style="font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase; font-size: 13px;">Generador
                de Planillas Físicas</span>
        </div>
        <button onclick="window.print()"
            style="padding: 10px 28px; cursor: pointer; background: #28a745; color: white; border: none; border-radius: 6px; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; transition: all 0.2s ease; box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);">
            <i class="fa fa-print" style="margin-right: 10px;"></i> Ejecutar Impresión
        </button>
    </div>

    <!-- Main Sheet Container -->
    <div class="planilla-preview">
        <div class="header-info"
            style="display: flex; justify-content: space-between; align-items: flex-end; padding-bottom: 12px; margin-bottom: 15px; border-bottom: 3px solid #000;">
            <div style="flex: 1.4;">
                <h1
                    style="color: #000; margin: 0; font-size: 28px; font-weight: 900; letter-spacing: -0.8px; line-height: 1;">
                    <?php echo strtoupper($GLOBALS['site_name'] ?? 'MINIMARKET PLUS'); ?>
                </h1>
                <p
                    style="margin: 4px 0 0 0; font-size: 11px; font-weight: 700; color: #444; text-transform: uppercase; letter-spacing: 0.6px; opacity: 0.8;">
                    Sistema de Gestión de Operaciones y Control Administrativo</p>
            </div>
            <div style="text-align: right; flex: 1;">
                <h2
                    style="margin: 0; font-size: 19px; font-weight: 800; color: #000; text-transform: uppercase; line-height: 1.1;">
                    <?php echo $titulo; ?>
                </h2>
                <div
                    style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 6px; font-size: 10.5px; font-weight: 700; color: #222;">
                    <span>Fecha: <?php echo date('d/m/Y'); ?></span>
                    <span>Hora: <?php echo date('H:i'); ?></span>
                </div>
            </div>
        </div>

        <!-- Instructions & Product Identification -->
        <div style="display: flex; gap: 15px; margin-bottom: 15px; align-items: stretch;">
            <div
                style="flex: 1.5; padding: 10px 15px; background: #fafafa; border: 1px solid #ddd; border-left: 6px solid #111; border-radius: 0 4px 4px 0;">
                <p style="margin: 0; font-size: 10.5px; color: #222; font-style: italic; line-height: 1.5;">
                    <strong
                        style="text-transform: uppercase; font-style: normal; color: #000; margin-right: 6px; font-weight: 800;">Instrucciones:</strong>
                    <?php echo $instrucciones; ?>
                </p>
            </div>

            <div
                style="flex: 1; padding: 10px 15px; border: 1.5px solid #000; background: #fff; display: flex; flex-direction: column; justify-content: center;">
                <label
                    style="font-size: 10px; font-weight: 900; color: #000; text-transform: uppercase; margin-bottom: 8px; display: block; border-bottom: 1px solid #000; padding-bottom: 3px;"><?php echo $labelIdentificacion; ?></label>
                <div style="border-bottom: 1px solid #000; height: 20px; width: 100%; margin-top: 5px;"></div>
            </div>
        </div>

        <!-- Data Table -->
        <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
            <thead>
                <tr>
                    <?php
                    $totalCols = count($columnas);
                    foreach ($columnas as $idx => $col):
                        $width = ($idx === 0) ? '45%' : (55 / ($totalCols - 1)) . '%';
                        ?>
                        <th
                            style="width: <?php echo $width; ?>; background: #ececec; border: 1.8px solid #000; padding: 8px 12px; font-size: 10.5px; font-weight: 800; text-align: left; text-transform: uppercase; color: #000;">
                            <?php echo $col; ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php for ($i = 0; $i < $numFilas; $i++): ?>
                    <tr>
                        <?php foreach ($columnas as $col): ?>
                            <td style="height: 22px; border: 1px solid #000; padding: 0;"></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <!-- Signatures and Footer Section -->
        <div style="margin-top: 25px; display: flex; justify-content: space-around; align-items: flex-start;">
            <div style="text-align: center; width: 230px;">
                <div style="border-top: 1.8px solid #000; margin-bottom: 8px;"></div>
                <span
                    style="font-size: 11px; font-weight: 800; color: #000; text-transform: uppercase; display: block; letter-spacing: 0.5px;">Firma
                    Responsable Operativo</span>
                <span
                    style="font-size: 9px; color: #555; font-weight: 600; text-transform: uppercase; margin-top: 2px; display: block;">Soporte
                    de Gestión Integral</span>
            </div>
            <div style="text-align: center; width: 230px;">
                <div style="border-top: 1.8px solid #000; margin-bottom: 8px;"></div>
                <span
                    style="font-size: 11px; font-weight: 800; color: #000; text-transform: uppercase; display: block; letter-spacing: 0.5px;">Validación
                    de Auditoría</span>
                <span
                    style="font-size: 9px; color: #555; font-weight: 600; text-transform: uppercase; margin-top: 2px; display: block;">Control
                    Interno y Cumplimiento</span>
            </div>
        </div>

        <?php if ($tipo === 'manufactura'): ?>
            <!-- Rendimiento Técnico (Solo para Manufactura) -->
            <div style="margin-top: 15px; padding: 12px; border: 2px solid #000; background: #fdfdfd; border-radius: 5px;">
                <h3
                    style="margin: 0 0 10px 0; font-size: 13px; font-weight: 800; text-transform: uppercase; border-bottom: 1px solid #ddd; padding-bottom: 5px;">
                    Datos de Rendimiento (Post-Proceso)</h3>
                <div style="display: flex; gap: 20px;">
                    <div style="flex: 1;">
                        <label style="font-size: 10px; font-weight: 700; display: block; margin-bottom: 5px;">PESO TOTAL
                            INSUMOS (Kg):</label>
                        <div style="border-bottom: 1.5px solid #000; height: 25px; width: 100%;"></div>
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 10px; font-weight: 700; display: block; margin-bottom: 5px;">PESO FINAL
                            OBTENIDO (Kg):</label>
                        <div style="border-bottom: 1.5px solid #000; height: 25px; width: 100%;"></div>
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 10px; font-weight: 700; display: block; margin-bottom: 10px;">UNIDADES
                            RESULTANTES (Und):</label>
                        <div style="border-bottom: 1.5px solid #000; height: 25px; width: 100%;"></div>
                    </div>
                </div>
                <p style="margin: 10px 0 0 0; font-size: 9px; color: #555; font-style: italic;">* Estos datos son vitales
                    para calcular el estándar de merma y normalizar la receta a 1 Kg o 1 Unidad en el sistema.</p>
            </div>
        <?php endif; ?>

        <div
            style="margin-top: 20px; padding-top: 10px; border-top: 1.2px dashed #ccc; display: flex; justify-content: space-between; align-items: center; color: #888; font-size: 8.5px; font-weight: 600; font-family: 'Segoe UI', monospace; letter-spacing: 0.2px;">
            <span>PROTOCOLO: <?php echo strtoupper(bin2hex(random_bytes(4))); ?></span>
            <span style="color: #666;">DOC ID: ADM-<?php echo strtoupper($tipo); ?>-V2.1</span>
            <span style="text-transform: uppercase;">Copia de Respaldo Físico Autorizada</span>
        </div>
    </div>
</body>

</html>