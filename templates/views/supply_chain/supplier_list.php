<?php
// templates/views/supply_chain/supplier_list.php
// Variables esperadas: $suppliers, $search
?>

<?php require_once __DIR__ . '/../../header.php'; ?>
<?php require_once __DIR__ . '/../../menu.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>🏢 Gestión de Proveedores</h2>
        <a href="add_supplier.php" class="btn btn-success">
            <i class="fa fa-plus-circle"></i> Nuevo Proveedor
        </a>
    </div>

    <!-- Barra de Búsqueda -->
    <div class="card mb-4 bg-light">
        <div class="card-body py-3">
            <form method="GET" action="" class="row g-2 align-items-center">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Buscar por nombre..."
                            value="<?= htmlspecialchars($search ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Buscar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body p-0">
            <?php if (!empty($suppliers)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Empresa / Contacto</th>
                                <th>Datos de Contacto</th>
                                <th>Ubicación</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($suppliers as $supplier): ?>
                                <tr>
                                    <td class="fw-bold">#<?= $supplier['id'] ?></td>
                                    <td>
                                        <strong class="fs-5"><?= htmlspecialchars($supplier['name']) ?></strong><br>
                                        <small class="text-muted"><i class="fa fa-user"></i>
                                            <?= htmlspecialchars($supplier['contact_person']) ?></small>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column small">
                                            <span><i
                                                    class="fa fa-envelope text-primary me-2"></i><?= htmlspecialchars($supplier['email']) ?></span>
                                            <span><i
                                                    class="fa fa-phone text-success me-2"></i><?= htmlspecialchars($supplier['phone']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <small><?= mb_strimwidth(htmlspecialchars($supplier['address']), 0, 30, "...") ?></small>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="edit_supplier.php?id=<?= $supplier['id'] ?>" class="btn btn-sm btn-primary"
                                                title="Ver/Editar">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <a href="delete_supplier.php?id=<?= $supplier['id'] ?>"
                                                class="btn btn-sm btn-danger" title="Eliminar"
                                                onclick="return confirm('¿Eliminar proveedor?');">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-5 text-center text-muted">
                    <i class="fa fa-users-slash fa-3x mb-3"></i>
                    <p>No hay proveedores registrados.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../footer.php'; ?>