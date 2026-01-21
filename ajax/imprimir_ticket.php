<?php
// ajax/imprimir_ticket.php
require_once '../templates/autoload.php';
require_once '../funciones/PrinterHelper.php'; // Incluir la clase nueva

header('Content-Type: application/json');

if (!isset($_POST['order_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Falta ID']);
    exit;
}

$orderId = $_POST['order_id'];
$order = $orderManager->getOrderById($orderId);
$items = $orderManager->getOrderItems($orderId);

if (!$order) {
    echo json_encode(['status' => 'error', 'message' => 'Orden no encontrada']);
    exit;
}

// 1. PREPARAR DATOS PARA LA IMPRESORA
$header = [
    'company' => $config->get('site_name'),
    'order_id' => $orderId,
    'date' => date('d/m/Y H:i', strtotime($order['created_at'])),
    'cajero' => $order['customer_name'],
    'cliente' => $order['shipping_address'] ?? 'Mostrador'
];

$totals = ['total' => number_format($order['total_price'], 2)];

$printItems = [];
$kitchenItems = [];

foreach ($items as $item) {
    $mods = $orderManager->getItemModifiers($item['id']);

    // A. Datos para Factura Cliente
    $extrasFin = [];
    foreach ($mods as $m) {
        if ($m['modifier_type'] == 'add' && $m['price_adjustment_usd'] > 0) {
            $extrasFin[] = ['name' => $m['ingredient_name'], 'price' => number_format($m['price_adjustment_usd'], 2)];
        }
    }

    $printItems[] = [
        'qty' => $item['quantity'],
        'name' => strtoupper($item['name']),
        'total' => number_format($item['price'] * $item['quantity'], 2),
        'extras_finance' => $extrasFin
    ];

    // B. Datos para Cocina (Lógica Granular)
    $groupedMods = [];
    foreach ($mods as $m) {
        $groupedMods[$m['sub_item_index']][] = $m;
    }

    // Subnombres para combos
    $subNames = [];
    if ($item['product_type'] == 'compound') {
        $comps = $productManager->getProductComponents($item['product_id']);
        foreach ($comps as $c) {
            if ($c['component_type'] == 'product') {
                $p = $productManager->getProductById($c['component_id']);
                for ($k = 0; $k < $c['quantity']; $k++)
                    $subNames[] = strtoupper($p['name']);
            }
        }
    }

    $loop = ($item['product_type'] == 'compound' && !empty($subNames)) ? count($subNames) : $item['quantity'];

    $subs = [];
    for ($i = 0; $i < $loop; $i++) {
        $currentMods = $groupedMods[$i] ?? [];
        $isTakeaway = false;
        $modList = [];
        $note = "";

        foreach ($currentMods as $m) {
            if ($m['modifier_type'] == 'info' && $m['is_takeaway'] == 1)
                $isTakeaway = true;
            if ($m['modifier_type'] == 'remove')
                $modList[] = "NO " . strtoupper($m['ingredient_name']);
            if ($m['modifier_type'] == 'add')
                $modList[] = "+ " . strtoupper($m['ingredient_name']);

            // Nota solo en el primero
            if ($i == 0 && $m['sub_item_index'] == -1 && $m['modifier_type'] == 'info')
                $note = $m['note'];
        }

        $subs[] = [
            'num' => $i + 1,
            'name' => $subNames[$i] ?? '',
            'is_takeaway' => $isTakeaway,
            'mods' => $modList,
            'note' => $note
        ];
    }

    $kitchenItems[] = [
        'qty' => $item['quantity'],
        'name' => strtoupper($item['name']),
        'subs' => $subs
    ];
}

// 2. MANDAR A IMPRIMIR
$type = $_POST['type'] ?? 'all';
$printer = new PrinterHelper();

if ($type == 'customer') {
    $result = $printer->printCustomerTicket($header, $printItems, $totals);
} elseif ($type == 'kitchen') {
    $result = $printer->printKitchenTicket($header, $kitchenItems);
} else {
    $result = $printer->printTicket($header, $printItems, $totals, $kitchenItems);
}

if ($result === true) {
    echo json_encode(['status' => 'ok']);
} else {
    echo json_encode(['status' => 'error', 'message' => $result]);
}
?>