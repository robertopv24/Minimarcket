<?php
// Blindaje contra basura en el buffer (Warnings/Notices)
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL); // Loguear todo internamente, pero no mostrar

session_start();
require_once '../../templates/autoload.php';

header('Content-Type: application/json');

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verificar permisos
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('No autorizado');
        }

        // Recibir datos JSON
        $inputRaw = file_get_contents('php://input');
        $data = json_decode($inputRaw, true);

        if (!$data) {
            throw new Exception('JSON inválido o vacío');
        }

        $name = trim($data['name'] ?? '');
        $docId = trim($data['document_id'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $address = trim($data['address'] ?? '');

        // Email opcional
        $email = trim($data['email'] ?? '');
        if (empty($email)) {
            $uniqueId = !empty($docId) ? $docId : time();
            $email = "cliente_{$uniqueId}@local.com";
        }

        if (empty($name) || empty($docId)) {
            throw new Exception('Nombre y Cédula son obligatorios');
        }

        // Insertar Cliente
        if (!isset($creditManager)) {
            throw new Exception('Error interno: CreditManager no cargado');
        }

        $newClientId = $creditManager->createClient($name, $docId, $phone, $email, $address, 0);

        if ($newClientId) {
            // Auto-seleccionar en sesión
            $_SESSION['pos_client_id'] = $newClientId;
            $_SESSION['pos_client_name'] = $name;

            $response = [
                'success' => true,
                'client' => [
                    'id' => $newClientId,
                    'text' => $name . ' (' . $docId . ')'
                ]
            ];
        } else {
            throw new Exception('No se pudo insertar el cliente (posible duplicado de cédula).');
        }

    } catch (Exception $e) {
        // Manejo de duplicados SQL
        $msg = $e->getMessage();
        if (strpos($msg, 'Duplicate entry') !== false) {
            $msg = 'Ya existe un cliente con esa Cédula o Email.';
        }
        $response = ['success' => false, 'error' => $msg];
    }

} else {
    $response = ['success' => false, 'error' => 'Método no permitido'];
}

// LIMPIEZA FINAL Y SALIDA
ob_clean(); // Borrar cualquier warning previo
echo json_encode($response);
exit;
