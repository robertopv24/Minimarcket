<?php
session_start();
require_once '../templates/header.php';
require_once '../templates/menu.php';
require_once '../clases/conexion.php';

$mensaje = "";

// Verificar si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conexion = conectar();
    $email = trim($_POST['email']);

    // Verificar si el correo existe en la base de datos
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        // Generar token de restablecimiento
        $token = bin2hex(random_bytes(50));
        $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Guardar el token en la base de datos
        $sql = "UPDATE users SET reset_token = ?, token_expiry = ? WHERE email = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$token, $expira, $email]);

        // Enviar correo con el enlace de restablecimiento
        $enlace = "http://localhost/paginas/password_reset_confirm.php?token=$token";
        mail($email, "Restablecimiento de contraseña", "Haz clic en el siguiente enlace para restablecer tu contraseña: $enlace");

        $mensaje = "Se ha enviado un enlace de restablecimiento a tu correo.";
    } else {
        $mensaje = "No encontramos una cuenta con ese correo.";
    }
}
?>


<div class="container-fluid">
  <div class="row  align-items-center justify-content-center" style="min-height: 40vh;">
      <div class="col-12 col-sm-8 col-md-6 col-lg-10 col-xl-12">
          <div class="bg-secondary rounded p-4 p-sm-5 my-4 mx-3">

<br><br><br><br>
<main class="container">
    <h1>Restablecer Contraseña</h1>
    <p>Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.</p>

    <?php if (!empty($mensaje)) : ?>
        <p class="alert alert-info"><?= $mensaje ?></p>
    <?php endif; ?>

    <form action="password_reset.php" method="POST">
        <label for="email">Correo Electrónico:</label>
        <input type="email" name="email" required>
        <div  align="center" class="align-items-center justify-content-center ">
          <br>
                                    <button type="submit" class="btn btn-primary ">Enviar Enlace</button>
        </div>

    </form>
</main>
<br><br><br><br><br><br><br>
</div>
</div>
</div>
</div>


<?php
require_once '../templates/footer.php';
?>
