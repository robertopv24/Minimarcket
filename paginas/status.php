<?php
session_start();
require_once '../templates/header.php';
require_once '../templates/menu.php';
require_once '../clases/conexion.php';

$conexion = conectar();

// Simulación de estados de los servicios
$servicios = [
    "Base de Datos" => $conexion ? "Operativo" : "Error",
    "Servidor Web" => "Operativo",
    "Correo Electrónico" => "En Mantenimiento",
    "API de Pagos" => "En Mantenimiento",
    "Notificaciones Push" => "En Mantenimiento"
];

// Colores de estado
$colores = [
    "Operativo" => "green",
    "En Mantenimiento" => "orange",
    "Error" => "red"
];
?>


<div class="container-fluid">
  <div class="row h-100 align-items-center justify-content-center" style="min-height: 40vh;">
      <div class="col-12 col-sm-8 col-md-6 col-lg-10 col-xl-12">
          <div class="bg-secondary rounded p-4 p-sm-5 my-4 mx-3">


<main class="container">
    <h1>Estado de los Servicios</h1>
    <p>Aquí puedes verificar el estado actual de nuestros servicios en la plataforma.</p>

    <table border="1">
        <thead>
            <tr>
                <th>Servicio</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($servicios as $servicio => $estado) : ?>
                <tr>
                    <td><?= $servicio ?></td>
                    <td style="color: <?= $colores[$estado] ?>; font-weight: bold;"><?= $estado ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <br>
    <p><strong>Última actualización:</strong> <?= date("d/m/Y H:i:s") ?></p>
</main>
  <br>  <br>  <br>
</div>
</div>
</div>
</div>


<?php
require_once '../templates/footer.php';
?>
