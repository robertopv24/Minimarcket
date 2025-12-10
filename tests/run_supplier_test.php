<?php
require_once __DIR__ . '/../templates/autoload.php';
require_once __DIR__ . '/SimpleTest.php';

echo "=== TEST DE INTEGRACIÓN: PROVEEDORES ===\n";

try {
    // 1. Crear Proveedor
    $uniqueId = uniqid();
    $name = "Supplier Test $uniqueId";
    $contact = "Contact $uniqueId";
    $phone = "555-1234";
    $email = "supp_$uniqueId@test.com";

    echo "1. Creando proveedor '$name'...\n";
    $supplierManager->addSupplier($name, $contact, $email, $phone, "Address Test");

    // Verificar creación (No retorna ID directamente, buscamos en DB o lista)
    // Asumimos que es el último (o buscamos por nombre si hay método)
    $stmt = $db->prepare("SELECT * FROM suppliers WHERE name = ?");
    $stmt->execute([$name]);
    $supplier = $stmt->fetch(PDO::FETCH_ASSOC);

    SimpleTest::assertNotNull($supplier, "El proveedor debió crearse.");
    SimpleTest::assertEquals($email, $supplier['email'], "Email coincide.");
    echo "   ✅ Proveedor creado ID: " . $supplier['id'] . "\n";

    // 2. Editar Proveedor
    echo "2. Editando proveedor...\n";
    $newName = "Supplier UPDATED $uniqueId";
    $supplierManager->updateSupplier($supplier['id'], $newName, $contact, $email, $phone, "New Addr");

    // Verificar
    $updatedSup = $supplierManager->getSupplierById($supplier['id']);
    SimpleTest::assertEquals($newName, $updatedSup['name'], "Nombre actualizado correctamente.");
    echo "   ✅ Actualización verificada.\n";

    // 3. Eliminar Proveedor
    echo "3. Eliminando proveedor...\n";
    $supplierManager->deleteSupplier($supplier['id']);

    $deletedSup = $supplierManager->getSupplierById($supplier['id']);
    if (!$deletedSup) {
        echo "   ✅ Proveedor eliminado correctamente.\n";
    } else {
        echo "   ❌ ERROR: El proveedor sigue existiendo.\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "❌ Excepción: " . $e->getMessage() . "\n";
    exit(1);
}
?>