<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../templates/autoload.php';

// Verificar si el formulario ha sido enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Intentar iniciar sesión
    $result = $userManager->login($email, $password);

    if ($result === true) {
        header("Location: ../index.php");
        exit();
    } else {
        $_SESSION['error'] = $result; // Guardar mensaje de error en sesión
    }
}




require_once '../templates/header.php';

require_once '../templates/menu.php';




?>






            <!-- Sign Up Start -->
            <div class="container-fluid">
                <div class="row h-100 align-items-center justify-content-center" style="min-height: 100vh;">
                    <div class="col-12 col-sm-8 col-md-6 col-lg-5 col-xl-4">
                        <div class="bg-secondary rounded p-4 p-sm-10 my-4 mx-3">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <a href="index.html" class="">
                                    <h3 class="text-primary"><i class="fa fa-user-edit me-2"></i></h3>
                                </a>
                                <h3>Iniciar sesión</h3>
                            </div>
                            <form method="post">
                            <div class="form-floating mb-3">
                                <input type="email" name="email" required class="form-control" id="floatingInput" placeholder="name@example.com">
                                <label for="floatingInput">Email address</label>
                            </div>
                            <div class="form-floating mb-4">
                                <input type="password" name="password" required class="form-control" id="floatingPassword" placeholder="Password">
                                <label for="floatingPassword">Password</label>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="exampleCheck1">
                                    <label class="form-check-label" for="exampleCheck1">Check me out</label>
                                </div>
                                <a href="">Forgot Password</a>
                            </div>
                            <button  type="submit" class="btn btn-primary py-3 w-100 mb-4">Sign Up</button>
                            <p class="text-center mb-0">Already have an Account? <a href="">Sign In</a></p>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- Sign Up End -->


            <?php
            require_once '../templates/footer.php';
            ?>
