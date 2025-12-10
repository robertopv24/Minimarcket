<?php
session_start();
require_once '../templates/autoload.php';

// Validar Admin o Cajero (PosAccess)
if (!$userManager->hasPosAccess($_SESSION)) {
    header("Location: ../index.php");
    exit;
}

$mensaje = '';
$error = '';

// CREAR/EDITAR CLIENTE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_client'])) {
    $id = $_POST['client_id'] ?? null;
    $name = trim($_POST['name']);
    $docId = trim($_POST['document_id']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $creditLimit = floatval($_POST['credit_limit'] ?? 0);

    if ($id) {
        // EDITAR
        $stmt = $db->prepare("UPDATE clients SET name = ?, document_id = ?, phone = ?, email = ?, address = ?, credit_limit = ? WHERE id = ?");
        $stmt->execute([$name, $docId, $phone, $email, $address, $creditLimit, $id]);
        $mensaje = "✅ Cliente actualizado correctamente.";
    } else {
        // CREAR
        $clientId = $creditManager->createClient($name, $docId, $phone, $email, $address, $creditLimit);
        if ($clientId) {
            $mensaje = "✅ Cliente creado con ID: $clientId";
        } else {
            $error = "Error al crear cliente.";
        }
    }
}

// ELIMINAR CLIENTE
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Verificar que no tenga deuda
    $stmt = $db->prepare("SELECT current_debt FROM clients WHERE id = ?");
    $stmt->execute([$id]);
    $debt = $stmt->fetchColumn();

    if ($debt > 0.01) {
        $error = "❌ No se puede eliminar. Cliente tiene deuda pendiente: $$debt";
    } else {
        $stmt = $db->prepare("DELETE FROM clients WHERE id = ?");
        $stmt->execute([$id]);
        $mensaje = "✅ Cliente eliminado.";
    }
}

// OBTENER CLIENTES
$search = $_GET['search'] ?? '';
if ($search) {
    $stmt = $db->prepare("SELECT * FROM clients WHERE name LIKE ? OR document_id LIKE ? OR phone LIKE ? ORDER BY name");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
} else {
    $stmt = $db->query("SELECT * FROM clients ORDER BY name");
}
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// OBTENER CLIENTE PARA EDITAR
$editClient = null;
if (isset($_GET['edit'])) {
    $editClient = $creditManager->getClientById($_GET['edit']);
}

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fa fa-users me-2"></i>Gestión de Clientes</h2>
            <p class="text-muted">Administrar base de datos de clientes y límites de crédito</p>
        </div>
        <a href="cobranzas.php" class="btn btn-outline-primary">📊 Ir a Cobranzas</a>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $mensaje ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- FORMULARIO -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><?= $editClient ? '✏️ Editar Cliente' : '➕ Nuevo Cliente' ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($editClient): ?>
                            <input type="hidden" name="client_id" value="<?= $editClient['id'] ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Nombre Completo *</label>
                            <input type="text" name="name" class="form-control" required
                                value="<?= htmlspecialchars($editClient['name'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Documento (Cédula/RIF)</label>
                            <input type="text" name="document_id" class="form-control"
                                value="<?= htmlspecialchars($editClient['document_id'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" name="phone" class="form-control"
                                value="<?= htmlspecialchars($editClient['phone'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                value="<?= htmlspecialchars($editClient['email'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Dirección</label>
                            <textarea name="address" class="form-control"
                                rows="2"><?= htmlspecialchars($editClient['address'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Límite de Crédito (USD)</label>
                            <input type="number" step="0.01" name="credit_limit" class="form-control"
                                value="<?= $editClient['credit_limit'] ?? 0 ?>">
                            <small class="text-muted">Máximo que puede deber este cliente</small>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="save_client" class="btn btn-primary">
                                <i class="fa fa-save"></i> <?= $editClient ? 'Actualizar' : 'Guardar' ?>
                            </button>
                            <?php if ($editClient): ?>
                                <a href="clientes.php" class="btn btn-secondary">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- LISTADO -->
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">📋 Clientes Registrados (<?= count($clients) ?>)</h5>
                    <form method="GET" class="d-flex">
                        <input type="search" name="search" class="form-control me-2" placeholder="Buscar..."
                            value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-primary">🔍</button>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Doc/Teléfono</th>
                                    <th>Deuda</th>
                                    <th>Límite</th>
                                    <th>Disponible</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($clients)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <?= $search ? '❌ No se encontraron resultados' : 'No hay clientes registrados' ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($clients as $c):
                                        $disponible = $c['credit_limit'] - $c['current_debt'];
                                        ?>
                                        <tr>
                                            <td><strong>#<?= $c['id'] ?></strong></td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($c['name']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($c['email'] ?? '') ?></small>
                                            </td>
                                            <td>
                                                <small><?= htmlspecialchars($c['document_id'] ?? '') ?></small><br>
                                                <small class="text-muted"><?= htmlspecialchars($c['phone'] ?? '') ?></small>
                                            </td>
                                            <td>
                                                <?php if ($c['current_debt'] > 0): ?>
                                                    <span
                                                        class="badge bg-danger">$<?= number_format($c['current_debt'], 2) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">$0.00</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>$<?= number_format($c['credit_limit'], 2) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $disponible > 0 ? 'success' : 'secondary' ?>">
                                                    $<?= number_format($disponible, 2) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?edit=<?= $c['id'] ?>" class="btn btn-outline-primary"
                                                        title="Editar">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <a href="cobranzas.php?client_id=<?= $c['id'] ?>"
                                                        class="btn btn-outline-info" title="Ver Deudas">
                                                        <i class="fa fa-wallet"></i>
                                                    </a>
                                                    <?php if ($c['current_debt'] == 0): ?>
                                                        <a href="?delete=<?= $c['id'] ?>" class="btn btn-outline-danger"
                                                            onclick="return confirm('¿Eliminar cliente <?= htmlspecialchars($c['name']) ?>?')"
                                                            title="Eliminar">
                                                            <i class="fa fa-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>