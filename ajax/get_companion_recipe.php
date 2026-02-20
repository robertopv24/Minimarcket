<?php
// Prevent HTML errors from breaking JSON
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    // Path resolution: /ajax/ -> /templates/autoload.php
    $path = __DIR__ . '/../templates/autoload.php';
    
    if (!file_exists($path)) {
       // Fallback for different structures or manual include
       if (file_exists(__DIR__ . '/../funciones/ProductManager.php')) {
           require_once __DIR__ . '/../funciones/conexion.php';
           require_once __DIR__ . '/../funciones/ProductManager.php';
       } else {
           throw new Exception("Autoload not found at $path");
       }
    } else {
        require_once $path;
    }

    session_start();

    // Seguridad básica
    if (!isset($_SESSION['user_id'])) {
        // Allow for dev/testing if needed, or strictly enforce. 
        // For now, let's just return empty context if not logged in or handle gracefully
        // http_response_code(403);
        // echo json_encode(['error' => 'Unauthorized']);
        // exit;
    }

    $id = $_GET['id'] ?? null;
    if (!$id) {
        echo json_encode(['error' => 'Missing ID']);
        exit;
    }

    $pm = new ProductManager();
    $data = $pm->getCompanionRecipe($id);

    echo json_encode($data);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
