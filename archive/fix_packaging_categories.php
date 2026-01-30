<?php
// admin/fix_packaging_categories.php
require_once __DIR__ . '/../templates/autoload.php';

echo "--- INICIO DE CORRECCIÓN DE CATEGORÍAS DE EMPAQUE ---\n";

$keywords = ['caja', 'bolsa', 'envase', 'vaso', 'papel', 'servilleta', 'calcomania', 'tapa', 'vasos'];
$updatedCount = 0;

try {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT id, name FROM raw_materials WHERE category = 'ingredient'");
    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($materials as $m) {
        $nameLower = mb_strtolower($m['name']);
        $shouldBePackaging = false;

        foreach ($keywords as $kw) {
            if (strpos($nameLower, $kw) !== false) {
                $shouldBePackaging = true;
                break;
            }
        }

        if ($shouldBePackaging) {
            $update = $db->prepare("UPDATE raw_materials SET category = 'packaging' WHERE id = ?");
            if ($update->execute([$m['id']])) {
                echo "Actualizado: " . $m['name'] . " -> packaging\n";
                $updatedCount++;
            }
        }
    }

    echo "\nTotal de insumos corregidos: $updatedCount\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "--- FIN ---\n";
?>