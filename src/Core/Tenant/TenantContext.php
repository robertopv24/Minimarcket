<?php

namespace Minimarcket\Core\Tenant;

class TenantContext
{
    private static ?array $currentTenant = null;

    /**
     * Establece el tenant actual.
     */
    public static function setTenant(array $tenant): void
    {
        self::$currentTenant = $tenant;
    }

    /**
     * Obtiene el tenant actual.
     */
    public static function getTenant(): ?array
    {
        return self::$currentTenant;
    }

    /**
     * Obtiene el ID del tenant actual.
     */
    public static function getTenantId(): int
    {
        return self::$currentTenant['id'] ?? 1; // Default to ID 1 if not set
    }
}
