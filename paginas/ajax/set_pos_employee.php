<?php
session_start();
require_once '../../templates/autoload.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Si envían data vacía o null, es para "deseleccionar" empleado
    if (!isset($data['employee_id']) || empty($data['employee_id'])) {
        unset($_SESSION['pos_employee_id']);
        unset($_SESSION['pos_employee_name']);
        echo json_encode(['success' => true, 'message' => 'Empleado desvinculado']);
        exit;
    }

    $empId = $data['employee_id'];
    $user = $userManager->getUserById($empId);

    if ($user) {
        $_SESSION['pos_employee_id'] = $user['id'];
        $_SESSION['pos_employee_name'] = $user['name'];
        // Limpiar cliente si seleccionamos empleado (o viceversa, según lógica de negocio)
        // Por ahora permitimos ambos, pero el checkout priorizará.
        echo json_encode([
            'success' => true,
            'message' => 'Empleado seleccionado',
            'employee' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'job_role' => $user['job_role'] ?? 'Empleado'
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Empleado no encontrado']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
