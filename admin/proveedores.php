<?php
// proveedores.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../templates/autoload.php';

session_start();
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}



require_once '../templates/header.php';
require_once '../templates/menu.php';


?>


<div class="container mt-5">
    <h2>Gestión de Proveedores</h2>

    <?php
    $suppliers = $supplierManager->getAllSuppliers();
    if ($suppliers):
    ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Persona de Contacto</th>
                    <th>Correo Electrónico</th>
                    <th>Teléfono</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($suppliers as $supplier): ?>
                    <tr>
                        <td><?= htmlspecialchars($supplier['name']) ?></td>
                        <td><?= htmlspecialchars($supplier['contact_person']) ?></td>
                        <td><?= htmlspecialchars($supplier['email']) ?></td>
                        <td><?= htmlspecialchars($supplier['phone']) ?></td>
                        <td>
                            <a href="edit_supplier.php?id=<?= $supplier['id'] ?>" class="btn btn-sm btn-primary">Editar</a>
                            <a href="delete_supplier.php?id=<?= $supplier['id'] ?>" class="btn btn-sm btn-danger">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No hay proveedores registrados.</p>
    <?php endif; ?>

    <h3>Agregar Nuevo Proveedor</h3>
    <form method="post" action="process_supplier.php">
        <div class="mb-3">
            <label for="name" class="form-label">Nombre:</label>
            <input type="text" name="name" id="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="contact_person" class="form-label">Persona de Contacto:</label>
            <input type="text" name="contact_person" id="contact_person" class="form-control">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Correo Electrónico:</label>
            <input type="email" name="email" id="email" class="form-control">
        </div>
        <div class="mb-3">
            <label for="phone" class="form-label">Teléfono:</label>
            <input type="text" name="phone" id="phone" class="form-control">
        </div>
        <div class="mb-3">
            <label for="address" class="form-label">Dirección:</label>
            <textarea name="address" id="address" class="form-control"></textarea>
        </div>
        <button type="submit" name="action" value="add" class="btn btn-success">Agregar Proveedor</button>
    </form>
</div>









<?php require_once '../templates/footer.php'; ?>
