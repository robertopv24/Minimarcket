<?php
// admin/kds_settings.php
require_once '../templates/autoload.php';
session_start();

// Validar acceso administrativo
if (!isset($_SESSION['user_id'])) {
    header("Location: ../paginas/login.php");
    exit;
}
$userManager->requireAdminAccess($_SESSION);

$successMsg = "";
$errorMsg = "";

// PROCESAR GUARDADO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $keysToUpdate = [
        'kds_refresh_interval',
        'kds_color_llevar',
        'kds_color_local',
        'kds_color_delivery',
        'kds_color_preparing',
        'kds_sound_enabled',
        'kds_warning_time_medium',
        'kds_warning_time_late',
        'kds_color_warning_medium',
        'kds_color_warning_late',
        'kds_use_short_codes',
        'kds_color_card_bg',
        'kds_color_mixed_bg',
        'kds_color_mod_add',
        'kds_color_mod_remove',
        'kds_color_mod_side',
        'kds_product_name_color',
        'kds_sound_url_kitchen',
        'kds_sound_url_pizza',
        'kds_sound_url_dispatch',
        'kds_simple_flow'
    ];

    $allOk = true;
    foreach ($keysToUpdate as $key) {
        $value = $_POST[$key] ?? '';
        if (!$config->update($key, $value)) {
            $allOk = false;
            $errorMsg .= "Error en: $key. ";
        }
    }

    if ($allOk) {
        $successMsg = "Configuración guardada correctamente.";
    } else {
        $errorMsg = "Hubo un error al guardar: " . $errorMsg;
    }
}

