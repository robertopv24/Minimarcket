<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../templates/autoload.php';

// session_start();
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

// Procesar Movimientos Manuales y Transferencias
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'vault_movement';

    if ($action === 'vault_movement') {
        $type = $_POST['type'];
        $amount = $_POST['amount'];
        $currency = $_POST['currency'];
        $description = $_POST['description'];
        $origin = ($type == 'deposit') ? 'manual_deposit' : 'owner_withdrawal';

        $res = $vaultManager->registerMovement($type, $origin, $amount, $currency, $description, $_SESSION['user_id']);

        if ($res === true) {
            $mensaje = '<div class="alert alert-success">Movimiento de efectivo registrado con éxito.</div>';
        } else {
            $mensaje = '<div class="alert alert-danger">Error: ' . $res . '</div>';
        }
    } elseif ($action === 'transfer') {
        // Procesar transferencia entre métodos de pago
        $fromMethodId = $_POST['from_method'];
        $toMethodId = $_POST['to_method'];
        $amount = floatval($_POST['transfer_amount']);
        $exchangeRate = floatval($_POST['exchange_rate'] ?? 1);
        $notes = $_POST['transfer_notes'] ?? '';

        try {
            $db->beginTransaction();

            // Obtener información de los métodos
            $stmtFrom = $db->prepare("SELECT name, type, currency FROM payment_methods WHERE id = ?");
            $stmtFrom->execute([$fromMethodId]);
            $fromMethod = $stmtFrom->fetch(PDO::FETCH_ASSOC);

            $stmtTo = $db->prepare("SELECT name, type, currency FROM payment_methods WHERE id = ?");
            $stmtTo->execute([$toMethodId]);
            $toMethod = $stmtTo->fetch(PDO::FETCH_ASSOC);

            if (!$fromMethod || !$toMethod) {
                throw new Exception("Método de pago no válido");
            }

            // Calcular monto convertido si hay cambio de moneda
            $amountTo = $amount;
            if ($fromMethod['currency'] !== $toMethod['currency']) {
                if ($fromMethod['currency'] === 'USD' && $toMethod['currency'] === 'VES') {
                    $amountTo = $amount * $exchangeRate;
                } elseif ($fromMethod['currency'] === 'VES' && $toMethod['currency'] === 'USD') {
                    $amountTo = $amount / $exchangeRate;
                }
            }

            // Obtener sesión de caja
            $stmtSession = $db->prepare("SELECT id FROM cash_sessions WHERE user_id = ? AND status = 'open' LIMIT 1");
            $stmtSession->execute([$_SESSION['user_id']]);
            $session = $stmtSession->fetch(PDO::FETCH_ASSOC);
            $sessionId = $session ? $session['id'] : 0;

            if ($sessionId === 0) {
                $stmtLast = $db->prepare("SELECT id FROM cash_sessions ORDER BY id DESC LIMIT 1");
                $stmtLast->execute();
                $last = $stmtLast->fetch(PDO::FETCH_ASSOC);
                $sessionId = $last ? $last['id'] : 1;
            }

            $description = "Transferencia: {$fromMethod['name']} → {$toMethod['name']}";
            if (!empty($notes)) {
                $description .= " | $notes";
            }

            // Registrar transacción de salida (expense) del método origen
            $amountUsdFrom = ($fromMethod['currency'] === 'USD') ? $amount : ($amount / $exchangeRate);
            $stmtExpense = $db->prepare("INSERT INTO transactions 
                (cash_session_id, type, amount, currency, exchange_rate, amount_usd_ref, payment_method_id, reference_type, description, created_by) 
                VALUES (?, 'expense', ?, ?, ?, ?, ?, 'manual', ?, ?)");
            $stmtExpense->execute([
                $sessionId,
                $amount,
                $fromMethod['currency'],
                $exchangeRate,
                $amountUsdFrom,
                $fromMethodId,
                $description,
                $_SESSION['user_id']
            ]);

            // Registrar transacción de entrada (income) al método destino
            $amountUsdTo = ($toMethod['currency'] === 'USD') ? $amountTo : ($amountTo / $exchangeRate);
            $stmtIncome = $db->prepare("INSERT INTO transactions 
                (cash_session_id, type, amount, currency, exchange_rate, amount_usd_ref, payment_method_id, reference_type, description, created_by) 
                VALUES (?, 'income', ?, ?, ?, ?, ?, 'manual', ?, ?)");
            $stmtIncome->execute([
                $sessionId,
                $amountTo,
                $toMethod['currency'],
                $exchangeRate,
                $amountUsdTo,
                $toMethodId,
                $description,
                $_SESSION['user_id']
            ]);

            // Si el origen es efectivo, registrar retiro de bóveda
            if ($fromMethod['type'] === 'cash') {
                $vaultRes = $vaultManager->registerMovement(
                    'withdrawal',
                    'owner_withdrawal',
                    $amount,
                    $fromMethod['currency'],
                    "Transferencia a {$toMethod['name']}",
                    $_SESSION['user_id'],
                    null,
                    false
                );
                if ($vaultRes !== true) {
                    throw new Exception("Error al actualizar bóveda: $vaultRes");
                }
            }

            // Si el destino es efectivo, registrar depósito en bóveda
            if ($toMethod['type'] === 'cash') {
                $vaultRes = $vaultManager->registerMovement(
                    'deposit',
                    'manual_deposit',
                    $amountTo,
                    $toMethod['currency'],
                    "Transferencia desde {$fromMethod['name']}",
                    $_SESSION['user_id'],
                    null,
                    false
                );
                if ($vaultRes !== true) {
                    throw new Exception("Error al actualizar bóveda: $vaultRes");
                }
            }

            $db->commit();
            $mensaje = '<div class="alert alert-success">Transferencia realizada con éxito.</div>';
        } catch (Exception $e) {
            $db->rollBack();
            $mensaje = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    }
}

// 1. Saldo Físico (Bóveda)
$balanceVault = $vaultManager->getBalance();

// 2. Saldos Totales por Método (Digitales y Bancos)
$sqlBalances = "SELECT pm.id, pm.name, pm.type, pm.currency,
                SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE -t.amount END) as total_balance
                FROM payment_methods pm
                LEFT JOIN transactions t ON pm.id = t.payment_method_id
                GROUP BY pm.id, pm.name, pm.type, pm.currency
                HAVING total_balance != 0";
$stmt = $db->query($sqlBalances);
$globalBalances = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Obtener TODOS los métodos de pago para el modal de transferencia
$allMethods = $transactionManager->getPaymentMethods();

// 4. Obtener tasa de cambio actual
$currentRate = isset($GLOBALS['config']) ? $GLOBALS['config']->get('exchange_rate') : 1;

// 5. OBTENER HISTORIAL DE MOVIMIENTOS (COMPLETO)
$movements = $vaultManager->getAllMovements();

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>🏦 Tesorería General</h2>
        <div>
            <button class="btn btn-warning me-2" data-bs-toggle="modal" data-bs-target="#modalTransfer">
                <i class="fa fa-exchange-alt"></i> Transferir Fondos
            </button>
            <a href="reportes_caja.php" class="btn btn-outline-dark">
                <i class="fa fa-history"></i> Ver Historial de Cierres de Caja
            </a>
        </div>
    </div>

    <?= $mensaje ?>

    <h5 class="text-muted border-bottom pb-2 mb-3">💵 Efectivo en Custodia (Bóveda)</h5>
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-success text-white text-center shadow">
                <div class="card-body">
                    <h2 class="fw-bold">$<?= number_format($balanceVault['balance_usd'], 2) ?></h2>
                    <p class="mb-0">Dólares Físicos</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-info text-white text-center shadow">
                <div class="card-body">
                    <h2 class="fw-bold"><?= number_format($balanceVault['balance_ves'], 2) ?> Bs</h2>
                    <p class="mb-0">Bolívares Físicos</p>
                </div>
            </div>
        </div>
    </div>

    <h5 class="text-muted border-bottom pb-2 mb-3">💳 Saldos en Cuentas (Calculados)</h5>
    <div class="row mb-4">
        <?php foreach ($globalBalances as $bal):
            if ($bal['type'] == 'cash')
                continue;
            ?>
            <div class="col-md-3 mb-3">
                <div class="card border-start border-4 border-primary shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase text-muted small"><?= htmlspecialchars($bal['name']) ?></h6>
                        <h4 class="text-primary fw-bold">
                            <?= number_format($bal['total_balance'], 2) ?>
                            <small class="text-dark fs-6"><?= $bal['currency'] ?></small>
                        </h4>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card mb-5 shadow-sm">
        <div class="card-header bg-secondary text-white">
            <i class="fa fa-hand-holding-usd"></i> Operaciones Manuales (Solo Efectivo/Bóveda)
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Acción</label>
                    <select name="type" class="form-select">
                        <option value="withdrawal">🔴 Retirar Dinero (Gasto/Dueño)</option>
                        <option value="deposit">🟢 Ingresar Dinero (Aporte)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Monto</label>
                    <input type="number" name="amount" step="0.01" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Moneda</label>
                    <select name="currency" class="form-select">
                        <option value="USD">USD ($)</option>
                        <option value="VES">VES (Bs)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Concepto</label>
                    <input type="text" name="description" class="form-control"
                        placeholder="Ej: Pago nómina, Retiro personal..." required>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-dark px-5">Registrar</button>
                </div>
            </form>
        </div>
    </div>

    <h4 class="mb-3">📜 Historial de Movimientos de Bóveda</h4>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Monto</th>
                            <th>Origen</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($movements)): ?>
                            <?php foreach ($movements as $mov): ?>
                                <tr>
                                    <td><?= date('d/m H:i', strtotime($mov['created_at'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $mov['type'] == 'deposit' ? 'success' : 'danger' ?>">
                                            <?= $mov['type'] == 'deposit' ? 'Entrada' : 'Salida' ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold">
                                        <?= number_format($mov['amount'], 2) ?>         <?= $mov['currency'] ?>
                                    </td>
                                    <td>
                                        <?php
                                        $origenLabel = match ($mov['origin']) {
                                            'session_close' => '<span class="badge bg-info text-dark">Cierre Caja</span>',
                                            'supplier_payment' => '<span class="badge bg-warning text-dark">Pago Prov.</span>',
                                            'manual_deposit' => '<span class="badge bg-success">Aporte</span>',
                                            'owner_withdrawal' => '<span class="badge bg-danger">Retiro</span>',
                                            default => $mov['origin']
                                        };
                                        echo $origenLabel;
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($mov['description']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No hay movimientos registrados aún.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Transferir Fondos -->
<div class="modal fade" id="modalTransfer" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fa fa-exchange-alt"></i> Transferir Fondos entre Métodos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="transfer">

                <div class="alert alert-info small">
                    <i class="fa fa-info-circle"></i> Mueve dinero de un método de pago a otro.
                    Si involucra efectivo, se actualizará la bóveda automáticamente.
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Desde (Origen)</label>
                    <select name="from_method" id="fromMethod" class="form-select" required>
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($allMethods as $m): ?>
                            <option value="<?= $m['id'] ?>" data-currency="<?= $m['currency'] ?>"
                                data-type="<?= $m['type'] ?>">
                                <?= htmlspecialchars($m['name']) ?> (<?= $m['currency'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Hacia (Destino)</label>
                    <select name="to_method" id="toMethod" class="form-select" required>
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($allMethods as $m): ?>
                            <option value="<?= $m['id'] ?>" data-currency="<?= $m['currency'] ?>"
                                data-type="<?= $m['type'] ?>">
                                <?= htmlspecialchars($m['name']) ?> (<?= $m['currency'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Monto a Transferir</label>
                    <input type="number" step="0.01" name="transfer_amount" id="transferAmount" class="form-control"
                        placeholder="Ej: 100.00" required>
                    <small class="text-muted" id="currencyHint">Ingresa el monto en la moneda de origen</small>
                </div>

                <div class="mb-3" id="exchangeRateGroup" style="display: none;">
                    <label class="form-label fw-bold">Tasa de Cambio</label>
                    <input type="number" step="0.01" name="exchange_rate" id="exchangeRate" class="form-control"
                        value="<?= $currentRate ?>" placeholder="Ej: <?= $currentRate ?>">
                    <small class="text-muted">1 USD = <span id="rateDisplay"><?= $currentRate ?></span> VES</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notas / Concepto (Opcional)</label>
                    <input type="text" name="transfer_notes" class="form-control"
                        placeholder="Ej: Cambio de efectivo a digital">
                </div>

                <div class="alert alert-warning small" id="conversionPreview" style="display: none;">
                    <strong>Vista Previa:</strong> <span id="previewText"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-warning">Confirmar Transferencia</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Detectar cambio de moneda y mostrar/ocultar tasa de cambio
    const fromMethod = document.getElementById('fromMethod');
    const toMethod = document.getElementById('toMethod');
    const exchangeRateGroup = document.getElementById('exchangeRateGroup');
    const exchangeRate = document.getElementById('exchangeRate');
    const transferAmount = document.getElementById('transferAmount');
    const conversionPreview = document.getElementById('conversionPreview');
    const previewText = document.getElementById('previewText');

    function updateExchangeRateVisibility() {
        const fromCurrency = fromMethod.selectedOptions[0]?.dataset.currency;
        const toCurrency = toMethod.selectedOptions[0]?.dataset.currency;

        if (fromCurrency && toCurrency && fromCurrency !== toCurrency) {
            exchangeRateGroup.style.display = 'block';
            updatePreview();
        } else {
            exchangeRateGroup.style.display = 'none';
            conversionPreview.style.display = 'none';
        }
    }

    function updatePreview() {
        const fromCurrency = fromMethod.selectedOptions[0]?.dataset.currency;
        const toCurrency = toMethod.selectedOptions[0]?.dataset.currency;
        const amount = parseFloat(transferAmount.value) || 0;
        const rate = parseFloat(exchangeRate.value) || 1;

        if (amount > 0 && fromCurrency && toCurrency && fromCurrency !== toCurrency) {
            let convertedAmount = amount;
            if (fromCurrency === 'USD' && toCurrency === 'VES') {
                convertedAmount = amount * rate;
            } else if (fromCurrency === 'VES' && toCurrency === 'USD') {
                convertedAmount = amount / rate;
            }

            previewText.textContent = `${amount.toFixed(2)} ${fromCurrency} = ${convertedAmount.toFixed(2)} ${toCurrency}`;
            conversionPreview.style.display = 'block';
        } else {
            conversionPreview.style.display = 'none';
        }
    }

    fromMethod.addEventListener('change', updateExchangeRateVisibility);
    toMethod.addEventListener('change', updateExchangeRateVisibility);
    transferAmount.addEventListener('input', updatePreview);
    exchangeRate.addEventListener('input', updatePreview);
</script>

<?php require_once '../templates/footer.php'; ?>