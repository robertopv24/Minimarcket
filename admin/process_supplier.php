<?php
// process_supplier.php
require_once '../templates/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $name = $_POST['name'] ?? '';
            $contactPerson = $_POST['contact_person'] ?? '';
            $email = $_POST['email'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $address = $_POST['address'] ?? '';

            if ($supplierManager->addSupplier($name, $contactPerson, $email, $phone, $address)) {
                header('Location: proveedores.php');
                exit;
            } else {
                echo "Error al agregar el proveedor.";
            }
            break;
        case 'edit':
            $id = $_POST['id'] ?? 0;
            $name = $_POST['name'] ?? '';
            $contactPerson = $_POST['contact_person'] ?? '';
            $email = $_POST['email'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $address = $_POST['address'] ?? '';

            if ($supplierManager->updateSupplier($id, $name, $contactPerson, $email, $phone, $address)) {
                header('Location: proveedores.php');
                exit;
            } else {
                echo "Error al actualizar el proveedor.";
            }
            break;
        case 'delete':
            $id = $_POST['id'] ?? 0;
            if ($supplierManager->deleteSupplier($id)) {
                header('Location: proveedores.php');
                exit;
            } else {
                echo "Error al eliminar el proveedor.";
            }
            break;
        default:
            echo "Acción no válida.";
            break;
    }
} else {
    echo "Acceso no permitido.";
}
?>
