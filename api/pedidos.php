<?php
// pedidos.php - API de gestión de pedidos
header('Content-Type: application/json');
require_once '../clases/Database.php';
require_once '../clases/Order.php';

$db = new Database();
$conn = $db->getConnection();
$order = new Order($conn);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            echo json_encode($order->getOrderById($_GET['id']));
        } else {
            echo json_encode($order->getAllOrders());
        }
        break;
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($order->createOrder($data['user_id'], $data['total_price_usd'], $data['total_price_ves'], $data['status']));
        break;
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($order->updateOrder($data['id'], $data['status']));
        break;
    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($order->deleteOrder($data['id']));
        break;
    default:
        echo json_encode(['error' => 'Método no permitido']);
        break;
}
?>
