<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_name']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../paginas/login.php");
    exit();
}

require_once '../templates/autoload.php';


$mensaje = '';

// Obtener el ID del producto a eliminar
$productoId = $_GET['id'] ?? 0;

// Obtener la información del producto
$sql = "SELECT * FROM products WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$productoId]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    $mensaje = '<div class="alert alert-danger">Producto no encontrado.</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirmar'])) {
        // Eliminar el producto de la base de datos
        $sql = "DELETE FROM products WHERE id = ?";
        $stmt = $db->prepare($sql);
        $resultado = $stmt->execute([$productoId]);

        if ($resultado) {
            $mensaje = '<div class="alert alert-success">Producto eliminado con éxito.</div>';
            // Redirigir a la lista de productos después de la eliminación
            header("Location: productos.php?success=true");
            exit();
        } else {
            $mensaje = '<div class="alert alert-danger">Error al eliminar el producto.</div>';
        }
    } elseif (isset($_POST['cancelar'])) {
        // Redirigir a la lista de productos si se cancela la eliminación
        header("Location: productos.php");
        exit();
    }
}



require_once '../templates/header.php';
require_once '../templates/menu.php';


?>

<div class="container mt-5">
    <h2>Eliminar Producto</h2>
    <?php echo $mensaje; ?>
    <?php if ($producto): ?>
        <p>¿Estás seguro de que deseas eliminar el producto "<?php echo htmlspecialchars($producto['name']); ?>"?</p>
        <form method="post" action="">
            <button type="submit" name="confirmar" class="btn btn-danger">Confirmar</button>
            <button type="submit" name="cancelar" class="btn btn-secondary">Cancelar</button>
        </form>
    <?php endif; ?>
</div>

<?php require_once '../templates/footer.php'; ?>