// Obtener valores actuales
$currentSettings = $config->getAll();

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0" style="border-radius: 15px; background: #1e293b; color: white;">
                <div class="card-header bg-primary text-white p-3" style="border-radius: 15px 15px 0 0;">
                    <h4 class="mb-0 fw-bold"><i class="fa fa-cogs me-2"></i> Configuración KDS & Despacho</h4>
                </div>
                <div class="card-body p-4">

                    <?php if ($successMsg): ?>
                        <div class="alert alert-success alert-dismissible fade show border-0" role="alert">
                            <i class="fa fa-check-circle me-2"></i>
                            <?= $successMsg ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($errorMsg): ?>
                        <div class="alert alert-danger alert-dismissible fade show border-0" role="alert">
                            <i class="fa fa-exclamation-triangle me-2"></i>
                            <?= $errorMsg ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row g-4">
                            <!-- SECCIÓN: COLORES -->
                            <div class="col-12 mt-0">
                                <h5 class="text-info border-bottom border-secondary pb-2 mb-3"><i
                                        class="fa fa-palette me-2"></i> Identidad Visual</h5>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Color [LLEVAR]</label>
                                <div class="input-group overflow-hidden" style="border-radius: 8px;">
                                    <input type="color" class="form-control form-control-color w-100 border-0"
                                        name="kds_color_llevar"
                                        value="<?= $currentSettings['kds_color_llevar'] ?? '#ef4444' ?>"
                                        style="height: 45px;">
                                </div>
                                <small class="text-muted">Color de la etiqueta Takeaway.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Color [LOCAL]</label>
                                <div class="input-group overflow-hidden" style="border-radius: 8px;">
                                    <input type="color" class="form-control form-control-color w-100 border-0"
                                        name="kds_color_local"
                                        value="<?= $currentSettings['kds_color_local'] ?? '#3b82f6' ?>"
                                        style="height: 45px;">
                                </div>
                                <small class="text-muted">Color de la etiqueta de mesa.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Color [DELIVERY]</label>
                                <div class="input-group overflow-hidden" style="border-radius: 8px;">
                                    <input type="color" class="form-control form-control-color w-100 border-0"
                                        name="kds_color_delivery"
                                        value="<?= $currentSettings['kds_color_delivery'] ?? '#10b981' ?>"
                                        style="height: 45px;">
                                </div>
                                <small class="text-muted">Color de la etiqueta de domicilio.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Color Fondo Tarjeta</label>
                                <div class="input-group overflow-hidden" style="border-radius: 8px;">
                                    <input type="color" class="form-control form-control-color w-100 border-0"
                                        name="kds_color_card_bg"
                                        value="<?= $currentSettings['kds_color_card_bg'] ?? '#ffffff' ?>"
                                        style="height: 45px;">
                                </div>
                                <small class="text-muted">Color de fondo del ticket en KDS.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Fondo Pedido Mixto</label>
                                <div class="input-group overflow-hidden" style="border-radius: 8px;">
                                    <input type="color" class="form-control form-control-color w-100 border-0"
                                        name="kds_color_mixed_bg"
                                        value="<?= $currentSettings['kds_color_mixed_bg'] ?? '#fff3cd' ?>"
                                        style="height: 45px;">
                                </div>
                                <small class="text-muted">Color de fondo para resaltar Combos/Mixtos.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Color Tarjeta [PREPARANDO]</label>
                                <div class="input-group overflow-hidden" style="border-radius: 8px;">
                                    <input type="color" class="form-control form-control-color w-100 border-0"
                                        name="kds_color_preparing"
                                        value="<?= $currentSettings['kds_color_preparing'] ?? '#f59e0b' ?>"
                                        style="height: 45px;">
                                </div>
                                <small class="text-muted">Color base de las tarjetas en estado
                                    pendiente/preparando.</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold">Color [++] (Añadir)</label>
                                <div class="input-group overflow-hidden" style="border-radius: 8px;">
                                    <input type="color" class="form-control form-control-color w-100 border-0"
                                        name="kds_color_mod_add"
                                        value="<?= $currentSettings['kds_color_mod_add'] ?? '#198754' ?>"
                                        style="height: 40px;">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold">Color [--] (Quitar)</label>
                                <div class="input-group overflow-hidden" style="border-radius: 8px;">
                                    <input type="color" class="form-control form-control-color w-100 border-0"
                                        name="kds_color_mod_remove"
                                        value="<?= $currentSettings['kds_color_mod_remove'] ?? '#dc3545' ?>"
                                        style="height: 40px;">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold">Color [**] (Extra)</label>
                                <div class="input-group overflow-hidden" style="border-radius: 8px;">
                                    <input type="color" class="form-control form-control-color w-100 border-0"
                                        name="kds_color_mod_side"
                                        value="<?= $currentSettings['kds_color_mod_side'] ?? '#0dcaf0' ?>"
                                        style="height: 40px;">
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold">Color Nombre del Producto</label>
                                <div class="input-group overflow-hidden" style="border-radius: 8px;">
                                    <input type="color" class="form-control form-control-color w-100 border-0"
                                        name="kds_product_name_color"
                                        value="<?= $currentSettings['kds_product_name_color'] ?? '#ffffff' ?>"
                                        style="height: 45px;">
                                </div>
                                <small class="text-muted">Color del texto principal del producto.</small>
                            </div>

                            <!-- SECCIÓN: TIEMPOS -->
                            <div class="col-12 mt-5">
                                <h5 class="text-warning border-bottom border-secondary pb-2 mb-3"><i
                                        class="fa fa-clock me-2"></i> Temporizadores y Refresco</h5>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold">Auto-Refresco (Seg)</label>
                                <input type="number" class="form-control bg-dark text-white border-secondary"
                                    name="kds_refresh_interval"
                                    value="<?= $currentSettings['kds_refresh_interval'] ?? '30' ?>" min="5" max="300">
                                <small class="text-muted">Tiempo entre actualizaciones.</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold">Alerta Media (Min)</label>
                                <input type="number" class="form-control bg-dark text-white border-secondary"
                                    name="kds_warning_time_medium"
                                    value="<?= $currentSettings['kds_warning_time_medium'] ?? '15' ?>" min="1">
                                <small class="text-muted">Cambio a color amarillo.</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold">Alerta Tardia (Min)</label>
                                <input type="number" class="form-control bg-dark text-white border-secondary"
                                    name="kds_warning_time_late"
                                    value="<?= $currentSettings['kds_warning_time_late'] ?? '25' ?>" min="1">
                                <small class="text-muted">Cambio a color rojo parpadeante.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Color Alerta Media</label>
                                <div class="input-group overflow-hidden" style="border-radius: 8px;">
                                    <input type="color" class="form-control form-control-color w-100 border-0"
                                        name="kds_color_warning_medium"
                                        value="<?= $currentSettings['kds_color_warning_medium'] ?? '#3b82f6' ?>"
                                        style="height: 45px;">
                                </div>
                                <small class="text-muted">Color de la cabecera al llegar al tiempo medio.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Color Alerta Tardía</label>
                                <div class="input-group overflow-hidden" style="border-radius: 8px;">
                                    <input type="color" class="form-control form-control-color w-100 border-0"
                                        name="kds_color_warning_late"
                                        value="<?= $currentSettings['kds_color_warning_late'] ?? '#ef4444' ?>"
                                        style="height: 45px;">
                                </div>
                                <small class="text-muted">Color de la cabecera al llegar al tiempo tardío.</small>
                            </div>

                            <!-- SECCIÓN: CÓDIGOS CORTOS -->
                            <div class="col-12 mt-5">
                                <h5 class="text-info border-bottom border-secondary pb-2 mb-3"><i
                                        class="fa fa-keyboard me-2"></i> Códigos de Comanda</h5>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold d-block">Uso de Abreviaturas</label>
                                <div class="p-3 rounded border border-secondary d-flex justify-content-between align-items-center"
                                    style="background: rgba(0,0,0,0.2);">
                                    <div class="form-check form-switch p-0 m-0">
                                        <input class="form-check-input ms-0 me-2" type="checkbox"
                                            name="kds_use_short_codes" value="1" id="shortCodeSwitch"
                                            <?= ($currentSettings['kds_use_short_codes'] ?? '0') == '1' ? 'checked' : '' ?>>
                                        <label class="form-check-label text-white" for="shortCodeSwitch">
                                            Usar códigos cortos en lugar de nombres largos
                                        </label>
                                    </div>
                                    <a href="short_codes.php" class="btn btn-sm btn-outline-info fw-bold">
                                        <i class="fa fa-list me-1"></i> Gestionar Códigos
                                    </a>
                                </div>
                                <small class="text-muted d-block mt-1">Ejemplo: Mostrar "/t" en lugar de "TOSINETA".
                                    Requiere configurar los códigos primero.</small>
                            </div>

                            <!-- SECCIÓN: FLUJO DE TRABAJO -->
                            <div class="col-12 mt-5">
                                <h5 class="text-warning border-bottom border-secondary pb-2 mb-3"><i
                                        class="fa fa-bolt me-2"></i> Flujo de Operación</h5>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold d-block">Rapidez Operativa</label>
                                <div class="p-3 rounded border border-secondary d-flex justify-content-between align-items-center"
                                    style="background: rgba(255,193,7,0.05);">
                                    <div class="form-check form-switch p-0 m-0">
                                        <input class="form-check-input ms-0 me-2" type="checkbox"
                                            name="kds_simple_flow" value="1" id="simpleFlowSwitch"
                                            <?= ($currentSettings['kds_simple_flow'] ?? '0') == '1' ? 'checked' : '' ?>>
                                        <label class="form-check-label text-white" for="simpleFlowSwitch">
                                            Habilitar Flujo Simple (1 Clic para finalizar)
                                        </label>
                                    </div>
                                    <span class="badge bg-warning text-dark fw-bold">1 CLIC</span>
                                </div>
                                <small class="text-muted d-block mt-1">Si se deshabilita, se requerirán 2 clics (Preparando -> Listo) para registrar métricas de productividad.</small>
                            </div>

                            <!-- SECCIÓN: SONIDO -->
                            <div class="col-12 mt-5">
                                <h5 class="text-success border-bottom border-secondary pb-2 mb-3"><i
                                        class="fa fa-volume-up me-2"></i> Sonidos del Sistema</h5>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold d-block">Alertas Sonoras</label>
                                <div class="form-check form-switch p-3 bg-dark-subtle rounded border border-secondary"
                                    style="background: rgba(0,0,0,0.2);">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="kds_sound_enabled"
                                        value="1" id="soundSwitch" <?= ($currentSettings['kds_sound_enabled'] ?? '1') == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label text-white" for="soundSwitch">
                                        Habilitar Sonidos del Sistema
                                    </label>
                                </div>
                            </div>

                            <!-- Sound URLs -->
                            <div class="col-md-4 mt-2">
                                <label class="form-label text-white-50"><i class="fa fa-utensils me-1"></i> Sonido
                                    Cocina</label>
                                <input type="text" class="form-control bg-dark text-white border-secondary"
                                    name="kds_sound_url_kitchen"
                                    value="<?= $currentSettings['kds_sound_url_kitchen'] ?? '../assets/sounds/ping.mp3' ?>">
                            </div>
                            <div class="col-md-4 mt-2">
                                <label class="form-label text-white-50"><i class="fa fa-pizza-slice me-1"></i> Sonido
                                    Pizza</label>
                                <input type="text" class="form-control bg-dark text-white border-secondary"
                                    name="kds_sound_url_pizza"
                                    value="<?= $currentSettings['kds_sound_url_pizza'] ?? '../assets/sounds/ping.mp3' ?>">
                            </div>
                            <div class="col-md-4 mt-2">
                                <label class="form-label text-white-50"><i class="fa fa-truck me-1"></i> Sonido
                                    Despacho</label>
                                <input type="text" class="form-control bg-dark text-white border-secondary"
                                    name="kds_sound_url_dispatch"
                                    value="<?= $currentSettings['kds_sound_url_dispatch'] ?? '../assets/sounds/success.mp3' ?>">
                            </div>

                            <div class="col-12 text-end mt-5">
                                <hr class="border-secondary opacity-25">
                                <button type="submit" name="save_settings"
                                    class="btn btn-lg btn-success px-5 fw-bold shadow-sm">
                                    <i class="fa fa-save me-2"></i> Guardar Cambios
                                </button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .form-control:focus {
        background-color: #2d3748 !important;
        color: white !important;
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
    }

    .form-control-color::-webkit-color-swatch {
        border-radius: 8px;
        border: none;
    }

    .input-group-text {
        background-color: #0f172a;
        border-color: #334155;
        color: #94a3b8;
    }
</style>

<?php require_once '../templates/footer.php'; ?>