<?php
// error_reporting(E_ALL); ini_set('display_errors', 1);
require_once __DIR__ . '/templates/autoload.php';
use Minimarcket\Core\Database\ConnectionManager;

echo "🛠️ Aplicando Migración SaaS (Schema Update)...\n";

global $app;
$pdo = $app->getContainer()->get(ConnectionManager::class)->getConnection();

$tables = [
    'products',
    'suppliers',
    'transactions',
    'orders',
    'purchase_orders', // AGREGADO
    'cart', // Corrección: singular
    'users',
    'accounts_receivable',
    // 'credit_clients', // Corrección: No existe, usamos accounts_receivable
    'order_items',
    'payroll_payments',
    'raw_materials',
    'vault_movements',
    'manufactured_products',
    'production_recipes', // Corrección: nombre real
    'product_components',
    'cash_sessions', // Corrección: nombre actual
];

foreach ($tables as $table) {
    try {
        echo "Checking '$table'...\n";

        // Verificar si existe la columna
        $stmt = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE 'tenant_id'");
        $stmt->execute();

        if ($stmt->fetch()) {
            echo " - OK: tenant_id ya existe en $table.\n";
        } else {
            echo " - UPDATE: Añadiendo tenant_id a $table...\n";
            $pdo->exec("ALTER TABLE {$table} ADD COLUMN tenant_id INT DEFAULT 1");
            $pdo->exec("ALTER TABLE {$table} ADD INDEX (tenant_id)");
            echo " - OK: Tabla actualizada.\n";
        }

    } catch (PDOException $e) {
        echo " - WARN: No se pudo actualizar $table (¿Tal vez no existe?). Error: " . $e->getMessage() . "\n";
    }
}

echo "✅ Migración completada.\n";
