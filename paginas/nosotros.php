<?php
// session_start();
// nosotros.php - Página sobre nosotros
require_once '../templates/header.php';
require_once '../templates/menu.php';
?>


<div class="container-fluid">
  <div class="row h-100 align-items-center justify-content-center" style="min-height: 100vh;">
      <div class="col-12 col-sm-1 col-md-1 col-lg-10 col-xl-6">
          <div class="bg-secondary rounded p-4 p-sm-5 my-4 mx-3">


<main>
    <section>
        <h2>Sobre Nosotros</h2>
        <p>En <?= SITE_NAME ?>, nuestra misión es ofrecer los mejores productos a nuestros clientes, brindando una experiencia de compra excepcional y garantizando calidad y servicio.</p>
        <p>Contamos con un equipo comprometido con la satisfacción de nuestros clientes y la innovación constante en el mercado.</p>
    </section>
</main>


</div>
</div>
</div>
</div>


<?php
require_once '../templates/footer.php';
?>
