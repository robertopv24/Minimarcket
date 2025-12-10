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
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0"><i class="fa fa-user-plus"></i> Registrar Nuevo Proveedor</h3>
                </div>
                <div class="card-body">
                    <form method="post" action="process_supplier.php">
                        <input type="hidden" name="csrf_token" value="<?= Csrf::getToken() ?>">
                        <input type="hidden" name="action" value="add">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nombre de la Empresa</label>
                                <input type="text" name="name" class="form-control"
                                    placeholder="Ej: Distribuidora Polar" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Persona de Contacto</label>
                                <input type="text" name="contact_person" class="form-control"
                                    placeholder="Ej: Juan Pérez">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Correo Electrónico</label>
                                <input type="email" name="email" class="form-control"
                                    placeholder="contacto@empresa.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="phone" class="form-control" placeholder="+58 412 1234567">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Dirección Física</label>
                            <textarea name="address" class="form-control" rows="3"
                                placeholder="Dirección de despacho o fiscal"></textarea>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="proveedores.php" class="btn btn-secondary me-2">Cancelar</a>
                            <button type="submit" class="btn btn-success px-5">Guardar Proveedor</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>