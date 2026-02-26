<?php

namespace App\Support;

class TenantContext
{
    private static ?int $tenantId = null;

    public static function set(?int $tenantId): void
    {
        self::$tenantId = $tenantId;
    }

    public static function id(): ?int
    {
        // If explicitly set (e.g. middleware / testing), use it.
        if (self::$tenantId !== null) {
            return self::$tenantId;
        }

        // Otherwise default to platform_admin tenant_id (HQ per company).
        // Safe-guard for CLI / unauthenticated contexts.
        try {
            return auth()->check() ? (int) auth()->user()->tenant_id : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function clear(): void
    {
        self::$tenantId = null;
    }
}