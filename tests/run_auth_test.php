<?php
require_once __DIR__ . '/../templates/autoload.php';
require_once __DIR__ . '/SimpleTest.php';

echo "=== TEST DE INTEGRACIÓN: USUARIOS Y AUTH ===\n";

try {
    // 1. Crear Usuario
    $uniqueId = uniqid();
    $username = "UserTest_$uniqueId";
    $email = "auth_test_$uniqueId@example.com";
    $password = "Secret123!";
    $docId = "V-" . $uniqueId;

    echo "1. Creando usuario '$username' ($email)...\n";
    $res = $userManager->createUser($username, $email, $password, "555-0000", $docId, "Test Address", "user");

    // Validar creación exitosa (createUser no retorna ID en esta version, verificamos por email)
    $user = $userManager->getUserByEmail($email);
    SimpleTest::assertNotNull($user, "El usuario debió crearse correctamente.");
    SimpleTest::assertEquals($username, $user['name'], "El nombre de usuario coincide.");
    echo "   ✅ Usuario creado con ID: " . $user['id'] . "\n";

    // 2. Probar Login (Password Hash Check)
    echo "2. Probando autenticación...\n";
    $loginUser = $userManager->login($email, $password);
    SimpleTest::assertNotNull($loginUser, "Login exitoso con credenciales correctas.");
    SimpleTest::assertEquals($user['id'], $loginUser['id'], "El ID del usuario logueado coincide.");
    echo "   ✅ Login exitoso.\n";

    // 3. Probar Login Fallido
    echo "3. Probando credenciales inválidas...\n";
    $badLogin = $userManager->login($email, "WrongPass");
    if ($badLogin === false) {
        echo "   ✅ Login rechazado correctamente con contraseña incorrecta.\n";
    } else {
        echo "   ❌ ERROR: Se permitió login con contraseña incorrecta.\n";
        exit(1);
    }

    // 4. Actualizar Usuario
    echo "4. Actualizando rol a 'admin'...\n";
    $userManager->updateUser($user['id'], $username, $email, "admin");
    $updatedUser = $userManager->getUserById($user['id']);
    SimpleTest::assertEquals('admin', $updatedUser['role'], "El rol se actualizó a admin.");
    echo "   ✅ Actualización verificada.\n";

    // 5. Eliminar Usuario
    echo "5. Eliminando usuario...\n";
    $userManager->deleteUser($user['id']);
    $deletedUser = $userManager->getUserById($user['id']);
    if (!$deletedUser) {
        echo "   ✅ Usuario eliminado correctamente.\n";
    } else {
        echo "   ❌ ERROR: El usuario sigue existiendo tras deleteUser.\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "❌ Excepción: " . $e->getMessage() . "\n";
    exit(1);
}
?>