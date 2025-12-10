<?php
require_once __DIR__ . '/../templates/autoload.php';
require_once __DIR__ . '/SimpleTest.php';

echo "=== TEST DE ALTA PRECISIÓN (DECIMAL 20,6) ===\n";

try {
    // 1. Crear Insumo con Micro Costo
    $microCost = 0.000015;
    $name = "Insumo Test Micro " . uniqid();
    echo "1. Creando insumo '$name' con costo $microCost...\n";

    $res = $rawMaterialManager->createMaterial($name, 'kg', $microCost, 5, 0);
    SimpleTest::assertTrue($res, "Insumo creado correctamente");

    // Recargar para verificar
    $materials = $rawMaterialManager->searchMaterials($name);
    $created = $materials[0];

    echo "   Costo Guardado: " . $created['cost_per_unit'] . "\n";

    if (abs(floatval($created['cost_per_unit']) - $microCost) < 0.0000001) {
        echo "   ✅ PRECISIÓN CORRECTA: El valor se guardó sin redondeo.\n";
    } else {
        echo "   ❌ ERROR PRECISIÓN: Se esperaba $microCost pero se obtuvo " . $created['cost_per_unit'] . "\n";
    }

    // 2. Sumar Stock y verificar promedio
    // Stock actual 0. Nuevo 10 @ 0.000025
    // Promedio debería ser 0.000025
    echo "\n2. Añadiendo Stock (10kg @ 0.000025)...\n";
    $rawMaterialManager->addStock($created['id'], 10, 0.000025);

    $updated = $rawMaterialManager->getMaterialById($created['id']);
    echo "   Nuevo Costo Promedio: " . $updated['cost_per_unit'] . "\n";

    // (0 * 0.000015 + 10 * 0.000025) / 10 = 0.000025
    if (abs(floatval($updated['cost_per_unit']) - 0.000025) < 0.0000001) {
        echo "   ✅ CÁLCULO PROMEDIO OK.\n";
    } else {
        echo "   ❌ ERROR PROMEDIO.\n";
    }

    // Limpieza
    echo "\nLimpiando datos de prueba...\n";
    $rawMaterialManager->deleteMaterial($created['id']);

} catch (Exception $e) {
    echo "Excepción: " . $e->getMessage() . "\n";
}
