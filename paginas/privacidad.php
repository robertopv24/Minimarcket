<?php
session_start();
require_once '../templates/header.php';
require_once '../templates/menu.php';
?>


<div class="container-fluid">
  <div class="row h-100 align-items-center justify-content-center" style="min-height: 100vh;">
      <div class="col-12 col-sm-8 col-md-6 col-lg-10 col-xl-12">
          <div class="bg-secondary rounded p-4 p-sm-5 my-4 mx-3">


<main class="container">
    <h1>Política de Privacidad</h1>
    <p>En <strong><?= SITE_NAME ?></strong>, la privacidad de nuestros usuarios es una prioridad. A continuación, explicamos cómo recopilamos, usamos y protegemos tu información.</p>

    <h2>1. Información que recopilamos</h2>
    <p>Podemos recopilar información personal como nombre, dirección de correo electrónico, número de teléfono y datos de pago cuando te registras en nuestro sitio o realizas una compra.</p>

    <h2>2. Uso de la información</h2>
    <p>Usamos tu información para:</p>
    <ul>
        <li>Procesar tus pedidos y transacciones.</li>
        <li>Brindar soporte al cliente.</li>
        <li>Mejorar nuestros servicios y personalizar tu experiencia.</li>
    </ul>

    <h2>3. Protección de datos</h2>
    <p>Implementamos medidas de seguridad para proteger tu información personal contra accesos no autorizados, pérdidas o modificaciones.</p>

    <h2>4. Cookies</h2>
    <p>Utilizamos cookies para mejorar la experiencia del usuario. Puedes gestionar las preferencias de cookies desde la configuración de tu navegador.</p>

    <h2>5. Compartir información</h2>
    <p>No compartimos tu información personal con terceros, excepto cuando sea necesario para cumplir con la ley o mejorar nuestros servicios.</p>

    <h2>6. Cambios en la política</h2>
    <p>Nos reservamos el derecho de modificar esta política de privacidad en cualquier momento. Te recomendamos revisarla periódicamente.</p>

    <h2>7. Contacto</h2>
    <p>Si tienes dudas sobre nuestra política de privacidad, contáctanos en <a href="contacto.php">nuestra página de contacto</a>.</p>

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
