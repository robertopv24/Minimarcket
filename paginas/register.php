<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// 1. Cargamos el "cerebro" del sistema (Autoload)
// Esto nos da acceso a $userManager, $db, $config, etc.
require_once '../templates/autoload.php';

$mensaje = '';

// 2. Procesamos el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $document_id = $_POST['document_id'] ?? '';
    $address = $_POST['address'] ?? '';

    // Usamos el UserManager central para crear el usuario
    // Esto asegura que la contraseña se encripte igual que en el Login
    $result = $userManager->createUser($name, $email, $password, $phone, $document_id, $address);

    if ($result === true) {
        $mensaje = '<div class="alert alert-success">¡Registro exitoso! <a href="login.php" class="alert-link">Inicia sesión aquí</a>.</div>';
    } else {
        // Si hay error (ej: email duplicado), lo mostramos
        $mensaje = '<div class="alert alert-danger">' . $result . '</div>';
    }
}

// 3. Cargamos la plantilla visual (Header y Menu)
require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container-fluid">
    <div class="row h-100 align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="col-12 col-sm-8 col-md-6 col-lg-5 col-xl-6">
            <div class="bg-secondary rounded p-4 p-sm-5 my-4 mx-3">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h3 class="text-primary"><i class="fa fa-user-plus me-2"></i>Registro</h3>
                </div>

                <?= $mensaje ?>

                <form method="post">
                    <div class="form-floating mb-3">
                        <input type="text" name="name" class="form-control" id="floatingText" placeholder="Nombre Completo" required>
                        <label for="floatingText">Nombre</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="email" name="email" class="form-control" id="floatingInput" placeholder="name@example.com" required>
                        <label for="floatingInput">Correo Electrónico</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="password" name="password" class="form-control" id="floatingPassword" placeholder="Contraseña" required>
                        <label for="floatingPassword">Contraseña</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="text" name="phone" class="form-control" id="floatingPhone" placeholder="Teléfono" required>
                        <label for="floatingPhone">Teléfono</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="text" name="document_id" class="form-control" id="floatingID" placeholder="Documento de Identidad" required>
                        <label for="floatingID">Documento de Identidad</label>
                    </div>
                    <div class="form-floating mb-3">
                        <textarea name="address" class="form-control" placeholder="Dirección" id="floatingAddress" style="height: 100px;" required></textarea>
                        <label for="floatingAddress">Dirección</label>
                    </div>

                    <button type="submit" class="btn btn-primary py-3 w-100 mb-4">Registrarse</button>
                    <p class="text-center mb-0">¿Ya tienes cuenta? <a href="login.php">Inicia Sesión</a></p>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>
