<!-- menu.php - Menú de navegación-->
<!-- Sidebar Start -->
<div class="sidebar pe-4 pb-3">
    <nav class="navbar bg-secondary navbar-dark">

        <div class="d-flex align-items-center ms-4 mb-4">
            <div class="position-relative">
                <!-- Imagen de perfil -->
                <img class="rounded-circle" src="../uploads/profile_pics/<?php echo htmlspecialchars($profile_pic);?>" alt="" style="width: 40px; height: 40px;">
                <div class="bg-success rounded-circle border border-2 border-white position-absolute end-0 bottom-0 p-1"></div>
            </div>
            <div class="ms-3">
                <!-- Mostrar el nombre y rol del usuario -->
                <?php if ($user_role === 'admin'): ?>
                    <h6 class="mb-0"><?php echo ucfirst(htmlspecialchars($user)); ?></h6>
                    <span><?php echo ucfirst(htmlspecialchars($user_role)); ?></span>
                <?php elseif ($user_role === 'user'): ?>
                    <h6 class="mb-0"><?php echo ucfirst(htmlspecialchars($user)); ?></h6>
                    <span><?php echo ucfirst(htmlspecialchars($user_role)); ?></span>
                <?php elseif (!$user_role): ?>
                    <h6 class="mb-0">Visitante</h6>
                    <span> </span>
                <?php endif; ?>
            </div>
        </div>

            <div class="navbar-nav w-100">

              <?php if ($user_role === 'admin'): ?>
                                  <div class="nav-item dropdown">
                                      <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"><i class="fa fa-cogs me-2"></i>Administración</a>
                                      <div class="dropdown-menu bg-transparent border-0 ps-4">
                                        <a href="../admin/index.php" class="nav-item nav-link">Dashboard</a>
                                        <a href="../admin/ventas.php" class="nav-item nav-link">Ventas</a>
                                        <a href="../admin/compras.php" class="nav-item nav-link">Compras</a>
                                        <a href="../admin/productos.php" class="nav-item nav-link">Inventario</a>

                                        <div class="nav-item nav-link text-warning small fw-bold mt-2">MANUFACTURA</div>
                                        <a href="../admin/insumos.php" class="nav-item nav-link">1. Materia Prima</a>
                                        <a href="../admin/manufactura.php" class="nav-item nav-link">2. Recetas y Producción</a>

                                        <div class="nav-item nav-link text-warning small fw-bold mt-2">FINANZAS</div>
                                        <a href="../admin/caja_chica.php" class="nav-item nav-link">Tesorería General</a>
                                        <a href="../admin/reportes_caja.php" class="nav-item nav-link">Auditoría de Cierres</a>

                                        <div class="nav-item nav-link text-warning small fw-bold mt-2">SISTEMA</div>
                                        <a href="../admin/usuarios.php" class="nav-item nav-link">Usuarios</a>
                                        <a href="../admin/proveedores.php" class="nav-item nav-link">Proveedores</a>
                                      </div>
                                  </div>


                      <div class="nav-item dropdown">
                          <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"><i class="fa fa-shopping-cart me-2"></i>Menú</a>
                          <div class="dropdown-menu bg-transparent border-0">
                            <a href="../paginas/index.php" class="nav-item nav-link ">Inicio</a>
                            <a href="../paginas/tienda.php" class="nav-item nav-link ">Tienda</a>
                            <a href="../paginas/cierre_caja.php" class="nav-item nav-link text-danger"><i class="fa fa-file-invoice-dollar me-2"></i>Cierre de Caja</a>

                          </div>
                      </div>


                <?php elseif ($user_role === 'user' ): ?>


                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"><i class="fa fa-shopping-cart me-2"></i>Menú</a>
                        <div class="dropdown-menu bg-transparent border-0">
                          <a href="index.php" class="nav-item nav-link ">Inicio</a>
                          <a href="tienda.php" class="nav-item nav-link ">Tienda</a>
                          <a href="cierre_caja.php" class="nav-item nav-link text-danger"><i class="fa fa-file-invoice-dollar me-2"></i>Cierre de Caja</a>

                        </div>
                    </div>

                <?php endif; ?>

                <?php if (!$user_role): ?>

                    <a href="index.php" class="nav-item nav-link ">Inicio</a>
                    <a href="tienda.php" class="nav-item nav-link ">Tienda</a>



                <?php endif; ?>

            </div>
        </nav>
        </div>



       <!-- Sidebar End -->
