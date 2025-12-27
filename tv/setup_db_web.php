<?php
// setup_db_web.php - Execute via Browser/HTTP
require_once '../templates/autoload.php';

echo "<h1>DB Setup</h1>";

try {
    $db = Database::getConnection();

     // 1. Table for TV Playlist Items
    $sql1 = "CREATE TABLE IF NOT EXISTS tv_playlist_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NULL,
        custom_title VARCHAR(255) NULL,
        custom_description TEXT NULL,
        custom_image_url VARCHAR(255) NULL,
        custom_price VARCHAR(50) NULL,
        duration_seconds INT DEFAULT 10,
        sort_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        show_suggestion TINYINT(1) DEFAULT 0,
        suggestion_text VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $db->exec($sql1);
    echo "Table 'tv_playlist_items' checked/created.<br>";

    // 2. Table for Global TV Settings
    $sql2 = "CREATE TABLE IF NOT EXISTS tv_settings (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_value TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql2);
    echo "Table 'tv_settings' checked/created.<br>";

    // 3. Seed Default Settings
    $defaults = [
        'background_audio' => '',
        'global_suggestion_probability' => '0.40',
        'default_duration' => '10',
        'chef_suggestions' => json_encode([
            "¡Añade unos tequeños extras!",
            "¿Postre? ¡Prueba nuestras tortas!",
            "¡Refresco grande para compartir!",
            "¡Pide tu salsa extra!"
        ])
    ];

    foreach ($defaults as $key => $val) {
        $stmt = $db->prepare("INSERT IGNORE INTO tv_settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$key, $val]);
    }
    echo "Default settings seeded.<br>";
    echo "<b>DONE</b>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
