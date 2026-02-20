<?php
// cli_test_logic.php
// Script de prueba para verificar correcciones logicas
// Se ejecuta desde CLI: php cli_test_logic.php

define('CLI_MODE', true);

// Ajustar rutas si estamos en la raiz o donde sea
// Asumimos que estamos en /home/roberto/Escritorio/Minimarcket/
require_once __DIR__ . '/templates/autoload.php';

// Mock de Session para Csrf y Auth si fuera necesario (aunque Managers no suelen deender de $_SESSION directo salvo helpers)
$_SESSION['user_id'] = 1;

echo "--- INICIO DE TEST DE LÓGICA ---\n";

try {
    // 1. Crear Orden de Compra
    echo "[TEST 1] Crear Orden de Compra... ";
    // (supplierId, orderDate, deliveryDate, items, rate)
    // Items vacios primero
    $poId = $purchaseOrderManager->createPurchaseOrder(1, date('Y-m-d'), date('Y-m-d'), [], 1.0);

    if ($poId) {
        echo "OK (ID: $poId)\n";
    } else {
        die("FALLÓ: No se pudo crear la orden.\n");
    }

    // 2. Agregar Item y Verificar Total
    echo "[TEST 2] Agregar Item y Verificar Total... ";
    // ID 1 prod, qty 10, precio 5.00 -> Total debe ser 50.00
    $purchaseOrderManager->addItemToPurchaseOrder($poId, 1, 10, 5.00);

    $order = $purchaseOrderManager->getPurchaseOrderById($poId);
    if ($order['total_amount'] == 50.00) {
        echo "OK (Total actualizado a 50.00)\n";
    } else {
        echo "FALLÓ (Total es " . $order['total_amount'] . ", esperado 50.00)\n";
    }

    // 3. Recibir Orden (Debe funcionar)
    echo "[TEST 3] Recibir Orden... ";
    $receiptId = $purchaseReceiptManager->createPurchaseReceipt($poId, date('Y-m-d'));
    if ($receiptId) {
        echo "OK (Receipt ID: $receiptId)\n";
    } else {
        echo "FALLÓ al recibir.\n";
    }

    // 4. Recibir Orden DUPLICADA (Debe fallar)
    echo "[TEST 4] Doble Recepción (Debe fallar)... ";
    $doubleReceipt = $purchaseReceiptManager->createPurchaseReceipt($poId, date('Y-m-d'));
    if ($doubleReceipt === false) {
        // El manager devuelve false en catch? No, mi codigo lanza exception y el manager hace catch return false...
        // Espera, mi codigo en manager tiene catch PDOException return false.
        // Pero yo agregue throw Exception.
        // Veamos el catch block de createPurchaseReceipt:
        /*
            catch (PDOException $e) { ... return false; }
            catch (Exception $e) { ... return false; }
        */
        // Entonces devolvera false.
        echo "OK (Devolvió false correctamente)\n";
    } else {
        echo "FALLÓ (Permitió doble recepción ID: $doubleReceipt)\n";
    }

    // Limpieza (Opcional, borrar lo creado)
    // $purchaseOrderManager->deletePurchaseOrder($poId);

} catch (Exception $e) {
    echo "\nEXCEPCIÓN NO CONTROLADA: " . $e->getMessage() . "\n";
}

echo "--- FIN DE TEST ---\n";
?>