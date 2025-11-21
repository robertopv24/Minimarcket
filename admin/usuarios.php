<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_name']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../paginas/login.php");
    exit();
}

require_once '../templates/autoload.php';
require_once '../templates/header.php';
require_once '../templates/menu.php';

// Obtener la lista de usuarios
$usuarios = $userManager->getAllUsers();

?>

<div class="container mt-5">
    <h2>Gestión de Usuarios</h2>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Correo Electrónico</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td><?php echo $usuario['id']; ?></td>
                    <td><?php echo $usuario['name']; ?></td>
                    <td><?php echo $usuario['email']; ?></td>
                    <td><?php echo $usuario['role']; ?></td>
                    <td>
                        <a href="editar_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-primary">Editar</a>
                        <a href="eliminar_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-danger">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="agregar_usuario.php" class="btn btn-success">Agregar Usuario</a>
</div>

<?php require_once '../templates/footer.php'; ?>
