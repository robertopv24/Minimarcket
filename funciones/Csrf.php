<?php

class Csrf
{
    /**
     * Genera un token CSRF y lo guarda en la sesión si no existe.
     * @return string El token CSRF.
     */
    public static function getToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Genera un campo input hidden HTML con el token.
     * @return string HTML input.
     */
    public static function insertTokenField()
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /**
     * Valida el token CSRF enviado en la petición (POST).
     * Lanza una excepción si el token es inválido o no existe.
     * @throws Exception
     */
    public static function validateToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return; // No validamos GET (o podemos decidir hacerlo si es estricto)
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

        // Si llegamos aquí, es válido.
        return true;
    }

    /**
     * Valida un token específico (Legacy/Manual).
     * @param string $token El token a validar.
     * @return bool True si es válido.
     */
    public static function validate($token)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
