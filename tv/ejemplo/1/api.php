<?php
include 'db_config.php';

header('Content-Type: application/json');
$pdo = connectDB();
$method = $_SERVER['REQUEST_METHOD'];

// --- LECTURA (GET) ---
if ($method === 'GET') {
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        if (isset($settings['chef_suggestions_json'])) {
            $settings['chef_suggestions'] = json_decode($settings['chef_suggestions_json'], true);
        }
        
        echo json_encode(['success' => true, 'data' => $settings]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// --- ESCRITURA (POST) ---
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $key = $data['key'] ?? '';
    $value = $data['value'] ?? '';

    // AGREGAMOS 'background_audio' A LA LISTA BLANCA
    $allowed_keys = ['image_duration_ms', 'suggestion_probability', 'chef_suggestions_json', 'background_audio'];
    
    if (!in_array($key, $allowed_keys)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Clave no permitida: ' . $key]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value)
            VALUES (:key, :value)
            ON DUPLICATE KEY UPDATE setting_value = :value
        ");
        $stmt->execute([':key' => $key, ':value' => $value]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}