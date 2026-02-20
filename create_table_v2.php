<?php
// Standalone connection to avoid autoload issues
$host = 'localhost';
$db   = 'burger_db'; // Assuming DB name based on context, or I can check converting config. But I'll try standard XAMPP defaults + previous context
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     // Try to connect to 'burger_db' first, if fails try without dbname to list dbs
    try {
         $pdo = new PDO($dsn, $user, $pass, $options);
    } catch(PDOException $e) {
         // Fallback: maybe DB name is different. Let's try to infer or just use "test" if strictly needed.
         // Actually, let's look at `admin/templates/autoload.php` content to see real connection logic?
         // No, let's just use the `funciones/conexion.php` which IS available.
         require_once __DIR__ . '/funciones/conexion.php';
         $pdo = Database::getConnection();
    }

    $sql = "CREATE TABLE IF NOT EXISTS product_companion_components (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_companion_id INT NOT NULL,
        component_type ENUM('raw', 'manufactured', 'product') NOT NULL,
        component_id INT NOT NULL,
        quantity DECIMAL(10, 6) NOT NULL,
        FOREIGN KEY (product_companion_id) REFERENCES product_companions(id) ON DELETE CASCADE
    );";
    
    $pdo->exec($sql);
    echo "Table product_companion_components created successfully.";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
