<?php
// admin/categories.php
session_start();
require_once '../templates/autoload.php';

// Verificar acceso admin
$userManager->requireAdminAccess($_SESSION);

$title = "Gestionar Categorías";
$success = "";
$error = "";

// 1. PROCESAR ACCIONES
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = strtoupper(trim($_POST['name']));
        $station = $_POST['kitchen_station'] ?? 'kitchen';
        $icon = trim($_POST['icon']) ?: 'fa-tag';
        $desc = trim($_POST['description']);
        $isVisible = isset($_POST['is_visible']) ? 1 : 0;

        if (!empty($name)) {
            if ($productManager->createCategory($name, $station, $icon, $desc, $isVisible)) {
                $success = "Categoría creada con éxito.";
            } else {
                $error = "Error al crear la categoría (podría estar duplicada).";
            }
        }
    } elseif ($action === 'update') {
        $id = $_POST['id'];
        $name = strtoupper(trim($_POST['name']));
        $station = $_POST['kitchen_station'];
        $icon = trim($_POST['icon']);
        $desc = trim($_POST['description']);
        $isVisible = isset($_POST['is_visible']) ? 1 : 0;

        if ($productManager->updateCategory($id, $name, $station, $icon, $desc, $isVisible)) {
            $success = "Categoría actualizada.";
        } else {
            $error = "Error al actualizar.";
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        if ($productManager->deleteCategory($id)) {
            $success = "Categoría eliminada.";
        } else {
            $error = "Error al eliminar.";
        }
    }
}

$categories = $productManager->getCategories();

$availableIcons = [
    'fa-tag',
    'fa-hamburger',
    'fa-pizza-slice',
    'fa-hotdog',
    'fa-drumstick-bite',
    'fa-utensils',
    'fa-box-open',
    'fa-glass-whiskey',
    'fa-wine-glass',
    'fa-coffee',
    'fa-ice-cream',
    'fa-cookie',
    'fa-cheese',
    'fa-egg',
    'fa-fish',
    'fa-carrot',
    'fa-apple-whole',
    'fa-lemon',
    'fa-pepper-hot',
    'fa-bread-slice',
    'fa-beer-mug-empty'
];

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<style>
    .icon-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(45px, 1fr));
        gap: 8px;
        max-height: 200px;
        overflow-y: auto;
        padding: 10px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        background: #f8f9fa;
    }

    .icon-option {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 40px;
        width: 40px;
        border: 1px solid #ced4da;
        border-radius: 6px;
        background: white;
        color: #212529;
        /* Color oscuro explícito */
        cursor: pointer;
        transition: all 0.2s;
        font-size: 1.2rem;
    }

    .icon-option:hover {
        background: #e9ecef;
        border-color: #0d6efd;
        color: #0d6efd;
    }

    .icon-option.active {
        background: #0d6efd;
        color: white !important;
        border-color: #0a58ca;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
</style>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fa-solid fa-tags me-2"></i> Gestión de Categorías</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCreate">
            <i class="fa fa-plus me-1"></i> Nueva Categoría
        </button>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th style="width: 50px;">Icono</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th class="text-center">Visible</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">No hay categorías configuradas.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $c): ?>
                            <tr>
                                <td>
                                    <?= $c['id'] ?>
                                </td>
                                <td class="text-center"><i
                                        class="fa <?= htmlspecialchars($c['icon'] ?? '') ?> fa-lg text-primary"></i></td>
                                <td><strong>
                                        <?= htmlspecialchars($c['name'] ?? '') ?>
                                    </strong>
                                    <br>
                                    <span class="badge bg-light text-dark border small">
                                        <i class="fa fa-door-open me-1"></i> <?= strtoupper($c['kitchen_station']) ?>
                                    </span>
                                </td>
                                <td class="text-muted small">
                                    <?= htmlspecialchars($c['description'] ?? '') ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($c['is_visible']): ?>
                                        <span class="badge bg-success"><i class="fa fa-eye me-1"></i> Sí</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="fa fa-eye-slash me-1"></i> No</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-secondary me-1"
                                        onclick='openEditModal(<?= json_encode($c) ?>)'>
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <form action="" method="POST" class="d-inline"
                                        onsubmit="return confirm('¿Eliminar esta categoría? Los productos vinculados pasarán a ser SIN CATEGORÍA.')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL CREAR -->
<div class="modal fade" id="modalCreate" tabindex="-1">
    <div class="modal-dialog">
        <form action="" method="POST" class="modal-content">
            <input type="hidden" name="action" value="create">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="name" class="form-control" placeholder="Ej: PIZZAS" required
                        autocomplete="off">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-primary">Seleccionar Icono</label>
                    <input type="hidden" name="icon" id="create_icon_val" value="fa-tag">
                    <div class="icon-grid" id="create_icon_grid">
                        <?php foreach ($availableIcons as $icon): ?>
                            <div class="icon-option <?= ($icon === 'fa-tag') ? 'active' : '' ?>"
                                onclick="selectIcon('create', '<?= $icon ?>')" data-icon="<?= $icon ?>">
                                <i class="fa <?= $icon ?>"></i>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Estación de Cocina *</label>
                    <select name="kitchen_station" class="form-select" required>
                        <option value="kitchen">COCINA (Hamburguesas, etc)</option>
                        <option value="pizza">PIZZA</option>
                        <option value="bar">BAR (Bebidas)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_visible" id="create_visible" checked>
                        <label class="form-check-label fw-bold" for="create_visible">Visible en Tienda</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDITAR -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <form action="" method="POST" class="modal-content">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-header bg-light">
                <h5 class="modal-title">Editar Categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="name" id="edit_name" class="form-control" required autocomplete="off">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-success">Seleccionar Icono</label>
                    <input type="hidden" name="icon" id="edit_icon_val">
                    <div class="icon-grid" id="edit_icon_grid">
                        <?php foreach ($availableIcons as $icon): ?>
                            <div class="icon-option" onclick="selectIcon('edit', '<?= $icon ?>')" data-icon="<?= $icon ?>">
                                <i class="fa <?= $icon ?>"></i>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Estación de Cocina *</label>
                    <select name="kitchen_station" id="edit_station" class="form-select" required>
                        <option value="kitchen">COCINA</option>
                        <option value="pizza">PIZZA</option>
                        <option value="bar">BAR</option>
                    </select>
                </div>
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_visible" id="edit_visible">
                        <label class="form-check-label fw-bold" for="edit_visible">Visible en Tienda</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="submit" class="btn btn-success">Actualizar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(cat) {
        document.getElementById('edit_id').value = cat.id;
        document.getElementById('edit_name').value = cat.name;
        document.getElementById('edit_station').value = cat.kitchen_station;
        document.getElementById('edit_description').value = cat.description;
        document.getElementById('edit_visible').checked = cat.is_visible == 1;
        selectIcon('edit', cat.icon || 'fa-tag');
        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    }

    function selectIcon(mode, icon) {
        const input = document.getElementById(mode + '_icon_val');
        const grid = document.getElementById(mode + '_icon_grid');

        input.value = icon;

        // Update UI
        grid.querySelectorAll('.icon-option').forEach(opt => {
            opt.classList.remove('active');
            if (opt.dataset.icon === icon) opt.classList.add('active');
        });
    }
</script>

<?php require_once '../templates/footer.php'; ?>