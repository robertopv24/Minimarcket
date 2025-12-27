<?php
require_once '../templates/autoload.php';
require_once '../funciones/Csrf.php';

// Auth Check
session_start();
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

$success = '';
$error = '';

$db = Database::getConnection();

// --- HANDLERS ---

// 1. Save Global Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) die("CSRF Error");
    
    $settings = [
        'background_audio' => $_POST['background_audio'],
        'default_duration' => $_POST['default_duration'],
        'global_suggestion_probability' => $_POST['global_suggestion_probability']
    ];

    if (isset($_FILES['background_audio_file']) && $_FILES['background_audio_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/tv/audio/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = 'bg_music_' . time() . '.' . pathinfo($_FILES['background_audio_file']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['background_audio_file']['tmp_name'], $uploadDir . $fileName);
        $settings['background_audio'] = 'uploads/tv/audio/' . $fileName; // Safe relative path
    } else {
        $settings['background_audio'] = $_POST['background_audio']; // Keep existing or text input if provided
    }

    foreach($settings as $k => $v) {
        $stmt = $db->prepare("REPLACE INTO tv_settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$k, $v]);
    }
    $success = "Configuración global guardada.";
}

// 2. Add/Edit Playlist Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_item'])) {
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) die("CSRF Error");

    $id = $_POST['item_id'] ?? '';
    $productId = !empty($_POST['product_id']) ? $_POST['product_id'] : null;
    $title = $_POST['custom_title'];
    $desc = $_POST['custom_description'];
    $price = $_POST['custom_price'];
    $img = !empty($_POST['custom_image_url']) ? $_POST['custom_image_url'] : null; // Handle file upload ideally, but text for now
    $dur = $_POST['duration_seconds'];
    $sugg = isset($_POST['show_suggestion']) ? 1 : 0;
    $suggText = $_POST['suggestion_text'];
    $active = isset($_POST['is_active']) ? 1 : 0;
    $order = $_POST['sort_order'] ?? 0;

    // Handle File Upload for custom image
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/tv/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = uniqid('tv_') . '_' . basename($_FILES['image_file']['name']);
        move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir . $fileName);
        $img = 'uploads/tv/' . $fileName;
    }

    if ($id) {
        // Update
        $sql = "UPDATE tv_playlist_items SET product_id=?, custom_title=?, custom_description=?, custom_image_url=?, custom_price=?, duration_seconds=?, sort_order=?, is_active=?, show_suggestion=?, suggestion_text=? WHERE id=?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$productId, $title, $desc, $img, $price, $dur, $order, $active, $sugg, $suggText, $id]);
        $success = "Item actualizado.";
    } else {
        // Create
        $sql = "INSERT INTO tv_playlist_items (product_id, custom_title, custom_description, custom_image_url, custom_price, duration_seconds, sort_order, is_active, show_suggestion, suggestion_text) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$productId, $title, $desc, $img, $price, $dur, $order, $active, $sugg, $suggText]);
        $success = "Nuevo item agregado.";
    }
}

// 3. Delete Item
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM tv_playlist_items WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: tv_config.php");
    exit;
}

// --- DATA FETCHING ---
// Get Settings
$s_stmt = $db->query("SELECT * FROM tv_settings");
$currentSettings = [];
while($row = $s_stmt->fetch()) {
    $currentSettings[$row['setting_key']] = $row['setting_value'];
}

// Get Items
$i_stmt = $db->query("SELECT t.*, p.name as product_name FROM tv_playlist_items t LEFT JOIN products p ON t.product_id = p.id ORDER BY t.sort_order ASC");
$items = $i_stmt->fetchAll();

