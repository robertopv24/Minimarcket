<?php
// session_start();
// 1. Usamos el Autoload. Esto carga la DB automáticamente en la variable $db
require_once '../templates/autoload.php';
require_once '../templates/header.php';
require_once '../templates/menu.php';

// Ya no necesitamos '../clases/conexion.php'

// 2. Verificamos la conexión usando la variable $db que ya existe gracias al autoload
// Si $db es un objeto PDO, la conexión es exitosa.
$dbStatus = ($db instanceof PDO) ? "Operativo" : "Error";

// Simulación de estados de los servicios
$servicios = [
    "Base de Datos" => $dbStatus,
    "Servidor Web" => "Operativo", // Si ves esto, PHP está corriendo
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

                <table class="table table-bordered text-white">
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
            <br><br><br>
          </div>
      </div>
  </div>
</div>

<?php
require_once '../templates/footer.php';
?>
