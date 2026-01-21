<?php
// funciones/SessionHelper.php

class SessionHelper
{
    /**
     * Establecer un mensaje flash
     * @param string $type success, error, warning, info
     * @param string $message El mensaje a mostrar
     */
    public static function setFlash($type, $message)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash_messages'][] = [
            'type' => $type,
            'message' => $message
        ];
    }

    /**
     * Obtener y limpiar todos los mensajes flash
     * @return array
     */
    public static function getFlashes()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $flashes = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);
        return $flashes;
    }

    /**
     * Verificar si hay mensajes
     */
    public static function hasFlashes()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return !empty($_SESSION['flash_messages']);
    }
}
