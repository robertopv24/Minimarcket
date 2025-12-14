<?php
// session_start();
require_once '../templates/autoload.php';
require_once '../templates/pos_check.php'; // SEGURIDAD POS

use Minimarcket\Core\Container;
use Minimarcket\Modules\Finance\Services\CashRegisterService;
use Minimarcket\Modules\Sales\Services\CartService;
use Minimarcket\Core\Config\ConfigService;
use Minimarcket\Modules\Finance\Services\TransactionService;

$container = Container::getInstance();
$cashRegisterService = $container->get(CashRegisterService::class);
$cartService = $container->get(CartService::class);
$configService = $container->get(ConfigService::class);
$transactionService = $container->get(TransactionService::class);

// 1. Validar Sesión de Caja
$userId = $_SESSION['user_id'] ?? null;
if (!$userId || !$cashRegisterService->hasOpenSession($userId)) {
    header("Location: apertura_caja.php");
    exit;
}

// 2. Obtener carrito
$cartItems = $cartService->getCart($userId);
if (empty($cartItems)) {
    header("Location: tienda.php");
    exit;
}

$totals = $cartService->calculateTotal($cartItems);
$totalUsd = $totals['total_usd'];
$rate = $configService->get('exchange_rate');
$methods = $transactionService->getPaymentMethods();

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<div class="container mt-4 mb-5">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fa fa-list-alt me-2"></i>Resumen</h5>
                </div>
                <div class="list-group list-group-flush" style="max-height: 400px; overflow-y: auto;">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-start py-2">
                            <div class="me-auto">
                                <div class="fw-bold small"><?= htmlspecialchars($item['name']) ?> x<?= $item['quantity'] ?>
                                </div>
                                <?php
                                $grouped = $item['modifiers_grouped'] ?? [];
                                if (!empty($grouped)) {
                                    foreach ($grouped as $idx => $data) {
                                        $icon = ($data['is_takeaway'] == 1) ? '🥡' : '🍽️';
                                        echo "<div class='text-muted' style='font-size:0.75em'>$icon #" . ($idx + 1) . "</div>";
                                    }
                                }
                                ?>
                            </div>
                            <span class="fw-bold small">$<?= number_format($item['total_price'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fs-5">Total:</span>
                        <div class="text-end">
                            <div class="fs-4 fw-bold text-success">$<?= number_format($totalUsd, 2) ?></div>
                            <div class="small text-muted"><?= number_format($totalUsd * $rate, 2) ?> Bs</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-grid mt-3">
                <a href="carrito.php" class="btn btn-outline-secondary">⬅ Volver al Carrito</a>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow border-primary">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fa fa-cash-register me-2"></i>Caja</h4>
                </div>
                <div class="card-body">
                    <form id="checkoutForm" action="process_checkout.php" method="POST">

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="customer_name"
                                    placeholder="Nombre Cliente (Opcional)">
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="shipping_address" value="Tienda Física"
                                    placeholder="Nota/Mesa">
                            </div>
                        </div>

                        <hr>

                        <h6 class="text-primary mb-3">Ingrese Montos Recibidos:</h6>
                        <div class="row g-3">
                            <?php foreach ($methods as $method): ?>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text w-50 small" style="font-size: 0.85rem;">
                                            <?= $method['name'] ?>
                                        </span>
                                        <input type="number" step="0.01" class="form-control payment-input fw-bold text-end"
                                            name="payments[<?= $method['id'] ?>]" data-currency="<?= $method['currency'] ?>"
                                            placeholder="0.00">
                                        <span class="input-group-text"><?= $method['currency'] ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-4 p-3 rounded border" style="background-color: #f8f9fa;">
                            <div class="row text-center align-items-center">
                                <div class="col-md-4 border-end">
                                    <small class="text-muted d-block text-uppercase fw-bold">Total Recibido</small>
                                    <div id="paidUsd" class="fs-4 fw-bold text-primary">$0.00</div>
                                    <div id="paidVes" class="small text-muted">0.00 Bs</div>
                                </div>

                                <div class="col-md-4 border-end" id="colRemaining">
                                    <small class="text-muted d-block text-uppercase fw-bold">Falta por Pagar</small>
                                    <div id="remainUsd" class="fs-4 fw-bold text-danger">
                                        $<?= number_format($totalUsd, 2) ?></div>
                                    <div id="remainVes" class="small text-danger">
                                        <?= number_format($totalUsd * $rate, 2) ?> Bs
                                    </div>
                                </div>

                                <div class="col-md-4" id="colChange" style="opacity: 0.5;">
                                    <small class="text-muted d-block text-uppercase fw-bold">Su Cambio</small>
                                    <div id="changeUsd" class="fs-4 fw-bold text-success">$0.00</div>
                                    <div id="changeVes" class="small text-success fw-bold">0.00 Bs</div>
                                </div>
                            </div>
                        </div>

                        <div id="changeMethodContainer"
                            class="mt-3 p-3 bg-warning bg-opacity-10 border border-warning rounded"
                            style="display:none;">
                            <label class="form-label fw-bold text-dark"><i class="fa fa-hand-holding-usd"></i> ¿Cómo
                                entregas el vuelto?</label>
                            <select name="change_method_id" class="form-select border-warning">
                                <option value="">Seleccione origen del dinero...</option>
                                <?php foreach ($methods as $m):
                                    // Solo mostramos métodos de Efectivo o Pago Móvil para dar vuelto
                                    // Asumimos que Zelle no se usa para dar vuelto comúnmente, pero lo dejamos abierto.
                                    ?>
                                    <option value="<?= $m['id'] ?>">
                                        Entregar en <?= $m['name'] ?> (<?= $m['currency'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text text-muted">El sistema registrará la salida de dinero de esta cuenta.
                            </div>
                        </div>

                        <!-- SECCIÓN CRÉDITO Y BENEFICIOS -->
                        <div class="mt-4">
                            <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal"
                                data-bs-target="#modalCredit">
                                <i class="fa fa-handshake me-2"></i> Procesar Crédito o Beneficio
                            </button>
                        </div>

                        <!-- Inputs Ocultos para Crédito -->
                        <input type="hidden" name="is_credit" id="is_credit" value="0">
                        <input type="hidden" name="credit_client_id" id="credit_client_id">
                        <input type="hidden" name="credit_employee_id" id="credit_employee_id">
                        <input type="hidden" name="credit_type" id="credit_type" value="">
                        <!-- 'client_credit', 'employee_credit', 'benefit' -->
                        <input type="hidden" name="admin_password" id="admin_password_input">

                        <div class="d-grid mt-4">
                            <button class="btn btn-secondary btn-lg py-3" type="submit" id="btnSubmit" disabled>
                                <i class="fa fa-lock me-2"></i> Complete el Pago
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CRÉDITOS -->
<div class="modal fade" id="modalCredit" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fa fa-file-invoice-dollar"></i> Crédito / Beneficio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs mb-3" id="creditTabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#tabClient"
                            onclick="setCreditType('client_credit')">
                            <i class="fa fa-user"></i> Cliente Externo
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tabEmployee"
                            onclick="setCreditType('employee_credit')">
                            <i class="fa fa-id-badge"></i> Empleado (Nómina)
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Tab Cliente -->
                    <div class="tab-pane fade show active" id="tabClient">
                        <div class="mb-3 position-relative">
                            <label class="form-label">Buscar Cliente:</label>
                            <input type="text" class="form-control" id="searchClientInput"
                                placeholder="Nombre o Cédula...">
                            <div class="form-text">Escriba al menos 2 caracteres y seleccione de la lista</div>
                            <div class="list-group mt-2" id="clientResults"
                                style="position: absolute; z-index: 1050; max-height: 200px; overflow-y: auto; display: none; width: 100%; box-shadow: 0 4px 6px rgba(0,0,0,0.3);">
                            </div>
                        </div>
                        <div id="selectedClientInfo" class="alert alert-info d-none">
                            <strong>Cliente:</strong> <span id="selClientName"></span><br>
                            <small>Límite: $<span id="selClientLimit"></span> | Deuda: $<span
                                    id="selClientDebt"></span></small>
                        </div>
                    </div>

                    <!-- Tab Empleado -->
                    <div class="tab-pane fade" id="tabEmployee">
                        <div class="mb-3 position-relative">
                            <label class="form-label">Buscar Empleado:</label>
                            <input type="text" class="form-control" id="searchEmpInput" placeholder="Nombre...">
                            <div class="form-text">Escriba al menos 2 caracteres y seleccione de la lista</div>
                            <div class="list-group mt-2" id="empResults"
                                style="position: absolute; z-index: 1050; max-height: 200px; overflow-y: auto; display: none; width: 100%; box-shadow: 0 4px 6px rgba(0,0,0,0.3);">
                            </div>
                        </div>
                        <div id="selectedEmpInfo" class="alert alert-warning d-none">
                            <strong>Empleado:</strong> <span id="selEmpName"></span><br>
                            <small class="text-muted">Rol: <span id="selEmpRole"></span></small>
                        </div>

                        <div class="mb-3 form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" id="chkBenefit"
                                onchange="toggleBenefit(this)">
                            <label class="form-check-label fw-bold" for="chkBenefit">Es Beneficio de Empresa (Sin
                                cobro)</label>
                            <div class="form-text">Si se activa, la cuenta la paga la empresa como Gasto Operativo.
                            </div>
                        </div>
                    </div>
                </div>

                <hr>
                <!-- AUTORIZACIÓN ADMIN -->
                <div class="bg-light p-3 rounded border">
                    <label class="form-label fw-bold text-danger"><i class="fa fa-user-shield"></i> Autorización de
                        Supervisor</label>
                    <input type="password" class="form-control" id="modalAdminPass"
                        placeholder="Ingrese Contraseña Admin">
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="applyCredit()">Aplicar Cargo</button>
            </div>
        </div>
    </div>
</div>

<script>
    const totalOrderUsd = <?= $totalUsd ?>;
    const rate = <?= $rate ?>;
    const inputs = document.querySelectorAll('.payment-input');
    const btnSubmit = document.getElementById('btnSubmit');
    const divChangeMethod = document.getElementById('changeMethodContainer');
    const selectChangeMethod = document.querySelector('select[name="change_method_id"]');

    // Credit Logic Vars
    let selectedClientId = null;
    let selectedEmpId = null;
    let currentCreditType = 'client_credit';

    function calculate() {
        let paidUsd = 0;

        // 1. Sumar Pagos (Convirtiendo todo a USD base)
        inputs.forEach(input => {
            let val = parseFloat(input.value) || 0;
            if (input.dataset.currency === 'VES') {
                paidUsd += (val / rate);
            } else {
                paidUsd += val;
            }
        });

        // 2. Calcular Diferencia
        let diff = totalOrderUsd - paidUsd;
        let epsilon = 0.001;
        let isCredit = document.getElementById('is_credit').value == '1';

        // Si es credito, forzamos "Pagado" visualmente
        if (isCredit) {
            paidUsd = totalOrderUsd;
            diff = 0;
        }

        // 3. Actualizar Visuales (Pagado)
        document.getElementById('paidUsd').textContent = '$' + paidUsd.toFixed(2);
        document.getElementById('paidVes').textContent = (paidUsd * rate).toLocaleString('es-VE', { minimumFractionDigits: 2 }) + ' Bs';

        // 4. Lógica de Estados
        if (diff > epsilon && !isCredit) {
            // AÚN FALTA DINERO
            document.getElementById('remainUsd').textContent = '$' + diff.toFixed(2);
            document.getElementById('remainVes').textContent = (diff * rate).toLocaleString('es-VE', { minimumFractionDigits: 2 }) + ' Bs';

            document.getElementById('changeUsd').textContent = "$0.00";
            document.getElementById('changeVes').textContent = "0.00 Bs";

            // Estilos
            document.getElementById('colRemaining').style.opacity = "1";
            document.getElementById('colChange').style.opacity = "0.3";
            divChangeMethod.style.display = "none";
            selectChangeMethod.required = false;

            // Botón bloqueado
            btnSubmit.disabled = true;
            btnSubmit.className = "btn btn-secondary btn-lg py-3";
            btnSubmit.innerHTML = '<i class="fa fa-lock me-2"></i> Complete el Pago';

        } else {
            // PAGO COMPLETO O SOBRANTE (VUELTO) O CRÉDITO
            let change = Math.abs(diff);

            document.getElementById('remainUsd').textContent = "$0.00";
            document.getElementById('remainVes').textContent = "0.00 Bs";

            document.getElementById('changeUsd').textContent = '$' + change.toFixed(2);
            document.getElementById('changeVes').textContent = (change * rate).toLocaleString('es-VE', { minimumFractionDigits: 2 }) + ' Bs';

            // Estilos
            document.getElementById('colRemaining').style.opacity = "0.3";
            document.getElementById('colChange').style.opacity = "1";

            // Activar botón
            btnSubmit.disabled = false;

            if (isCredit) {
                btnSubmit.className = "btn btn-info btn-lg py-3 shadow text-white";
                let typeLabel = (document.getElementById('chkBenefit').checked && currentCreditType === 'employee_credit') ? 'BENEFICIO' : 'CRÉDITO';
                btnSubmit.innerHTML = `<i class="fa fa-file-signature me-2"></i> <strong>CONFIRMAR ${typeLabel}</strong>`;
                divChangeMethod.style.display = "none";
                selectChangeMethod.required = false;
            } else {
                btnSubmit.className = "btn btn-success btn-lg py-3 shadow";
                btnSubmit.innerHTML = '<i class="fa fa-check-circle me-2"></i> <strong>CONFIRMAR VENTA</strong>';

                // Lógica de Vuelto
                if (change > 0.01) {
                    divChangeMethod.style.display = "block";
                    selectChangeMethod.required = true;
                } else {
                    divChangeMethod.style.display = "none";
                    selectChangeMethod.required = false;
                }
            }
        }
    }

    inputs.forEach(input => {
        input.addEventListener('input', calculate);
        input.addEventListener('keyup', calculate);
    });

    // --- CREDIT MODAL LOGIC ---

    function setCreditType(type) {
        currentCreditType = type;
        // Reset selections logic if needed
    }

    function toggleBenefit(chk) {
        if (chk.checked) {
            currentCreditType = 'benefit';
        } else {
            currentCreditType = 'employee_credit';
        }
    }

    // Client Search
    const searchClientInput = document.getElementById('searchClientInput');
    const clientResultsDiv = document.getElementById('clientResults');
    const selectedClientInfo = document.getElementById('selectedClientInfo');

    searchClientInput.addEventListener('input', function () {
        // Clear selection when user types
        selectedClientId = null;
        selectedClientInfo.classList.add('d-none');

        let q = this.value.trim();
        if (q.length < 2) {
            clientResultsDiv.style.display = 'none';
            return;
        }

        fetch('ajax/search_clients.php?q=' + encodeURIComponent(q), {
            credentials: 'same-origin'
        })
            .then(r => {
                console.log('Response status:', r.status);
                if (!r.ok) {
                    throw new Error('HTTP error ' + r.status);
                }
                return r.json();
            })
            .then(data => {
                console.log('Client search results:', data);
                let html = '';
                if (data.error) {
                    html = '<div class="list-group-item text-danger">Error: ' + data.error + '</div>';
                } else if (data.length === 0) {
                    html = '<div class="list-group-item text-muted">No se encontraron clientes</div>';
                } else {
                    data.forEach(c => {
                        const escapedName = c.name.replace(/'/g, "\\'");
                        html += `<a href="#" class="list-group-item list-group-item-action" style="cursor: pointer;" onclick="event.preventDefault(); selectClient(${c.id}, '${escapedName}', ${c.credit_limit}, ${c.current_debt})">
                            <strong>${c.name}</strong><br>
                            <small class="text-muted">${c.document_id || 'Sin documento'} | Límite: $${c.credit_limit} | Deuda: $${c.current_debt}</small>
                        </a>`;
                    });
                }
                clientResultsDiv.innerHTML = html;
                clientResultsDiv.style.display = 'block';
            })
            .catch(err => {
                console.error('Error searching clients:', err);
                clientResultsDiv.innerHTML = '<div class="list-group-item text-danger">Error: ' + err.message + '</div>';
                clientResultsDiv.style.display = 'block';
            });
    });

    function selectClient(id, name, limit, debt) {
        selectedClientId = id;
        document.getElementById('selClientName').innerText = name;
        document.getElementById('selClientLimit').innerText = limit;
        document.getElementById('selClientDebt').innerText = debt;

        selectedClientInfo.classList.remove('d-none');
        clientResultsDiv.style.display = 'none';
        searchClientInput.value = name;
        searchClientInput.classList.add('is-valid');
        searchClientInput.classList.remove('is-invalid');
    }

    // Employee Search
    const searchEmpInput = document.getElementById('searchEmpInput');
    const empResultsDiv = document.getElementById('empResults');
    const selectedEmpInfo = document.getElementById('selectedEmpInfo');

    searchEmpInput.addEventListener('input', function () {
        // Clear selection when user types
        selectedEmpId = null;
        selectedEmpInfo.classList.add('d-none');

        let q = this.value.trim();
        if (q.length < 2) {
            empResultsDiv.style.display = 'none';
            return;
        }

        fetch('ajax/search_employees.php?q=' + encodeURIComponent(q), {
            credentials: 'same-origin'
        })
            .then(r => r.json())
            .then(data => {
                let html = '';
                if (data.length === 0) {
                    html = '<div class="list-group-item text-muted">No se encontraron empleados</div>';
                } else {
                    data.forEach(u => {
                        const escapedName = u.name.replace(/'/g, "\\'");
                        const escapedRole = (u.job_role || '').replace(/'/g, "\\'");
                        html += `<a href="#" class="list-group-item list-group-item-action" style="cursor: pointer;" onclick="event.preventDefault(); selectEmp(${u.id}, '${escapedName}', '${escapedRole}')">
                            <strong>${u.name}</strong><br>
                            <small class="text-muted">Rol: ${u.job_role || 'N/A'}</small>
                        </a>`;
                    });
                }
                empResultsDiv.innerHTML = html;
                empResultsDiv.style.display = 'block';
            })
            .catch(err => {
                console.error('Error searching employees:', err);
                empResultsDiv.innerHTML = '<div class="list-group-item text-danger">Error al buscar</div>';
                empResultsDiv.style.display = 'block';
            });
    });

    function selectEmp(id, name, role) {
        selectedEmpId = id;
        document.getElementById('selEmpName').innerText = name;
        document.getElementById('selEmpRole').innerText = role;

        selectedEmpInfo.classList.remove('d-none');
        empResultsDiv.style.display = 'none';
        searchEmpInput.value = name;
        searchEmpInput.classList.add('is-valid');
        searchEmpInput.classList.remove('is-invalid');
    }

    function applyCredit() {
        const pass = document.getElementById('modalAdminPass').value;
        if (!pass) {
            alert("Debe ingresar la contraseña de Administrador.");
            return;
        }

        if (currentCreditType === 'client_credit' && !selectedClientId) {
            alert("Debe seleccionar un cliente de la lista de resultados.");
            searchClientInput.classList.add('is-invalid');
            searchClientInput.focus();
            return;
        }
        if ((currentCreditType === 'employee_credit' || currentCreditType === 'benefit') && !selectedEmpId) {
            alert("Debe seleccionar un empleado de la lista de resultados.");
            searchEmpInput.classList.add('is-invalid');
            searchEmpInput.focus();
            return;
        }

        // Fill Hidden Fields
        document.getElementById('is_credit').value = '1';
        document.getElementById('credit_client_id').value = selectedClientId || '';
        document.getElementById('credit_employee_id').value = selectedEmpId || '';
        document.getElementById('credit_type').value = currentCreditType;
        document.getElementById('admin_password_input').value = pass;

        // Hide Modal
        var myModalEl = document.getElementById('modalCredit');
        var modal = bootstrap.Modal.getInstance(myModalEl);
        modal.hide();

        // Disable Payment Inputs to avoid confusion
        inputs.forEach(i => i.disabled = true);

        // Recalculate to update button
        calculate();

        // Auto-scroll to button
        btnSubmit.scrollIntoView();
        alert("Modo Crédito activado. Presione CONFIRMAR para finalizar.");
    }
</script>

<?php require_once '../templates/footer.php'; ?>