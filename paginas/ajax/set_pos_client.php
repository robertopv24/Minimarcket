<?php
session_start();
require_once '../../templates/autoload.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Si envían data vacía o null, es para "deseleccionar" cliente
    if (!isset($data['client_id']) || empty($data['client_id'])) {
        unset($_SESSION['pos_client_id']);
        unset($_SESSION['pos_client_name']);
        echo json_encode(['success' => true, 'message' => 'Cliente desvinculado']);
        exit;
    }

    $clientId = $data['client_id'];
    // FIX: Usar CreditManager para Clientes, no UserManager (que es para empleados/admin)
    $client = $creditManager->getClientById($clientId);

    if ($client) {
        $_SESSION['pos_client_id'] = $client['id'];
        $_SESSION['pos_client_name'] = $client['name'];
        echo json_encode(['success' => true, 'message' => 'Cliente seleccionado']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
