<?php
// productos.php - API de gestión de productos
header('Content-Type: application/json');
require_once '../clases/Database.php';
require_once '../clases/Product.php';

$db = new Database();
$conn = $db->getConnection();
$product = new Product($conn);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            echo json_encode($product->getProductById($_GET['id']));
        } else {
            echo json_encode($product->getAllProducts());
        }
        break;
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($product->createProduct($data['name'], $data['description'], $data['price_usd'], $data['stock']));
        break;
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($product->updateProduct($data['id'], $data['name'], $data['description'], $data['price_usd'], $data['stock']));
        break;
    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($product->deleteProduct($data['id']));
        break;
    default:
        echo json_encode(['error' => 'Método no permitido']);
        break;
}
?>
