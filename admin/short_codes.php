<?php
// admin/short_codes.php
require_once '../templates/autoload.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../paginas/login.php");
    exit;
}
$userManager->requireAdminAccess($_SESSION);

$successMsg = "";
$errorMsg = "";

// PROCESAR GUARDADO MASIVO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_codes'])) {
    try {
        $db->beginTransaction();

        // 1. Raw Materials
        if (isset($_POST['raw'])) {
            $stmt = $db->prepare("UPDATE raw_materials SET short_code = ? WHERE id = ?");
            foreach ($_POST['raw'] as $id => $code) {
                $stmt->execute([trim($code) ?: null, $id]);
            }
        }

        // 2. Manufactured Products
        if (isset($_POST['manufactured'])) {
            $stmt = $db->prepare("UPDATE manufactured_products SET short_code = ? WHERE id = ?");
            foreach ($_POST['manufactured'] as $id => $code) {
                $stmt->execute([trim($code) ?: null, $id]);
            }
        }

        // 3. Simple Products
        if (isset($_POST['products'])) {
            $stmt = $db->prepare("UPDATE products SET short_code = ? WHERE id = ?");
            foreach ($_POST['products'] as $id => $code) {
                $stmt->execute([trim($code) ?: null, $id]);
            }
        }

        $db->commit();
        $successMsg = "Códigos actualizados correctamente.";
    } catch (Exception $e) {
        $db->rollBack();
        $errorMsg = "Error al actualizar: " . $e->getMessage();
    }
}

// OBTENER DATOS
$rawItems = $db->query("SELECT id, name, short_code FROM raw_materials WHERE category = 'ingredient' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$manufactured = $db->query("SELECT id, name, short_code FROM manufactured_products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$products = $db->query("SELECT id, name, short_code FROM products WHERE product_type = 'simple' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0 text-white"><i class="fa fa-keyboard me-2 text-info"></i> Gestión de Códigos Cortos
            </h2>
            <p class="text-muted mb-0">Define abreviaturas para agilizar los tickets de cocina.</p>
        </div>
        <a href="kds_settings.php" class="btn btn-outline-light"><i class="fa fa-cog me-2"></i> Configuración KDS</a>
    </div>

    <?php if ($successMsg): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fa fa-check-circle me-2"></i>
            <?= $successMsg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="row g-4">
            <!-- INGREDIENTES -->
            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-0"
                    style="background: #1e293b; color: white; border-radius: 12px;">
                    <div class="card-header bg-dark text-white border-0 py-3" style="border-radius: 12px 12px 0 0;">
                        <h5 class="mb-0 fw-bold"><i class="fa fa-leaf me-2 text-success"></i> Ingredientes (Materia
                            Prima)</h5>
                    </div>
                    <div class="card-body p-0" style="max-height: 60vh; overflow-y: auto;">
                        <table class="table table-dark table-hover mb-0">
                            <thead class="sticky-top bg-dark">
                                <tr>
                                    <th>Nombre</th>
                                    <th width="100">Código</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rawItems as $it): ?>
                                    <tr>
                                        <td class="align-middle fs-7">
                                            <?= htmlspecialchars($it['name']) ?>
                                        </td>
                                        <td>
                                            <input type="text" name="raw[<?= $it['id'] ?>]"
                                                value="<?= htmlspecialchars($it['short_code'] ?? '') ?>"
                                                class="form-control form-control-sm bg-dark text-info border-secondary text-center fw-bold"
                                                maxlength="10" placeholder="...">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- PRODUCTOS MANUFACTURADOS -->
            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-0"
                    style="background: #1e293b; color: white; border-radius: 12px;">
                    <div class="card-header bg-dark text-white border-0 py-3" style="border-radius: 12px 12px 0 0;">
                        <h5 class="mb-0 fw-bold"><i class="fa fa-industry me-2 text-warning"></i> Manufacturados</h5>
                    </div>
                    <div class="card-body p-0" style="max-height: 60vh; overflow-y: auto;">
                        <table class="table table-dark table-hover mb-0">
                            <thead class="sticky-top bg-dark">
                                <tr>
                                    <th>Nombre</th>
                                    <th width="100">Código</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($manufactured as $it): ?>
                                    <tr>
                                        <td class="align-middle fs-7">
                                            <?= htmlspecialchars($it['name']) ?>
                                        </td>
                                        <td>
                                            <input type="text" name="manufactured[<?= $it['id'] ?>]"
                                                value="<?= htmlspecialchars($it['short_code'] ?? '') ?>"
                                                class="form-control form-control-sm bg-dark text-info border-secondary text-center fw-bold"
                                                maxlength="10" placeholder="...">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- PRODUCTOS TERMINADOS -->
            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-0"
                    style="background: #1e293b; color: white; border-radius: 12px;">
                    <div class="card-header bg-dark text-white border-0 py-3" style="border-radius: 12px 12px 0 0;">
                        <h5 class="mb-0 fw-bold"><i class="fa fa-box-open me-2 text-primary"></i> Productos Directos
                        </h5>
                    </div>
                    <div class="card-body p-0" style="max-height: 60vh; overflow-y: auto;">
                        <table class="table table-dark table-hover mb-0">
                            <thead class="sticky-top bg-dark">
                                <tr>
                                    <th>Nombre</th>
                                    <th width="100">Código</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $it): ?>
                                    <tr>
                                        <td class="align-middle fs-7">
                                            <?= htmlspecialchars($it['name']) ?>
                                        </td>
                                        <td>
                                            <input type="text" name="products[<?= $it['id'] ?>]"
                                                value="<?= htmlspecialchars($it['short_code'] ?? '') ?>"
                                                class="form-control form-control-sm bg-dark text-info border-secondary text-center fw-bold"
                                                maxlength="10" placeholder="...">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end mt-4">
            <button type="submit" name="save_codes" class="btn btn-lg btn-success px-5 fw-bold shadow-sm rounded-pill">
                <i class="fa fa-save me-2"></i> Guardar Todos los Códigos
            </button>
        </div>
    </form>
</div>

<style>
    .fs-7 {
        font-size: 0.85rem;
    }

    .table-dark {
        --bs-table-bg: transparent;
    }

    ::-webkit-scrollbar {
        width: 6px;
    }

    ::-webkit-scrollbar-thumb {
        background: #475569;
        border-radius: 10px;
    }

    ::-webkit-scrollbar-track {
        background: transparent;
    }

    .form-control:focus {
        background-color: #0f172a !important;
        border-color: #3b82f6 !important;
        color: white !important;
    }
</style>

<?php require_once '../templates/footer.php'; ?>