<?php
// Force define credentials if not present, trying to bypass the .env issue for CLI
if (!isset($_ENV['DB_HOST'])) $_ENV['DB_HOST'] = '127.0.0.1'; // Force TCP to avoid socket permission issues
if (!isset($_ENV['DB_NAME'])) $_ENV['DB_NAME'] = 'minimarket';
if (!isset($_ENV['DB_USER'])) $_ENV['DB_USER'] = 'root';
if (!isset($_ENV['DB_PASSWORD'])) $_ENV['DB_PASSWORD'] = '';

require_once __DIR__ . '/../templates/autoload.php';

echo "Connecting to " . $_ENV['DB_HOST'] . "...\n";

try {
    // Re-init connection with forced params if the autoload one failed or is using localhost socket
    $dsn = "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4";
    $db = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
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
    
    $db->exec($sql1);
    echo "Table 'tv_playlist_items' checked/created.\n";

    // 2. Table for Global TV Settings (key-value store)
    $sql2 = "CREATE TABLE IF NOT EXISTS tv_settings (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_value TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql2);
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
        $stmt = $db->prepare("INSERT IGNORE INTO tv_settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$key, $val]);
    }
    echo "Default settings seeded.\n";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
