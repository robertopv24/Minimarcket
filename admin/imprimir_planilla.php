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

switch ($tipo) {
    case 'recepcion':
        $titulo = "Planilla de Recepción de Mercancía";
        $columnas = ["Producto / Insumo", "Cant. Recibida", "Costo Unit. ($)", "Fecha Venc.", "Firma Recibido"];
        $instrucciones = "Anote cada item recibido del proveedor. Verifique que el estado del empaque sea óptimo.";
        break;
    case 'produccion':
        $titulo = "Planilla de Control de Producción";
        $columnas = ["Producto Preparado", "Cant. Producida", "Lote / Hora", "Merma Proc.", "Responsable"];
        $instrucciones = "Registre la transformación de materia prima en productos terminados o semielaborados.";
        break;
    case 'inventario':
        $titulo = "Planilla de Toma Física de Inventario";
        $columnas = ["Código / Producto", "Ubicación", "Stock Sistema", "Conteo Real", "Diferencia"];
        $instrucciones = "Realice el conteo a ciegas (sin mirar el sistema primero) para mayor precisión.";
        break;
    case 'mermas':
        $titulo = "Planilla de Mermas y Averías";
        $columnas = ["Producto", "Cantidad", "Motivo (Daño/Vencido)", "Destino Final", "Autorización"];
        $instrucciones = "Reporte toda pérdida de stock que no sea por venta directa.";
        break;
    case 'arqueo':
        $titulo = "Planilla de Arqueo de Caja Diario";
        $columnas = ["Denominación", "Cant. Billetes", "Subtotal (Cash)", "Ventas POS", "Observaciones"];
        $instrucciones = "Cierre de turno. Debe coincidir el efectivo físico con el reporte de cierre del sistema.";
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
        @page { size: portrait; margin: 1cm; }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="background: #333; color: white; padding: 10px; display: flex; justify-content: space-between; align-items: center;">
        <span>Vista Previa de Planilla</span>
        <button onclick="window.print()" style="padding: 5px 15px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 4px;">Imprimir / Guardar PDF</button>
    </div>

    <div class="planilla-preview">
        <div class="header-info">
            <div>
                <h1 style="color: #000; margin: 0;"><?php echo strtoupper($GLOBALS['site_name']); ?></h1>
                <p style="margin: 5px 0;">Soporte de Gestión Integral</p>
            </div>
            <div style="text-align: right;">
                <h2 style="margin: 0;"><?php echo $titulo; ?></h2>
                <p style="margin: 5px 0;">Fecha: <?php echo date('d/m/Y H:i'); ?></p>
            </div>
        </div>

        <p><strong>Instrucciones:</strong> <?php echo $instrucciones; ?></p>

        <table>
            <thead>
                <tr>
                    <?php foreach ($columnas as $col): ?>
                        <th><?php echo $col; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php for ($i = 0; $i < 18; $i++): ?>
                <tr>
                    <?php foreach ($columnas as $col): ?>
                        <td style="height: 25px;"></td>
                    <?php endforeach; ?>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <div style="margin-top: 50px; display: flex; justify-content: space-around;">
            <div style="border-top: 1px solid #000; width: 200px; text-align: center; padding-top: 5px;">
                Firma Responsable
            </div>
            <div style="border-top: 1px solid #000; width: 200px; text-align: center; padding-top: 5px;">
                Firma Auditoría
            </div>
        </div>
        
        <div style="margin-top: 30px; font-size: 10px; text-align: center; color: #666;">
            Generado por Minimarket POS System v2.0 - Copia de Soporte Físico
        </div>
    </div>
</body>
</html>
