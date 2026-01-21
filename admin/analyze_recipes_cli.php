<?php
define('CLI_MODE', true);
require_once __DIR__ . '/../templates/autoload.php';

// $productionManager and $rawMaterialManager are available via autoload

$products = $productionManager->getAllManufactured();
$rawMaterials = $rawMaterialManager->getAllMaterials();
$rawMap = [];
foreach($rawMaterials as $r) {
    $rawMap[$r['id']] = $r;
}

echo "Analyzing " . count($products) . " manufactured products...\n\n";
echo sprintf("%-40s | %-10s | %-12s | %-12s | %-12s\n", "Product", "Unit", "Stored Cost", "Calc. Cost", "Diff %");
echo str_repeat("-", 100) . "\n";

foreach ($products as $prod) {
    $recipe = $productionManager->getRecipe($prod['id']);
    
    if (empty($recipe)) {
         echo sprintf("%-40s | %-10s | %-12s | %-12s | %-12s\n", substr($prod['name'], 0, 40), $prod['unit'], number_format($prod['unit_cost_average'], 4), "NO RECIPE", "N/A");
         continue;
    }

    $calcCost = 0;
    foreach ($recipe as $item) {
        $rawId = $item['raw_material_id'];
        if (!isset($rawMap[$rawId])) {
            // Raw material not found?
            continue;
        }
        $raw = $rawMap[$rawId];
        $qty = $item['quantity_required'];
        $cost = $raw['cost_per_unit'];
        
        $calcCost += ($qty * $cost);
    }

    $storedCost = floatval($prod['unit_cost_average']);
    $diff = 0;
    if ($storedCost > 0) {
        $diff = (($calcCost - $storedCost) / $storedCost) * 100;
    } elseif ($calcCost > 0) {
        $diff = 100; // stored is 0 but calculated is > 0
    }

    $diffStr = number_format($diff, 2) . '%';
    if (abs($diff) > 5) {
        $diffStr .= " (!)";
    }

    echo sprintf("%-40s | %-10s | %-12s | %-12s | %-12s\n", 
        substr($prod['name'], 0, 40), 
        $prod['unit'], 
        number_format($storedCost, 4), 
        number_format($calcCost, 4), 
        $diffStr
    );
}

echo "\nAnalysis Complete.\n";
