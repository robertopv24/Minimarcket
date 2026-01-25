<?php
// ajax/update_kds_status.php
require_once '../templates/autoload.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$orderId = $data['order_id'] ?? null;
$status = $data['status'] ?? 'ready';
$station = $data['station'] ?? null; // 'kitchen' o 'pizza'

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

try {
    if ($station) {
        // 1. Marcar esta estación como lista
        $col = ($station == 'pizza') ? 'kds_pizza_ready' : 'kds_kitchen_ready';
        $stmt = $db->prepare("UPDATE orders SET $col = 1 WHERE id = ?");
        $stmt->execute([$orderId]);

        // 2. Verificar si el resto de las estaciones requeridas también terminaron
        $items = $orderManager->getOrderItems($orderId);
        $allReady = true;

        // Consultamos FLAGS ACTUALES ya actualizados
        $stmtO = $db->prepare("SELECT kds_kitchen_ready, kds_pizza_ready FROM orders WHERE id = ?");
        $stmtO->execute([$orderId]);
        $orderFlags = $stmtO->fetch();

        // Detectamos qué estaciones REALMENTE requiere el pedido
        $requiresKitchen = false;
        $requiresPizza = false;

        foreach ($items as $it) {
            if ($it['product_type'] === 'compound') {
                $comps = $productManager->getProductComponents($it['product_id']);
                foreach ($comps as $c) {
                    $st = strtolower($c['category_station'] ?? $c['kitchen_station'] ?? '');
                    if ($st === 'kitchen')
                        $requiresKitchen = true;
                    if ($st === 'pizza')
                        $requiresPizza = true;
                }
            } else {
                $pInfo = $productManager->getProductById($it['product_id']);
                $st = strtolower($pInfo['category_station'] ?? $pInfo['kitchen_station'] ?? '');
                if ($st === 'kitchen')
                    $requiresKitchen = true;
                if ($st === 'pizza')
                    $requiresPizza = true;
            }
        }

        if ($requiresKitchen && !$orderFlags['kds_kitchen_ready'])
            $allReady = false;
        if ($requiresPizza && !$orderFlags['kds_pizza_ready'])
            $allReady = false;

        if ($allReady) {
            $stmtGlobal = $db->prepare("UPDATE orders SET status = 'ready', updated_at = NOW() WHERE id = ?");
            $stmtGlobal->execute([$orderId]);
        }
    } else {
        // Comportamiento anterior (Despacho marcando todo listo manualmente)
        $stmt = $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $orderId]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
