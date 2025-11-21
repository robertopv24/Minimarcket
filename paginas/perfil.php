<?php
session_start();

// perfil.php - Página de perfil del usuario
require_once '../templates/autoload.php';
require_once '../templates/header.php';
require_once '../templates/menu.php';






?>


<div class="container-fluid">
  <div class="row h-100 align-items-center justify-content-center" style="min-height: 100vh;">
      <div class="col-12 col-sm-1 col-md-1 col-lg-10 col-xl-6">
          <div class="bg-secondary rounded p-4 p-sm-5 my-4 mx-3">

<main>
    <section>
        <h2>Perfil de Usuario</h2>
        <p><strong>Nombre:</strong> <?= $user; ?></p>
        <p><strong>Email:</strong> <?= $email; ?></p>
        <p><strong>Rol:</strong> <?= $user_role; ?></p>

        <h3>Actualizar Información</h3>
        <form action="actualizar_perfil.php" method="POST">
            <div class="form-group">
                <label for="name">Nombre Completo</label>
                <input type="text" id="name" name="name" class="form-control" value="<?= $user; ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" class="form-control" value="<?= $email; ?>" required>
            </div>

            <div  align="center" class="align-items-center justify-content-center ">
              <br>
                                        <button type="submit" class="btn btn-primary ">Actualizar</button>
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
