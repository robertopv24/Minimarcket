<?php
require_once 'c:/xampp/htdocs/Minimarcket/templates/autoload.php';
$db = Database::getConnection();
$stmt = $db->query("DESCRIBE transactions");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($columns, JSON_PRETTY_PRINT);
?>