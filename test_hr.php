<?php
require_once __DIR__ . '/templates/autoload.php';

use Minimarcket\Modules\HR\Services\PayrollService;
use Minimarcket\Modules\User\Services\UserService;
use Minimarcket\Core\Database\ConnectionManager;

echo "Testing HR Module...\n";
global $app;
$container = $app->getContainer();

try {
    $payrollService = $container->get(PayrollService::class);
    $userService = $container->get(UserService::class);
    $db = $container->get(ConnectionManager::class)->getConnection();

    // 1. Crear/Preparar Empleado de Prueba
    echo "Preparing Test Employee...\n";
    // 1. Crear/Preparar Empleado de Prueba
    echo "Preparing Test Employee...\n";
    // Buscamos cualquier usuario
    $users = $userService->getAllUsers();
    if (empty($users)) {
        echo "No users found. Creating one...\n";
        $userService->createUser("HR Test User", "hr@test.com", "password", "admin");
        $user = $userService->getUserByEmail("hr@test.com");
    } else {
        $user = $users[0];
    }

    if (!$user) {
        throw new Exception("Could not find or create a user.");
    }

    // Actualizamos salario para que cuente como empleado
    $stmt = $db->prepare("UPDATE users SET salary_amount = ?, salary_frequency = 'monthly', job_role = 'manager' WHERE id = ?");
    $stmt->execute([1500.00, $user['id']]);
    echo "User ID {$user['id']} setup as Employee (Salary: 1500)\n";

    // 2. Listar Empleados (Debe aparecer el admin)
    echo "Listing Employees...\n";
    $employees = $payrollService->getPayrollStatus();
    $found = false;
    foreach ($employees as $emp) {
        if ($emp['id'] == $user['id']) {
            $found = true;
            echo "Found Employee: {$emp['name']} - Status: {$emp['status']}\n";
            break;
        }
    }

    if (!$found) {
        echo "[FAIL] Test Employee not found in Payroll Status.\n";
        exit;
    } else {
        echo "[PASS] Employee List working.\n";
    }

    // 3. Registrar Pago
    echo "Registering Payment ($500)...\n";
    $result = $payrollService->registerPayment($user['id'], 500, "Pago Prueba Nómina", 1);

    if ($result) {
        echo "[PASS] Payment Registered.\n";
    } else {
        echo "[FAIL] Payment Registration Failed.\n";
        exit;
    }

    // 4. Verificar Historial
    echo "Verifying Payment History...\n";
    $employeesAfter = $payrollService->getPayrollStatus();
    foreach ($employeesAfter as $emp) {
        if ($emp['id'] == $user['id']) {
            echo "Last Payment Date: {$emp['last_payment_date']}\n";
            if ($emp['last_payment_date'] == date('Y-m-d')) {
                echo "[PASS] Last Payment Date Updated.\n";
            } else {
                echo "[FAIL] Last Payment Date incorrect.\n";
            }
        }
    }

} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
