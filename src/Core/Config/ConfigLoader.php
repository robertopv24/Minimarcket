<?php

namespace Minimarcket\Core\Config;

use Exception;

/**
 * Class ConfigLoader
 * 
 * Carga configuración desde archivos .env y archivos PHP de configuración.
 * Implementación nativa sin dependencias externas (phpdotenv).
 */
class ConfigLoader
{
    /**
     * Carga las variables de entorno desde un archivo .env
     */
    public static function loadEnv(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }

    /**
     * Carga un archivo de configuración PHP y retorna su array.
     */
    public static function loadFile(string $path): array
    {
        if (!file_exists($path)) {
            throw new Exception("Configuration file not found: {$path}");
        }
        return require $path;
    }
}
