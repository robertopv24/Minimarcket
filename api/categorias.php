<?php
// categorias.php - API de gestión de categorías
header('Content-Type: application/json');
require_once '../clases/Database.php';
require_once '../clases/Category.php';

$db = new Database();
$conn = $db->getConnection();
$category = new Category($conn);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            echo json_encode($category->getCategoryById($_GET['id']));
        } else {
            echo json_encode($category->getAllCategories());
        }
        break;
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($category->createCategory($data['name']));
        break;
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($category->updateCategory($data['id'], $data['name']));
        break;
    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($category->deleteCategory($data['id']));
        break;
    default:
        echo json_encode(['error' => 'Método no permitido']);
        break;
}
?>
