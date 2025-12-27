<?php
// =================================================================
// 🔑 db_config.php: Configuración de Conexión a MySQL con Logs
// =================================================================

// *** MODIFICA ESTOS VALORES CON TUS CREDENCIALES REALES ***
define('DB_HOST', 'localhost');
define('DB_NAME', 'menu_digital_db');
define('DB_USER', 'root'); // Asegúrate de que estos son correctos
define('DB_PASS', ''); // Asegúrate de que estos son correctos

function connectDB() {
    error_log("[DB_CONFIG] Intentando conectar a la base de datos: " . DB_NAME);
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        error_log("[DB_CONFIG] Conexión exitosa.");
        return $pdo;
    } catch (PDOException $e) {
        error_log("[DB_CONFIG] ERROR CRÍTICO DE CONEXIÓN: " . $e->getMessage());
        // Detiene la ejecución y envía un código 500
        http_response_code(500);
        die(json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos: ' . $e->getMessage()]));
    }
}
?>
