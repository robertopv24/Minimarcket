<?php
define('CLI_MODE', true);
require_once __DIR__ . '/../templates/autoload.php';

echo "CORRECCION DE RECETAS WHOPPER\n";
echo "=============================\n";

// Definición de Receta Base "Especial" (Copiada de ID 147)
// Excluyendo la proteína principal
$baseEspecial = [
    ['type' => 'raw', 'id' => 109, 'qty' => 1.0000], // Pan Whopper
    ['type' => 'raw', 'id' => 108, 'qty' => 0.5000], // Queso Cebu
    ['type' => 'raw', 'id' => 13, 'qty' => 0.0400], // Papitas Rayadas
    ['type' => 'raw', 'id' => 16, 'qty' => 0.0500], // Salsa Tomate
    ['type' => 'raw', 'id' => 19, 'qty' => 0.0500], // Salsa Mayonesa
    ['type' => 'raw', 'id' => 18, 'qty' => 0.0200], // Salsa Mostaza
    ['type' => 'raw', 'id' => 52, 'qty' => 0.0200], // Lechuga
    ['type' => 'raw', 'id' => 56, 'qty' => 0.0250], // Tomate
    ['type' => 'raw', 'id' => 8, 'qty' => 0.0150], // Jamon Ahumado
    ['type' => 'raw', 'id' => 12, 'qty' => 0.0200], // Queso Amarillo
    ['type' => 'raw', 'id' => 7, 'qty' => 0.0250], // Tosineta
];

// Definición de Proteínas para cada ID
$configs = [
    148 => [ // ESPECIAL DE CHULETA
        ['type' => 'raw', 'id' => 9, 'qty' => 0.1800] // Chuleta Ahumada
    ],
    149 => [ // ESPECIAL DE POLLO CRISPY
        ['type' => 'manufactured', 'id' => 6, 'qty' => 0.1800] // File de Pollo (Asumiendo mismo que normal)
    ],
    150 => [ // ESPECIAL DE LOMO
        ['type' => 'manufactured', 'id' => 8, 'qty' => 0.1800] // Lomito preparado
    ],
    151 => [ // ESPECIAL DE MECHADA
        ['type' => 'manufactured', 'id' => 5, 'qty' => 0.1800] // Carne Mechada
    ],
    152 => [ // ESPECIAL MIXTA (Carne + Pollo)
        ['type' => 'manufactured', 'id' => 3, 'qty' => 0.0900], // Carne Hamburguesa
        ['type' => 'manufactured', 'id' => 6, 'qty' => 0.0900]  // File Pollo
    ],
    153 => [ // ESPECIAL MARACUCHA (Carne + Platano?)
        // AGREGANDO PLATANO FRITO
        ['type' => 'manufactured', 'id' => 3, 'qty' => 0.1800], // Carne normal
        ['type' => 'raw', 'id' => 21, 'qty' => 0.5000] // Platano Amarillo (Patacon) mitad?
    ],
    154 => [ // ESPECIAL MEGA (Doble Carne)
        ['type' => 'manufactured', 'id' => 3, 'qty' => 0.3600] // Doble Carne
    ],
    155 => [ // ESPECIAL TRAFASICA (Carne + Pollo + Chuleta)
        ['type' => 'manufactured', 'id' => 3, 'qty' => 0.0600],
        ['type' => 'manufactured', 'id' => 6, 'qty' => 0.0600],
        ['type' => 'raw', 'id' => 9, 'qty' => 0.0600]
    ]
];

$db->beginTransaction();

try {
    foreach ($configs as $prodId => $proteins) {
        echo "Procesando Producto ID $prodId...\n";

        // 1. Borrar componentes actuales
        $stmtDel = $db->prepare("DELETE FROM product_components WHERE product_id = ?");
        $stmtDel->execute([$prodId]);

        // 2. Insertar Base
        $stmtIns = $db->prepare("INSERT INTO product_components (product_id, component_type, component_id, quantity) VALUES (?, ?, ?, ?)");

        foreach ($baseEspecial as $item) {
            $stmtIns->execute([$prodId, $item['type'], $item['id'], $item['qty']]);
        }

        // 3. Insertar Proteina Solicitada
        foreach ($proteins as $prot) {
            $stmtIns->execute([$prodId, $prot['type'], $prot['id'], $prot['qty']]);
        }

        echo "   -> Receta actualizada.\n";
    }

    $db->commit();
    echo "\nTerminado Exitosamente.\n";
} catch (Exception $e) {
    $db->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
