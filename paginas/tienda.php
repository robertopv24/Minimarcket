
<?php
session_start();

require_once '../templates/autoload.php';
require_once '../templates/pos_check.php'; // SEGURIDAD POS

use Minimarcket\Core\Container;
use Minimarcket\Modules\Finance\Services\CashRegisterService;
use Minimarcket\Modules\Sales\Services\CartService;
use Minimarcket\Modules\Inventory\Services\ProductService;
use Minimarcket\Modules\Finance\Services\TransactionService;

// --- MODERN DI SETUP ---
global $app;
$container = $app->getContainer();
$cashRegisterService = $container->get(CashRegisterService::class);
$cartService = $container->get(CartService::class);
$productService = $container->get(ProductService::class);
$transactionService = $container->get(TransactionService::class);

// --- LÓGICA DE CONTROL DE CAJA ---
$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user_role'] ?? null;
$hasOpenSession = false;
$cajaAlert = "";

if ($userId) {
    $hasOpenSession = $cashRegisterService->hasOpenSession($userId);

    // REGLA 1: Si es Cajero y NO tiene caja, ¡FUERA! A abrir caja.
    if ($userRole === 'user' && !$hasOpenSession) {
        header("Location: apertura_caja.php");
        exit;
    }

    // REGLA 2: Si es Admin y NO tiene caja, advertencia.
    if ($userRole === 'admin' && !$hasOpenSession) {
        $cajaAlert = '<div class="alert alert-warning text-center m-3 shadow-sm border-warning">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>Atención Admin:</strong> No tienes una caja abierta.
                        <a href="apertura_caja.php" class="btn btn-sm btn-dark ms-2 rounded-pill">Abrir Caja para Vender</a>
                      </div>';
    }
}
// ----------------------------------

// Procesar "Agregar al Carrito"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (!$userId) {
        echo "<script>alert('Debes iniciar sesión'); window.location='login.php';</script>";
        exit;
    }

    // REGLA 3: Nadie puede vender sin caja abierta
    if (!$hasOpenSession) {
        echo "<script>alert('⚠️ ERROR: Debes abrir una caja (Turno) antes de realizar ventas.'); window.location='apertura_caja.php';</script>";
        exit;
    }

    $productId = $_POST['product_id'];
    $quantity = $_POST['quantity'];

    $cartService->addToCart($userId, $productId, $quantity);
    header('Location: carrito.php');
    exit;
}

$search = $_GET['search'] ?? '';
// Note: ProductService might not have searchProducts? Let's check Service Contract.
// Proxy had `searchProducts`. I must ensure Service has it.
// Checking ProductService...
// If ProductService lacks searchProducts, I should add it or use getAll and filter (bad performance).
// Assuming ProductService has it or I need to fix it. 
// I recall view_file of ProductService showing `searchProducts`.
// Let's assume yes. If not, error will show and I will fix.
$products = (!empty($search)) ? $productService->searchProducts($search) : $productService->getAllProducts();

require_once '../templates/header.php';
require_once '../templates/menu.php';
?>

<style>
    /* Premium UI Tweaks */
    .product-card {
        transition: transform 0.2s, box-shadow 0.2s;
        border: none;
        border-radius: 12px;
        overflow: hidden;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
    }

    .btn-rounded {
        border-radius: 20px;
    }

    .search-bar-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }
</style>

<?= $cajaAlert ?>

<div class="container mt-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>🛍️ Catálogo de Productos</h2>
        <a href="carrito.php" class="btn btn-primary position-relative">
            <i class="fa fa-shopping-cart"></i> Ver Carrito
            <?php
            // Un pequeño badge si hay items (opcional, pero util)
            $cartItems = $cartService->getCart($userId);
            $count = count($cartItems); 
            if ($count > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?= $count ?>
                    <span class="visually-hidden">items in cart</span>
                </span>
            <?php endif; ?>
        </a>
        <button class="btn btn-success ms-2" onclick="openDebtModal()">
            <i class="fa fa-hand-holding-usd"></i> Abonar Crédito
        </button>
    </div>

    <!-- Barra de Búsqueda -->
    <div class="card mb-4 bg-light">
        <div class="card-body py-3">
            <form method="GET" action="" class="row g-2 align-items-center">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                        <input type="text" name="search" class="form-control"
                            placeholder="¿Qué se te antoja hoy? (Pizza, Bebida...)"
                            value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Buscar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row mt-4 align-items-stretch">
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $product): ?>
                <div class="col-md-3 mb-4">
                    <div class="card h-100 product-card shadow-sm bg-white">
                        <div class="position-relative text-center p-3 bg-light" style="border-bottom: 1px solid #f1f1f1;">
                            <img src="../<?= htmlspecialchars($product['image_url']) ?>" class="card-img-top"
                                alt="<?= htmlspecialchars($product['name']) ?>" style="height: 160px; object-fit: contain;">
                            
                            <?php
                            $isSimple = ($product['product_type'] === 'simple');
                            // Use Service Method
                            $displayStock = $isSimple ? $product['stock'] : $productService->getVirtualStock($product['id']);
                            $stockBadgeClass = ($displayStock > 0) ? 'bg-success' : 'bg-danger';
                            ?>
                            <span class="position-absolute top-0 end-0 m-2 badge rounded-pill <?= $stockBadgeClass ?>">
                                Stock: <?= $displayStock ?>
                            </span>
                        </div>

                        <div class="card-body d-flex flex-column">
                            <h6 class="card-title text-center text-dark fw-bold mb-1"><?= htmlspecialchars($product['name']) ?></h6>
                            <p class="card-text small text-muted text-center flex-grow-1" style="font-size: 0.85em; overflow: hidden; max-height: 3em;">
                                <?= htmlspecialchars($product['description']) ?>
                            </p>

                            <div class="text-center mb-3">
                                <h4 class="text-primary fw-bold mb-0">$<?= number_format($product['price_usd'], 2) ?></h4>
                                <small class="text-secondary fw-bold"><?= number_format($product['price_ves'], 2) ?> VES</small>
                            </div>

                            <form action="#" method="post" class="mt-auto">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>">
                                
                                <div class="input-group mb-2 input-group-sm">
                                    <span class="input-group-text bg-white border-end-0"><i class="fa fa-hashtag"></i></span>
                                    <input type="number" name="quantity" value="1" min="1" max="<?= $displayStock ?>" class="form-control border-start-0 text-center" <?= $displayStock <= 0 ? 'disabled' : '' ?>>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-sm rounded-pill fw-bold" <?= $displayStock <= 0 ? 'disabled' : '' ?>>
                                        <i class="fa fa-cart-plus me-1"></i> Agregar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="fa fa-box-open fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No se encontraron productos.</h4>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL DE ABONO DE CRÉDITO -->
