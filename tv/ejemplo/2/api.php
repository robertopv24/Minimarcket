<?php
// api.php
include 'db_config.php';
header('Content-Type: application/json');
ini_set('display_errors', 0);

try {
    $pdo = connectDB();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $stmtOff = $pdo->query("SELECT * FROM ofertas ORDER BY orden ASC");
        $ofertas = $stmtOff->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $settings, 'ofertas' => $ofertas]);
    } 
    
    elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';

        if ($action === 'add_oferta') {
            $sql = "INSERT INTO ofertas (titulo, descripcion, precio, imagen_fondo, imagen_producto, titulo_size) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$data['titulo'], $data['descripcion'], $data['precio'], $data['imagen_fondo'], $data['imagen_producto'], $data['titulo_size']]);
            echo json_encode(['success' => true]);
        } 
        elseif ($action === 'update_oferta') {
            $sql = "UPDATE ofertas SET titulo=?, descripcion=?, precio=?, imagen_fondo=?, imagen_producto=?, titulo_size=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$data['titulo'], $data['descripcion'], $data['precio'], $data['imagen_fondo'], $data['imagen_producto'], $data['titulo_size'], $data['id']]);
            echo json_encode(['success' => true]);
        }
        elseif ($action === 'delete_oferta') {
            $stmt = $pdo->prepare("DELETE FROM ofertas WHERE id = ?");
            $stmt->execute([$data['id']]);
            echo json_encode(['success' => true]);
        }
        else {
            // Guardar ajustes generales
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = :v");
            $stmt->execute([':k' => $data['key'], ':v' => $data['value']]);
            echo json_encode(['success' => true]);
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}