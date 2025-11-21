<?php
session_start();
// contacto.php - Página de contacto
require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

  <div class="container-fluid">
    <div class="row h-100 align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="col-12 col-sm-8 col-md-6 col-lg-10 col-xl-6">
            <div class="bg-secondary rounded p-4 p-sm-5 my-4 mx-3">
<main>
    <section>
        <h2>Contacto</h2>
        <p>Si tienes alguna pregunta, no dudes en ponerte en contacto con nosotros. Estaremos encantados de ayudarte.</p>

        <form action="contacto.php" method="POST">
            <div class="form-group">
                <label for="name">Nombre</label>
                <input type="text" id="name" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="message">Mensaje</label>
                <textarea id="message" name="message" class="form-control" rows="4" required></textarea>
            </div>
            <div  align="center" class="align-items-center justify-content-center ">
              <br>
                                        <button type="submit" class="btn btn-primary ">Enviar Mensaje</button>
            </div>
        </form>
    </section>
</main>

</div>
</div>
</div>
</div>
<?php
require_once '../templates/footer.php';
?>
