<?php

namespace Minimarcket\Core\Security;

use Exception;

class CsrfToken
{
    public function getToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public function insertTokenField()
    {
        $token = $this->getToken();
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    public function validateToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (empty($_SESSION['csrf_token'])) {
            throw new Exception("Error de seguridad: Sesión expirada o token no generado.");
        }

        $token = $_POST['csrf_token'] ?? '';

        if (empty($token)) {
            throw new Exception("Error de seguridad: Token CSRF faltante.");
        }

        if (!hash_equals($_SESSION['csrf_token'], $token)) {
            throw new Exception("Error de seguridad: Token CSRF inválido.");
        }

        return true;
    }

    public function validate($token)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
