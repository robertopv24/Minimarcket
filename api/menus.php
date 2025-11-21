<?php
// menus.php - API de gestión de menús
header('Content-Type: application/json');
require_once '../clases/Database.php';
require_once '../clases/Menu.php';

$db = new Database();
$conn = $db->getConnection();
$menu = new Menu($conn);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            echo json_encode($menu->getMenuById($_GET['id']));
        } else {
            echo json_encode($menu->getAllMenus());
        }
        break;
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($menu->createMenu($data['title'], $data['url'], $data['type']));
        break;
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($menu->updateMenu($data['id'], $data['title'], $data['url'], $data['type']));
        break;
    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($menu->deleteMenu($data['id']));
        break;
    default:
        echo json_encode(['error' => 'Método no permitido']);
        break;
}
?>