// Get Products (for dropdown)
$products = $productManager->getAllProducts();

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📺 Configuración Menú TV</h2>
        <a href="../tv/index.php" target="_blank" class="btn btn-outline-primary"><i class="fa fa-external-link-alt"></i> Ver Pantalla</a>
    </div>

    <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" id="playlist-tab" data-bs-toggle="tab" data-bs-target="#playlist" type="button">📝 Playlist & Contenido</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button">⚙️ Configuración Global</button>
        </li>
    </ul>

    <div class="tab-content" id="myTabContent">
        
        <!-- PESTAÑA PLAYLIST -->
        <div class="tab-pane fade show active" id="playlist">
            <div class="text-end mb-3">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalItem" onclick="resetModal()">
                    <i class="fa fa-plus"></i> Agregar Slide
                </button>
            </div>
            
            <div class="card shadow">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Orden</th>
                                <th>Contenido (Producto/Título)</th>
                                <th>Duración</th>
                                <th>Sugerencia?</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($items as $it): ?>
                            <tr>
                                <td><span class="badge bg-secondary rounded-circle"><?= $it['sort_order'] ?></span></td>
                                <td>
                                    <?php if($it['product_id']): ?>
                                        <span class="badge bg-info text-dark">Producto</span> <strong><?= $it['product_name'] ?></strong>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Custom</span> <strong><?= $it['custom_title'] ?></strong>
                                    <?php endif; ?>
                                    
                                    <?php if($it['custom_image_url']): ?>
                                        <br><small class="text-muted"><i class="fa fa-image"></i> Usa imagen personalizada</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= $it['duration_seconds'] ?>s</td>
                                <td>
                                    <?= $it['show_suggestion'] ? '<span class="text-success"><i class="fa fa-check"></i> Sí</span>' : '<span class="text-muted">No</span>' ?>
                                    <?php if($it['show_suggestion']) echo "<br><small class='text-muted'>{$it['suggestion_text']}</small>"; ?>
                                </td>
                                <td><?= $it['is_active'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>' ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick='editItem(<?= json_encode($it) ?>)'>
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?= $it['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Borrar?')">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- PESTAÑA SETTINGS -->
        <div class="tab-pane fade" id="settings">
            <div class="card shadow p-4" style="max-width: 600px">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label>🔊 URL Música de Fondo (Opcional)</label>
                        <input type="text" name="background_audio" id="background_audio" class="form-control mb-2" value="<?= htmlspecialchars($currentSettings['background_audio'] ?? '') ?>" placeholder="URL o ruta de archivo...">
                        <input type="file" name="background_audio_file" class="form-control" accept="audio/mp3,audio/mpeg">
                        <small class="text-muted">Sube un archivo MP3 o escribe la ruta manualmente.</small>
                    </div>
                    <div class="mb-3">
                        <label>⏱️ Duración por defecto (segundos)</label>
                        <input type="number" name="default_duration" class="form-control" value="<?= $currentSettings['default_duration'] ?? 10 ?>">
                    </div>
                    <div class="mb-3">
                        <label>✨ Probabilidad de Sugerencia (0.0 - 1.0)</label>
                        <input type="number" step="0.1" min="0" max="1" name="global_suggestion_probability" class="form-control" value="<?= $currentSettings['global_suggestion_probability'] ?? 0.4 ?>">
                    </div>
                    <?= Csrf::insertTokenField() ?>
                    <button type="submit" name="save_settings" class="btn btn-success"><i class="fa fa-save"></i> Guardar Configuración</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- MODAL ITEM -->
<div class="modal fade" id="modalItem" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST" enctype="multipart/form-data">
            <div class="modal-header">
                <h5 class="modal-title">Editar Slide</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="item_id" id="item_id">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Producto Asociado (Opcional)</label>
                        <select name="product_id" id="product_id" class="form-select" onchange="toggleFields()">
                            <option value="">-- Ninguno (Slide Personalizado) --</option>
                            <?php foreach($products as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= $p['name'] ?> ($<?= $p['price_usd'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Si seleccionas un producto, los datos se llenan solos salvo que los sobrescribas abajo.</small>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Orden</label>
                        <input type="number" name="sort_order" id="sort_order" class="form-control" value="0">
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="form-check mt-4">
                           <input type="checkbox" name="is_active" id="is_active" class="form-check-input" checked>
                           <label class="form-check-label">Activo</label>
                       </div>
                    </div>
                </div>

                <hr>
                <h6>Overrides / Contenido Personalizado</h6>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                         <label>Título</label>
                         <input type="text" name="custom_title" id="custom_title" class="form-control" placeholder="Ej: ¡Oferta Especial!">
                    </div>
                    <div class="col-md-6 mb-3">
                         <label>Precio (Texto)</label>
                         <input type="text" name="custom_price" id="custom_price" class="form-control" placeholder="Ej: $5.00">
                    </div>
                </div>

                <div class="mb-3">
                    <label>Descripción</label>
                    <textarea name="custom_description" id="custom_description" class="form-control" rows="2"></textarea>
                </div>

                <div class="mb-3">
                    <label>Imagen Personalizada</label>
                    <input type="file" name="image_file" class="form-control">
                    <input type="hidden" name="custom_image_url" id="custom_image_url">
                    <small class="text-muted">Deja vacío para usar la imagen del producto (si existe).</small>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Duración (seg)</label>
                        <input type="number" name="duration_seconds" id="duration_seconds" class="form-control" value="10">
                    </div>
                </div>

                <hr>
                <h6>Configuración de Sugerencia (Cross-Selling)</h6>
                <div class="form-check mb-2">
                    <input type="checkbox" name="show_suggestion" id="show_suggestion" class="form-check-input">
                    <label class="form-check-label">Mostrar globo de sugerencia en este slide</label>
                </div>
                <div class="mb-3">
                    <label>Texto de Sugerencia</label>
                    <input type="text" name="suggestion_text" id="suggestion_text" class="form-control" placeholder="Ej: ¡Acompáñalo con papas!">
                </div>

                <?= Csrf::insertTokenField() ?>
            </div>
            <div class="modal-footer">
                <button type="submit" name="save_item" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
function editItem(data) {
    document.getElementById('item_id').value = data.id;
    document.getElementById('product_id').value = data.product_id || '';
    document.getElementById('custom_title').value = data.custom_title || '';
    document.getElementById('custom_description').value = data.custom_description || '';
    document.getElementById('custom_price').value = data.custom_price || '';
    document.getElementById('custom_image_url').value = data.custom_image_url || '';
    document.getElementById('duration_seconds').value = data.duration_seconds;
    document.getElementById('sort_order').value = data.sort_order;
    document.getElementById('is_active').checked = data.is_active == 1;
    document.getElementById('show_suggestion').checked = data.show_suggestion == 1;
    document.getElementById('suggestion_text').value = data.suggestion_text || '';
    
    var myModal = new bootstrap.Modal(document.getElementById('modalItem'));
    myModal.show();
}

function resetModal() {
    document.querySelector('#modalItem form').reset();
    document.getElementById('item_id').value = '';
    toggleFields();
}

function toggleFields() {
    const isProduct = document.getElementById('product_id').value !== '';
    const fields = ['custom_title', 'custom_description', 'custom_price'];
    
    fields.forEach(id => {
        const el = document.getElementById(id);
        if (isProduct) {
            el.setAttribute('placeholder', '(Usar valor del producto)');
            // Optional: el.disabled = true; // Use placeholder only to imply override
        } else {
            el.setAttribute('placeholder', 'Requerido');
        }
    });
}
</script>

<?php require_once '../templates/footer.php'; ?>
