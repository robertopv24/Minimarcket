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

// Obtener Historial
$sql = "SELECT cs.*, u.name as cashier_name,
        (cs.closing_balance_usd - cs.calculated_usd) as diff_usd,
        (cs.closing_balance_ves - cs.calculated_ves) as diff_ves
        FROM cash_sessions cs
        JOIN users u ON cs.user_id = u.id
        WHERE cs.status = 'closed'
        ORDER BY cs.closed_at DESC LIMIT 50";

$stmt = $db->query($sql);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📑 Auditoría de Cierres de Caja</h2>
        <a href="caja_chica.php" class="btn btn-secondary">Volver a Tesorería</a>
    </div>

    <div class="card shadow">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 align-middle text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Cajero</th>
                            <th>Cierre</th>
                            <th class="bg-secondary">Diferencia (Cuadre)</th>
                            <th>Acciones</th> </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($sessions)): ?>
                            <?php foreach ($sessions as $s):
                                // Semáforo de colores
                                $diffColor = 'text-muted';
                                if ($s['diff_usd'] < -0.5 || $s['diff_ves'] < -1) $diffColor = 'text-danger fw-bold';
                                elseif ($s['diff_usd'] > 0.5 || $s['diff_ves'] > 1) $diffColor = 'text-success fw-bold';
                            ?>
                                <tr>
                                    <td class="fw-bold">#<?= $s['id'] ?></td>
                                    <td class="text-start">
                                        <i class="fa fa-user-circle"></i> <?= htmlspecialchars($s['cashier_name']) ?>
                                    </td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($s['closed_at'])) ?><br>
                                        <small class="text-muted"><?= date('h:i A', strtotime($s['closed_at'])) ?></small>
                                    </td>

                                    <td>
                                        <div class="<?= $diffColor ?>">
                                            <?= ($s['diff_usd'] > 0 ? '+' : '') . number_format($s['diff_usd'], 2) ?> $
                                        </div>
                                        <div class="<?= $diffColor ?> small">
                                            <?= ($s['diff_ves'] > 0 ? '+' : '') . number_format($s['diff_ves'], 2) ?> Bs
                                        </div>
                                    </td>

                                    <td>
                                        <a href="ver_cierre.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-primary" title="Ver Desglose Completo">
                                            <i class="fa fa-eye"></i> Ver Detalle
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="p-5 text-muted">No hay cierres de caja registrados aún.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>
