<?php
require_once __DIR__ . '/templates/autoload.php';
use Minimarcket\Core\Database\ConnectionManager;

echo "🛠️ Creating Tenants Table (SaaS Requirement)...\n";

global $app;
$pdo = $app->getContainer()->get(ConnectionManager::class)->getConnection();

$sql = "CREATE TABLE IF NOT EXISTS tenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    subdomain VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

try {
    $pdo->exec($sql);
    echo "✅ Table 'tenants' created or already exists.\n";

    // Seed default tenants if empty
    $count = $pdo->query("SELECT COUNT(*) FROM tenants")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("INSERT INTO tenants (name, subdomain) VALUES ('Default Supermarket', 'default')");
        $pdo->exec("INSERT INTO tenants (name, subdomain) VALUES ('Tenant Two', 'tenant2')");
        echo "✅ Seeded default tenants.\n";
    }

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
