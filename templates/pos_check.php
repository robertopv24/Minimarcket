<?php
// POS GUARD
// Incluir despues de session_start y autoload
if (!isset($userManager) || !isset($_SESSION)) {
    // Si se incluye mal, denegar
    die("Access Error: Security headers missing.");
}

if (!$userManager->hasPosAccess($_SESSION)) {
    // Si no tiene permiso POS, redirigir a Mis Pagos o Error
    if (isset($_SESSION['user_id'])) {
        header("Location: ../paginas/mis_pagos.php");
    } else {
        header("Location: ../paginas/login.php");
    }
    exit;
}
