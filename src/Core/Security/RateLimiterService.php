<?php

namespace Minimarcket\Core\Security;

class RateLimiterService
{
    private $storageDir;
    private $maxAttempts;
    private $windowSeconds;
    private $blockDuration;

    public function __construct($maxAttempts = 5, $windowSeconds = 300, $blockDuration = 900)
    {
        $this->maxAttempts = $maxAttempts;
        $this->windowSeconds = $windowSeconds;
        $this->blockDuration = $blockDuration;

        // Directorio para almacenar intentos (debe ser writable)
        $this->storageDir = __DIR__ . '/../../../../temp/rate_limit';

        // Crear directorio si no existe
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    private function getClientIP()
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }

        return $ip;
    }

    private function getStorageFile($action)
    {
        $ip = $this->getClientIP();
        $filename = md5($action . '_' . $ip) . '.json';
        return $this->storageDir . '/' . $filename;
    }

    private function readAttempts($action)
    {
        $file = $this->getStorageFile($action);

        if (!file_exists($file)) {
            return ['count' => 0, 'first_attempt' => time(), 'blocked_until' => null];
        }

        $data = json_decode(file_get_contents($file), true);

        if (!is_array($data)) {
            return ['count' => 0, 'first_attempt' => time(), 'blocked_until' => null];
        }

        return $data;
    }

    private function writeAttempts($action, $data)
    {
        $file = $this->getStorageFile($action);
        file_put_contents($file, json_encode($data), LOCK_EX);
    }

    public function check($action)
    {
        $data = $this->readAttempts($action);
        $now = time();

        if ($data['blocked_until'] && $now < $data['blocked_until']) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'retry_after' => $data['blocked_until'] - $now,
                'message' => 'Demasiados intentos. Intenta de nuevo en ' . ($data['blocked_until'] - $now) . ' segundos.'
            ];
        }

        if ($data['blocked_until'] && $now >= $data['blocked_until']) {
            $data = ['count' => 0, 'first_attempt' => $now, 'blocked_until' => null];
            $this->writeAttempts($action, $data);
        }

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

    public function hit($action)
    {
        $data = $this->readAttempts($action);
        $now = time();

        if ($now - $data['first_attempt'] <= $this->windowSeconds) {
            $data['count']++;
        } else {
            $data = ['count' => 1, 'first_attempt' => $now, 'blocked_until' => null];
        }

        if ($data['count'] >= $this->maxAttempts) {
            $data['blocked_until'] = $now + $this->blockDuration;
        }

        $this->writeAttempts($action, $data);
    }

    public function reset($action)
    {
        $file = $this->getStorageFile($action);
        if (file_exists($file)) {
            unlink($file);
        }
    }

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