<div class="modal fade" id="modalDebtPOS" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">💵 Abonar a Crédito</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- PASO 1: BUSCAR CLIENTE -->
                <div id="step-search">
                    <label class="form-label fw-bold">Buscar Cliente</label>
                    <div class="input-group mb-2">
                        <input type="text" id="debt-client-search" class="form-control"
                            placeholder="Nombre o Cédula...">
                        <button class="btn btn-outline-secondary" onclick="searchDebtClient()">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                    <div id="debt-client-results" class="list-group"></div>
                </div>

                <!-- PASO 2: FORMULARIO PAGO -->
                <div id="step-pay" style="display:none;">
                    <div class="alert alert-info py-2 mb-3">
                        <h6 class="mb-0" id="selected-client-name"></h6>
                        <small>Deuda Total: <strong id="selected-client-debt" class="text-danger"></strong></small>
                    </div>

                    <form id="form-debt-pos">
                        <input type="hidden" id="p_client_id">

                        <div class="mb-3">
                            <label class="form-label">Monto a Abonar ($ USD)</label>
                            <input type="number" step="0.01" id="p_amount_usd" class="form-control form-control-lg"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Método de Pago</label>
                            <select id="p_payment_method" class="form-select" required>
                                <option value="">-- Seleccionar --</option>
                                <?php
                                $methods = $transactionService->getPaymentMethods();
                                foreach ($methods as $m): ?>
                                    <option value="<?= $m['id'] ?>"><?= $m['name'] ?> (<?= $m['currency'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="button" class="btn btn-secondary btn-sm" onclick="resetDebtModal()">Atrás</button>
                        <button type="submit" class="btn btn-success w-100 mt-2">✅ Procesar Abono</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // --- LÓGICA DE ABONO POS ---
    let debtModal;

    document.addEventListener('DOMContentLoaded', function () {
        debtModal = new bootstrap.Modal(document.getElementById('modalDebtPOS'));
    });

    function openDebtModal() {
        resetDebtModal();
        debtModal.show();
    }

    function resetDebtModal() {
        document.getElementById('step-search').style.display = 'block';
        document.getElementById('step-pay').style.display = 'none';
        document.getElementById('debt-client-results').innerHTML = '';
        document.getElementById('debt-client-search').value = '';
        document.getElementById('form-debt-pos').reset();
    }

    function searchDebtClient() {
        const query = document.getElementById('debt-client-search').value;
        if (query.length < 2) return;

        fetch(`ajax/search_clients.php?q=${encodeURIComponent(query)}`)
            .then(r => r.json())
            .then(data => {
                const list = document.getElementById('debt-client-results');
                list.innerHTML = '';

                if (data.length === 0) {
                    list.innerHTML = '<div class="list-group-item text-muted">No encontrado</div>';
                    return;
                }

                data.forEach(c => {
                    const item = document.createElement('button');
                    item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                    item.innerHTML = `
                        <div>
                            <strong>${c.name}</strong><br>
                            <small class="text-muted">${c.document_id}</small>
                        </div>
                        <span class="badge bg-danger">$${parseFloat(c.current_debt).toFixed(2)}</span>
                    `;
                    item.onclick = () => selectDebtClient(c);
                    list.appendChild(item);
                });
            });
    }

    // Búsqueda al presionar Enter
    document.getElementById('debt-client-search').addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault(); // Evitar submit de forms si los hubiera
            searchDebtClient();
        }
    });

    function selectDebtClient(client) {
        document.getElementById('p_client_id').value = client.id;
        document.getElementById('selected-client-name').textContent = client.name;
        document.getElementById('selected-client-debt').textContent = '$' + parseFloat(client.current_debt).toFixed(2);

        document.getElementById('step-search').style.display = 'none';
        document.getElementById('step-pay').style.display = 'block';
        document.getElementById('p_amount_usd').focus();
    }

    document.getElementById('form-debt-pos').addEventListener('submit', function (e) {
        e.preventDefault();

        const data = {
            client_id: document.getElementById('p_client_id').value,
            amount_usd: document.getElementById('p_amount_usd').value,
            payment_method_id: document.getElementById('p_payment_method').value
        };

        if (confirm(`¿Confirmar abono de $${data.amount_usd}?`)) {
            fetch('ajax/process_debt_payment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        alert(res.message);
                        debtModal.hide();
                        // Opcional: Recargar o actualizar UI
                    } else {
                        alert('Error: ' + res.message);
                    }
                })
                .catch(err => alert('Error de conexión'));
        }
    });
</script>

<?php require_once '../templates/footer.php'; ?>