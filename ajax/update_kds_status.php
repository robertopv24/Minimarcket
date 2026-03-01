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
        // 1. Marcar esta estación como en preparación o lista
        if ($status === 'preparing') {
            $col = ($station == 'pizza') ? 'kds_pizza_preparing' : 'kds_kitchen_preparing';
            $stmt = $db->prepare("UPDATE orders SET $col = 1 WHERE id = ?");
            $stmt->execute([$orderId]);
            $orderManager->logStatusMilestone($orderId, $station, 'preparing');
        } else {
            // Si estamos marcando como READY, verificamos si ya se marcó como PREPARING
            // Si no, lo hacemos automáticamente para no perder la métrica de inicio.
            $checkCol = ($station == 'pizza') ? 'kds_pizza_preparing' : 'kds_kitchen_preparing';
            $stmtCheck = $db->prepare("SELECT $checkCol FROM orders WHERE id = ?");
            $stmtCheck->execute([$orderId]);
            $isAlreadyPrep = $stmtCheck->fetchColumn();

            if (!$isAlreadyPrep) {
                $prepCol = ($station == 'pizza') ? 'kds_pizza_preparing' : 'kds_kitchen_preparing';
                $stmtPrep = $db->prepare("UPDATE orders SET $prepCol = 1 WHERE id = ?");
                $stmtPrep->execute([$orderId]);
                $orderManager->logStatusMilestone($orderId, $station, 'preparing');
            }

            $col = ($station == 'pizza') ? 'kds_pizza_ready' : 'kds_kitchen_ready';
            $stmt = $db->prepare("UPDATE orders SET $col = 1 WHERE id = ?");
            $stmt->execute([$orderId]);
            $orderManager->logStatusMilestone($orderId, $station, 'ready');
        }

        // 2. Verificar si el resto de las estaciones requeridas también terminaron
        // (Solo si estamos marcando como READY)
        if ($status === 'ready') {
            $items = $orderManager->getOrderItems($orderId);
            $allReady = true;

            $stmtO = $db->prepare("SELECT kds_kitchen_ready, kds_pizza_ready FROM orders WHERE id = ?");
            $stmtO->execute([$orderId]);
            $orderFlags = $stmtO->fetch();

            $requiresKitchen = false;
            $requiresPizza = false;

            foreach ($items as $it) {
                if ($it['product_type'] === 'compound') {
                    $comps = $productManager->getProductComponents($it['product_id']);
                    foreach ($comps as $c) {
                        $st = '';
                        if ($c['component_type'] == 'product') {
                            $compInfo = $productManager->getProductById($c['component_id']);
                            $st = strtolower($compInfo['category_station'] ?? $compInfo['kitchen_station'] ?? '');
                        } elseif ($c['component_type'] == 'manufactured') {
                            $stmtM = $db->prepare("SELECT kitchen_station FROM manufactured_products WHERE id = ?");
                            $stmtM->execute([$c['component_id']]);
                            $st = strtolower($stmtM->fetchColumn() ?: '');
                        }

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
        }
    } else {
        // Comportamiento manual o despacho
        $stmt = $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $orderId]);

        // Log generic system milestone
        $orderManager->logStatusMilestone($orderId, 'system', $status);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
