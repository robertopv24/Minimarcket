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
    <h1>Términos y Condiciones</h1>
    <p>Bienvenido a <strong><?= SITE_NAME ?></strong>. Antes de utilizar nuestro sitio web, te pedimos que leas y aceptes nuestros términos y condiciones.</p>

    <h2>1. Aceptación de los términos</h2>
    <p>Al acceder y utilizar este sitio web, aceptas cumplir con estos términos y condiciones. Si no estás de acuerdo con alguna parte de estos términos, no deberías usar nuestro sitio.</p>

    <h2>2. Uso del sitio</h2>
    <p>Este sitio es para uso personal y no comercial. No puedes copiar, distribuir o modificar ningún contenido sin nuestro permiso.</p>

    <h2>3. Privacidad</h2>
    <p>Respetamos tu privacidad. Consulta nuestra <a href="privacidad.php">Política de Privacidad</a> para más información sobre cómo manejamos tus datos.</p>

    <h2>4. Modificaciones</h2>
    <p>Nos reservamos el derecho de modificar estos términos en cualquier momento. Te recomendamos revisar esta página periódicamente.</p>

    <h2>5. Contacto</h2>
    <p>Si tienes preguntas sobre estos términos, puedes contactarnos en <a href="contacto.php">nuestra página de contacto</a>.</p>

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
