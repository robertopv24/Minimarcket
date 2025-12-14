<?php
// templates/views/supply_chain/supplier_form.php
// Variables: $supplier (opcional, para edición), $action (add/edit)
$isEdit = isset($supplier);
$formAction = $isEdit ? '/admin/suppliers/update?id=' . $supplier['id'] : '/admin/suppliers/store';
$title = $isEdit ? 'Editar Proveedor' : 'Registrar Nuevo Proveedor';
$submitText = $isEdit ? 'Actualizar Proveedor' : 'Guardar Proveedor';
?>

<?php require_once __DIR__ . '/../../header.php'; ?>
<?php require_once __DIR__ . '/../../menu.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0"><i class="fa fa-user-plus"></i> <?= $title ?></h3>
                </div>
                <div class="card-body">
                    <!-- FIX: Usamos la ruta del Router o un helper de rutas -->
                    <form method="post" action="<?= $formAction ?>">
                        <!-- CSRF Token -->
                        <?php $csrf = new \Minimarcket\Core\Security\CsrfToken(); ?>
                        <input type="hidden" name="csrf_token" value="<?= $csrf->getToken() ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nombre de la Empresa</label>
                                <input type="text" name="name" class="form-control"
                                    placeholder="Ej: Distribuidora Polar"
                                    value="<?= htmlspecialchars($supplier['name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Persona de Contacto</label>
                                <input type="text" name="contact_person" class="form-control"
                                    placeholder="Ej: Juan Pérez"
                                    value="<?= htmlspecialchars($supplier['contact_person'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Correo Electrónico</label>
                                <input type="email" name="email" class="form-control" placeholder="contacto@empresa.com"
                                    value="<?= htmlspecialchars($supplier['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="phone" class="form-control" placeholder="+58 412 1234567"
                                    value="<?= htmlspecialchars($supplier['phone'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Dirección Física</label>
                            <textarea name="address" class="form-control" rows="3"
                                placeholder="Dirección de despacho o fiscal"><?= htmlspecialchars($supplier['address'] ?? '') ?></textarea>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="/admin/suppliers" class="btn btn-secondary me-2">Cancelar</a>
                            <button type="submit" class="btn btn-success px-5"><?= $submitText ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../footer.php'; ?>