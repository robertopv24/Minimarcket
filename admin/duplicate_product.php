<?php
/**
 * admin/duplicate_product.php
 * Lógica para duplicar un producto existente
 */

require_once '../templates/autoload.php';

session_start();

// 1. Verificar Permisos (Admin)
if (!isset($_SESSION['user_id']) || $userManager->getUserById($_SESSION['user_id'])['role'] !== 'admin') {
    header('Location: ../paginas/login.php');
    exit;
}

// 2. Verificar ID
$id = $_GET['id'] ?? null;

if (!$id) {
    echo "<script>alert('ID de producto no proporcionado.'); window.location.href='productos.php';</script>";
    exit;
}

// 3. Ejecutar Duplicación
try {
    $newId = $productManager->duplicateProduct($id);

    if ($newId) {
        // Éxito: Redirigir al usuario al editor del NUEVO producto para que haga ajustes inmediatos
        echo "<script>
            if(confirm('Producto duplicado exitosamente. ¿Deseas editar la copia ahora?')) {
                window.location.href = 'edit_product.php?id={$newId}';
            } else {
                window.location.href = 'productos.php';
            }
        </script>";
    } else {
        echo "<script>alert('Error al duplicar el producto. Revisa los logs.'); window.location.href='productos.php';</script>";
    }

} catch (Exception $e) {
    echo "<script>alert('Error crítico: " . htmlspecialchars($e->getMessage()) . "'); window.location.href='productos.php';</script>";
}
