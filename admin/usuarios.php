<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../templates/autoload.php';

session_start();
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

require_once '../templates/header.php';
require_once '../templates/menu.php';

// Obtener lista de usuarios
$usuarios = $userManager->getAllUsers();
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>👥 Gestión de Usuarios</h2>
        <a href="agregar_usuario.php" class="btn btn-success">
            <i class="fa fa-user-plus"></i> Nuevo Usuario
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Rol / Permisos</th>
                            <th>Contacto</th>
                            <th>Fecha Registro</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <?php
                                // Definir estilo según el rol
                                $roleBadge = match($usuario['role']) {
                                    'admin' => '<span class="badge bg-danger"><i class="fa fa-user-shield me-1"></i>ADMINISTRADOR</span>',
                                    'user' => '<span class="badge bg-primary"><i class="fa fa-cash-register me-1"></i>CAJERO / USUARIO</span>',
                                    default => '<span class="badge bg-secondary">INVITADO</span>'
                                };

                                // Avatar por defecto si no hay imagen (usamos iniciales o icono)
                                $initial = strtoupper(substr($usuario['name'], 0, 1));
                            ?>
                            <tr>
                                <td class="fw-bold text-muted">#<?= $usuario['id'] ?></td>

                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-weight: bold;">
                                            <?= $initial ?>
                                        </div>
                                        <div>
                                            <span class="fw-bold"><?= htmlspecialchars($usuario['name']) ?></span><br>
                                            <small class="text-muted"><?= htmlspecialchars($usuario['email']) ?></small>
                                        </div>
                                    </div>
                                </td>

                                <td><?= $roleBadge ?></td>

                                <td>
                                    <?php if(!empty($usuario['phone'])): ?>
                                        <i class="fa fa-phone text-success me-2"></i><?= htmlspecialchars($usuario['phone']) ?>
                                    <?php else: ?>
                                        <span class="text-muted small">Sin teléfono</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <small class="text-muted">
                                        <i class="fa fa-calendar me-1"></i>
                                        <?= date('d/m/Y', strtotime($usuario['created_at'])) ?>
                                    </small>
                                </td>

                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="editar_usuario.php?id=<?= $usuario['id'] ?>" class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fa fa-edit"></i>
                                        </a>

                                        <?php if($usuario['id'] != $_SESSION['user_id']): ?>
                                            <a href="eliminar_usuario.php?id=<?= $usuario['id'] ?>" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Estás seguro de eliminar a este usuario?');">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary" disabled title="No puedes borrarte a ti mismo"><i class="fa fa-trash"></i></button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>
