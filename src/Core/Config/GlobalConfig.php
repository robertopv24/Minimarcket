<?php

namespace Minimarcket\Core\Config;

/**
 * Class GlobalConfig
 * 
 * Almacén central de configuración.
 * Singleton para acceso global facilitado.
 */
class GlobalConfig
{
    protected static array $items = [];

    /**
     * Carga un array de configuración.
     */
    public static function load(array $items): void
    {
        // echo "GlobalConfig LOAD: " . print_r(array_keys($items), true) . "\n";
        self::$items = array_merge(self::$items, $items);
    }

    /**
     * Obtiene un valor de configuración usando notación 'punto'.
     * Ej: GlobalConfig::get('db.host')
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$items)) {
            return self::$items[$key];
        }

        $array = self::$items;
        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * Verifica si existe una configuración.
     */
    public static function has(string $key): bool
    {
        return self::get($key) !== null;
    }
}
