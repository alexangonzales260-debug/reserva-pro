<?php

namespace App\Support;

class CurrentNegocio
{
    private static ?int $negocioId = null;

    /**
     * Set the active negocio for the current request/process.
     */
    public static function set(int $negocioId): void
    {
        self::$negocioId = $negocioId;
    }

    /**
     * Get the active negocio id, or null when there is no context.
     */
    public static function get(): ?int
    {
        return self::$negocioId;
    }

    /**
     * Determine whether a negocio context is active.
     */
    public static function isSet(): bool
    {
        return self::$negocioId !== null;
    }

    /**
     * Clear the active negocio context.
     */
    public static function clear(): void
    {
        self::$negocioId = null;
    }
}
