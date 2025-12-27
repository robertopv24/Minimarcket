<?php
// Standalone DB Setup - Bylassing User's Autoload
$host = '127.0.0.1'; // Use TCP
$db_name = 'minimarket';
$user = 'root';
$pass = '';

echo "Connecting to $host...\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

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
    
    $pdo->exec($sql1);
    echo "Table 'tv_playlist_items' checked/created.\n";

    // 2. Table for Global TV Settings (key-value store)
    $sql2 = "CREATE TABLE IF NOT EXISTS tv_settings (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_value TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql2);
    echo "Table 'tv_settings' checked/created.\n";

    // 3. Seed Default Settings if empty
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
        $stmt = $pdo->prepare("INSERT IGNORE INTO tv_settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$key, $val]);
    }
    echo "Default settings seeded.\n";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
