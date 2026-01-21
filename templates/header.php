<?php



// Verificar si hay un usuario logueado y definir el rol




// Obtener datos del usuario si está autenticado
$userId = $_SESSION['user_id'] ?? null;
$user = $_SESSION['user_name'] ?? null;
$email = $_SESSION['user_email'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;
$profile_pic = $_SESSION['user_profile_pic'] ?? 'default.jpg';







?>







<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $GLOBALS['site_name']; ?></title>

    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">


    <!-- Favicon -->
    <link href="../img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Roboto:wght@500;700&display=swap"
        rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Select2 Stylesheet (NUEVO) -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- SweetAlert2 (Alertas Modernas) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Libraries Stylesheet -->
    <link href="../lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="../lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

    <!-- Customized Bootstrap Stylesheet -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="../css/style.css" rel="stylesheet">

    <!-- JavaScript Libraries (Moved to Header for Stability) -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>


<body>
    <div class="container-fluid position-relative d-flex p-0">
        <!-- Spinner Start -->

        <!-- Spinner End -->

        <!-- Content Start -->
        <div class="content">
            <!-- Navbar Start -->
            <nav class="navbar navbar-expand bg-secondary navbar-dark sticky-top px-4 py-0">

                <a href="#" class="sidebar-toggler flex-shrink-0">
                    <i class="fa fa-bars"></i>
                </a>

                <div class="navbar-nav align-items-center ms-auto">
                    <!-- Logo del sitio, usando la constante SITE_NAME definida en config.php -->
                    <a href="index.html" class="navbar-brand mx-8 mb-8">
                        <h3 class="text-primary"><? echo $GLOBALS['site_name']; ?></h3>
                        <!-- Mostrar el nombre del sitio desde la configuración -->
                    </a>
                </div>

                <div class="navbar-nav align-items-center ms-auto">
                    <!-- Mostrar la información del usuario si está autenticado -->
                    <?php if ($user_role): ?>
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                                <img class="rounded-circle me-lg-2"
                                    src="../uploads/profile_pics/<?php echo $profile_pic; ?>" alt=""
                                    style="width: 40px; height: 40px;">
                                <span class="d-none d-lg-inline-flex"><?php echo ucfirst($user_role); ?></span>
                                <!-- Muestra el rol del usuario (Admin, User, etc.) -->
                            </a>
                            <div class="dropdown-menu dropdown-menu-end bg-secondary border-0 rounded-0 rounded-bottom m-0">
                                <?php if ($user_role === 'admin'): ?>
                                    <!-- Mostrar el menú para el administrador -->

                                    <a href="../paginas/perfil.php" class="nav-item nav-link ">Perfil</a>
                                    <a href="../paginas/carrito.php" class="nav-item nav-link ">Carrito</a>
                                    <a href="../paginas/logout.php" class="nav-item nav-link ">Cerras Sesión</a>


                                <?php elseif ($user_role === 'user'): ?>
                                    <!-- Mostrar el menú para el usuario -->

                                    <a href="../paginas/perfil.php" class="nav-item nav-link ">Perfil</a>
                                    <a href="../paginas/carrito.php" class="nav-item nav-link ">Carrito</a>
                                    <a href="../paginas/logout.php" class="nav-item nav-link ">Cerras Sesión</a>


                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Mostrar el menú para visitantes -->
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                                <img class="rounded-circle me-lg-2" src="img/user.jpg" alt=""
                                    style="width: 40px; height: 40px;">
                                <span class="d-none d-lg-inline-flex">Visitante</span>
                                <!-- Mostrar que el usuario es visitante -->
                            </a>
                            <div class="dropdown-menu dropdown-menu-end bg-secondary border-0 rounded-0 rounded-bottom m-0">

                                <a href="../paginas/register.php" class="nav-item nav-link ">Regístrate</a>
                                <a href="../paginas/login.php" class="nav-item nav-link ">Inicia Sesión</a>



                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </nav>
            <!-- Navbar End -->