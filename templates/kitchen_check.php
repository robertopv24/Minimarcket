<?php
// KITCHEN GUARD
// Incluir despues de session_start y autoload
if (!isset($userManager) || !isset($_SESSION)) {
    die("Access Error: Security headers missing.");
}

if (!$userManager->hasKitchenAccess($_SESSION)) {
    // Si es un cliente normal, lo mandamos a ver el estatus de su pedido, no a la cocina
    header("Location: ../paginas/status.php");
    exit;
}
