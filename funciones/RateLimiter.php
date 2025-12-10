<?php

/**
 * RateLimiter - Sistema simple de rate limiting basado en archivos
 * Previene brute force attacks limitando intentos por IP
 */
class RateLimiter
{
    private $storageDir;
    private $maxAttempts;
    private $windowSeconds;
    private $blockDuration;

    /**
     * @param int $maxAttempts Máximo de intentos permitidos
     * @param int $windowSeconds Ventana de tiempo en segundos
     * @param int $blockDuration Duración del bloqueo en segundos
     */
    public function __construct($maxAttempts = 5, $windowSeconds = 300, $blockDuration = 900)
    {
        $this->maxAttempts = $maxAttempts;
        $this->windowSeconds = $windowSeconds;
        $this->blockDuration = $blockDuration;

        // Directorio para almacenar intentos (debe ser writable)
        $this->storageDir = __DIR__ . '/../temp/rate_limit';

        // Crear directorio si no existe
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    /**
     * Obtiene la IP del cliente
     */
    private function getClientIP()
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // Si está detrás de un proxy, intentar obtener la IP real
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }

        return $ip;
    }

    /**
     * Genera nombre de archivo para el tracking
     */
    private function getStorageFile($action)
    {
        $ip = $this->getClientIP();
        $filename = md5($action . '_' . $ip) . '.json';
        return $this->storageDir . '/' . $filename;
    }

    /**
     * Lee datos de intentos desde archivo
     */
    private function readAttempts($action)
    {
        $file = $this->getStorageFile($action);

        if (!file_exists($file)) {
            return ['count' => 0, 'first_attempt' => time(), 'blocked_until' => null];
        }

        $data = json_decode(file_get_contents($file), true);

        // Si el archivo está corrupto, resetear
        if (!is_array($data)) {
            return ['count' => 0, 'first_attempt' => time(), 'blocked_until' => null];
        }

        return $data;
    }

    /**
     * Guarda datos de intentos en archivo
     */
    private function writeAttempts($action, $data)
    {
        $file = $this->getStorageFile($action);
        file_put_contents($file, json_encode($data), LOCK_EX);
    }

    /**
     * Verifica si la acción está permitida
     * @return array ['allowed' => bool, 'remaining' => int, 'retry_after' => int|null]
     */
    public function check($action)
    {
        $data = $this->readAttempts($action);
        $now = time();

        // Si está bloqueado, verificar si el bloqueo expiró
        if ($data['blocked_until'] && $now < $data['blocked_until']) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'retry_after' => $data['blocked_until'] - $now,
                'message' => 'Demasiados intentos. Intenta de nuevo en ' . ($data['blocked_until'] - $now) . ' segundos.'
            ];
        }

        // Si el bloqueo expiró, resetear
        if ($data['blocked_until'] && $now >= $data['blocked_until']) {
            $data = ['count' => 0, 'first_attempt' => $now, 'blocked_until' => null];
            $this->writeAttempts($action, $data);
        }

        // Si la ventana de tiempo expiró, resetear contador
        if ($now - $data['first_attempt'] > $this->windowSeconds) {
            $data = ['count' => 0, 'first_attempt' => $now, 'blocked_until' => null];
            $this->writeAttempts($action, $data);
        }

        $remaining = $this->maxAttempts - $data['count'];

        return [
            'allowed' => $remaining > 0,
            'remaining' => max(0, $remaining),
            'retry_after' => null,
            'message' => $remaining > 0 ? null : 'Límite de intentos alcanzado.'
        ];
    }

    /**
     * Registra un intento
     */
    public function hit($action)
    {
        $data = $this->readAttempts($action);
        $now = time();

        // Si es un nuevo intento dentro de la ventana
        if ($now - $data['first_attempt'] <= $this->windowSeconds) {
            $data['count']++;
        } else {
            // Nueva ventana
            $data = ['count' => 1, 'first_attempt' => $now, 'blocked_until' => null];
        }

        // Si alcanzó el límite, bloquear
        if ($data['count'] >= $this->maxAttempts) {
            $data['blocked_until'] = $now + $this->blockDuration;
        }

        $this->writeAttempts($action, $data);
    }

    /**
     * Resetea los intentos (útil después de login exitoso)
     */
    public function reset($action)
    {
        $file = $this->getStorageFile($action);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Limpia archivos antiguos (mantenimiento)
     * Llamar periódicamente desde cron o al inicio
     */
    public function cleanup($maxAge = 86400)
    {
        $files = glob($this->storageDir . '/*.json');
        $now = time();

        foreach ($files as $file) {
            if ($now - filemtime($file) > $maxAge) {
                unlink($file);
            }
        }
    }
}
