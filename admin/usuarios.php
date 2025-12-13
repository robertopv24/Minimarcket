<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../templates/autoload.php';

use Minimarcket\Core\Container;
use Minimarcket\Modules\User\Services\UserService;

global $app;
$container = $app->getContainer();
$userService = $container->get(UserService::class);

$sessionManager = $container->get(\Minimarcket\Core\Session\SessionManager::class);

if (!$sessionManager->isAuthenticated() || $sessionManager->get('user_role') !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

require_once '../templates/header.php';
require_once '../templates/menu.php';

// Obtener lista de usuarios
$search = $_GET['search'] ?? '';
$usuarios = $userService->searchUsers($search);
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0"><i class="fa fa-users me-2"></i>Gestión de Usuarios</h2>
            <div class="text-muted small">Administra los accesos y roles del sistema</div>
        </div>
        <a href="agregar_usuario.php" class="btn btn-success rounded-pill px-4 shadow-sm hover-lift">
            <i class="fa fa-user-plus me-2"></i> Nuevo Usuario
        </a>
    </div>

    <!-- Barra de Búsqueda -->
    <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden">
        <div class="card-body p-2 bg-light">
            <form method="GET" action="" class="row g-2 align-items-center m-1">
                <div class="col-md-10">
                    <div class="input-group bg-white rounded-pill border">
                        <span class="input-group-text bg-white border-0 ps-3 text-muted"><i
                                class="fa fa-search"></i></span>
                        <input type="text" name="search" class="form-control border-0"
                            placeholder="Buscar por nombre, email o teléfono..."
                            value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill">Buscar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Usuario</th>
                            <th>Rol / Permisos</th>
                            <th>Contacto</th>
                            <th>Fecha Registro</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        <?php foreach ($usuarios as $usuario): ?>
                            <?php
                            // Definir estilo según el rol
                            $roleBadge = match ($usuario['role']) {
                                'admin' => '<span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-2"><i class="fa fa-user-shield me-1"></i>ADMINISTRADOR</span>',
                                'user' => '<span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2"><i class="fa fa-cash-register me-1"></i>CAJERO / USUARIO</span>',
                                default => '<span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3 py-2">INVITADO</span>'
                            };

                            // Avatar por defecto si no hay imagen (usamos iniciales o icono)
                            $initial = strtoupper(substr($usuario['name'], 0, 1));
                            $bgColors = ['bg-primary', 'bg-success', 'bg-info', 'bg-warning', 'bg-danger', 'bg-dark'];
                            $randomBg = $bgColors[rand(0, 5)];
                            ?>
                            <tr class="hover-shadow-row transition-all">
                                <td class="ps-4 fw-bold text-muted">#<?= $usuario['id'] ?></td>

                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="<?= $randomBg ?> text-white rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm"
                                            style="width: 45px; height: 45px; font-weight: bold; font-size: 1.2rem;">
                                            <?= $initial ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($usuario['name']) ?>
                                            </div>
                                            <small class="text-muted"><?= htmlspecialchars($usuario['email']) ?></small>
                                        </div>
                                    </div>
                                </td>

                                <td><?= $roleBadge ?></td>

                                <td>
                                    <?php if (!empty($usuario['phone'])): ?>
                                        <div class="d-flex align-items-center text-muted">
                                            <i
                                                class="fa fa-phone me-2 text-success"></i><?= htmlspecialchars($usuario['phone']) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small fst-italic">Sin teléfono</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <small class="text-muted">
                                        <i class="fa fa-calendar-alt me-1"></i>
                                        <?= date('d/m/Y', strtotime($usuario['created_at'])) ?>
                                    </small>
                                </td>

                                <td class="text-end pe-4">
                                    <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                        <a href="editar_usuario.php?id=<?= $usuario['id'] ?>"
                                            class="btn btn-sm btn-outline-warning border-0" title="Editar">
                                            <i class="fa fa-edit"></i>
                                        </a>

                                        <?php if ($usuario['id'] != $sessionManager->get('user_id')): ?>
                                            <a href="eliminar_usuario.php?id=<?= $usuario['id'] ?>"
                                                class="btn btn-sm btn-outline-danger border-0" title="Eliminar"
                                                onclick="return confirm('¿Estás seguro de eliminar a este usuario?');">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary border-0" disabled
                                                title="No puedes borrarte a ti mismo"><i class="fa fa-trash"></i></button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">No se encontraron usuarios.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-lift:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
        transition: all 0.2s;
    }

    .transition-all {
        transition: all 0.2s ease;
    }

    .hover-shadow-row:hover {
        background-color: #f8f9fa;
    }
</style>

<?php require_once '../templates/footer.php'; ?>