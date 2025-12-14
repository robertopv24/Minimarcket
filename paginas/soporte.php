<?php
// session_start();
require_once '../templates/header.php';
require_once '../templates/menu.php';
?>


<div class="container-fluid">
  <div class="row h-100 align-items-center justify-content-center" style="min-height: 100vh;">
      <div class="col-12 col-sm-8 col-md-6 col-lg-10 col-xl-12">
          <div class="bg-secondary rounded p-4 p-sm-5 my-4 mx-3">


<main class="container">
    <h1>Centro de Soporte</h1>
    <p>Si tienes preguntas o necesitas ayuda, estamos aquí para asistirte.</p>

    <h2>1. Preguntas Frecuentes (FAQ)</h2>
    <p>Aquí encontrarás respuestas a las preguntas más comunes.</p>
    <ul>
        <li><strong>¿Cómo puedo registrarme?</strong> - Dirígete a la página de <a href="register.php">registro</a> y completa el formulario.</li>
        <li><strong>Olvidé mi contraseña, ¿qué hago?</strong> - Puedes restablecer tu contraseña en la página de <a href="password_reset.php">recuperación</a>.</li>
        <li><strong>¿Cómo contacto al soporte?</strong> - Puedes enviarnos un mensaje en la sección de contacto.</li>
    </ul>

    <h2>2. Contactar Soporte</h2>
    <p>Si no encontraste la respuesta a tu problema, contáctanos a través de:</p>
    <ul>
        <li><strong>Correo Electrónico:</strong> <a href="mailto:soporte@miplataforma.com">soporte@miplataforma.com</a></li>
        <li><strong>Teléfono:</strong> +123 456 7890</li>
        <li><strong>Formulario de contacto:</strong> <a href="contacto.php">Haz clic aquí</a></li>
    </ul>

    <h2>3. Horario de Atención</h2>
    <p>Estamos disponibles para responder tus consultas en el siguiente horario:</p>
    <ul>
        <li>Lunes a Viernes: 9:00 AM - 6:00 PM</li>
        <li>Sábados: 10:00 AM - 2:00 PM</li>
        <li>Domingos y Festivos: Cerrado</li>
    </ul>

    <h2>4. Estado de Servicio</h2>
    <p>Para verificar el estado de nuestros servicios, puedes visitar nuestra <a href="status.php">página de estado</a>.</p>

    <br>
    <p><strong>Última actualización:</strong> <?= date("d/m/Y") ?></p>
</main>

</div>
</div>
</div>
</div>


<?php
require_once '../templates/footer.php';
?>
