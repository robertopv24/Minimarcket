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

$mensaje = '';

// PROCESAR FORMULARIOS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'create') {
        $name = $_POST['name'];
        $unit = $_POST['unit'];
        $cost = $_POST['cost'];
        $min = $_POST['min_stock'];
        $type = $_POST['is_cooking_supply'];

        if ($rawMaterialManager->createMaterial($name, $unit, $cost, $min, $type)) {
            $mensaje = '<div class="alert alert-success">Insumo creado correctamente.</div>';
        } else {
            $mensaje = '<div class="alert alert-danger">Error al crear insumo.</div>';
        }
    }

    if ($action === 'add_stock') {
        $id = $_POST['id'];
        $qty = $_POST['quantity'];
        $newCost = $_POST['cost'];

        if ($rawMaterialManager->addStock($id, $qty, $newCost)) {
            $mensaje = '<div class="alert alert-success">Stock actualizado y costo promediado.</div>';
        } else {
            $mensaje = '<div class="alert alert-danger">Error al actualizar stock.</div>';
        }
    }

    if ($action === 'delete') {
        $res = $rawMaterialManager->deleteMaterial($_POST['id']);
        if ($res === true) {
            $mensaje = '<div class="alert alert-success">Insumo eliminado.</div>';
        } else {
            $mensaje = '<div class="alert alert-danger">' . $res . '</div>';
        }
    }
}

$materials = $rawMaterialManager->getAllMaterials();

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>🌾 Materias Primas e Insumos</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreate">
            <i class="fa fa-plus-circle"></i> Nuevo Insumo
        </button>
    </div>

    <?= $mensaje ?>

    <div class="card shadow">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Stock Actual</th>
                            <th>Costo Unit. Promedio</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materials as $m):
                            $lowStock = $m['stock_quantity'] <= $m['min_stock'];
                            $typeLabel = $m['is_cooking_supply'] ? '<span class="badge bg-warning text-dark">Indirecto (Aceite/Gas)</span>' : '<span class="badge bg-info text-dark">Ingrediente</span>';
                        ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($m['name']) ?></td>
                                <td><?= $typeLabel ?></td>
                                <td>
                                    <span class="fs-5 fw-bold <?= $lowStock ? 'text-danger' : 'text-success' ?>">
                                        <?= floatval($m['stock_quantity']) ?>
                                    </span>
                                    <small class="text-muted"><?= $m['unit'] ?></small>
                                </td>
                                <td>$<?= number_format($m['cost_per_unit'], 2) ?></td>
                                <td>
                                    <?php if ($lowStock): ?>
                                        <span class="badge bg-danger">STOCK BAJO</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">OK</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-success me-1" onclick="openAddStock(<?= $m['id'] ?>, '<?= $m['name'] ?>', '<?= $m['unit'] ?>')" title="Registrar Compra">
                                        <i class="fa fa-plus"></i> Stock
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('¿Borrar este insumo?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCreate" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Registrar Nuevo Insumo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre del Material</label>
                        <input type="text" name="name" class="form-control" placeholder="Ej: Harina de Trigo" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Unidad de Medida</label>
                            <select name="unit" class="form-select">
                                <option value="kg">Kilogramos (kg)</option>
                                <option value="gr">Gramos (gr)</option>
                                <option value="lt">Litros (lt)</option>
                                <option value="ml">Mililitros (ml)</option>
                                <option value="und">Unidad (und)</option>
                                <option value="m">Metros (m)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo de Uso</label>
                            <select name="is_cooking_supply" class="form-select">
                                <option value="0">Ingrediente Directo (Masa, Queso)</option>
                                <option value="1">Insumo Indirecto/Gasto (Aceite, Gas)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Costo Inicial ($)</label>
                            <input type="number" step="0.01" name="cost" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Alerta Stock Mínimo</label>
                            <input type="number" step="0.01" name="min_stock" class="form-control" value="5">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Insumo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalStock" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Registrar Entrada: <span id="stockName"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_stock">
                    <input type="hidden" name="id" id="stockId">

                    <div class="mb-3">
                        <label class="form-label">Cantidad a Ingresar (<span id="stockUnit"></span>)</label>
                        <input type="number" step="0.001" name="quantity" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nuevo Costo Unitario ($)</label>
                        <input type="number" step="0.01" name="cost" class="form-control" placeholder="Precio de la factura actual" required>
                        <div class="form-text">El sistema promediará este costo con el inventario existente.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Sumar al Inventario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openAddStock(id, name, unit) {
        document.getElementById('stockId').value = id;
        document.getElementById('stockName').textContent = name;
        document.getElementById('stockUnit').textContent = unit;
        new bootstrap.Modal(document.getElementById('modalStock')).show();
    }
</script>

<?php require_once '../templates/footer.php'; ?>
