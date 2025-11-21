<?php
// usuarios.php - API de gestión de usuarios
header('Content-Type: application/json');
require_once '../clases/Database.php';
require_once '../clases/User.php';

$db = new Database();
$conn = $db->getConnection();
$user = new User($conn);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            echo json_encode($user->getUserById($_GET['id']));
        } else {
            echo json_encode($user->getAllUsers());
        }
        break;
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($user->createUser($data['name'], $data['email'], password_hash($data['password'], PASSWORD_DEFAULT), $data['role']));
        break;
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($user->updateUser($data['id'], $data['name'], $data['email'], $data['role']));
        break;
    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($user->deleteUser($data['id']));
        break;
    default:
        echo json_encode(['error' => 'Método no permitido']);
        break;
}
?>
