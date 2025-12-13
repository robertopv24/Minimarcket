<?php
require_once __DIR__ . '/templates/autoload.php';
use Minimarcket\Core\Database\ConnectionManager;
global $app;
$db = $app->getContainer()->get(ConnectionManager::class);
$pdo = $db->getConnection();
$stmt = $pdo->query("DESCRIBE users");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
