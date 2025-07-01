<?php

namespace App\Core;

class Registry
{
    /**
     * @var array
     */
    private static array $registry = [];

    /**
     * Get a value from the registry.
     *
     * @param string $key
     * @return mixed|null
     */
    public static function get(string $key)
    {
        return self::$registry[$key] ?? null;
    }

    /**
     * Set a value in the registry.
     *
     * @param string $key
     * @param mixed $value
     */
    public static function set(string $key, $value): void
    {
        self::$registry[$key] = $value;
    }
    
    /**
     * Remove a value from the registry.
     *
     * @param string $key
     */
    public static function remove(string $key): void
    {
        unset(self::$registry[$key]);
    }

    /**
     * Check if a key exists in the registry.
     *
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return isset(self::$registry[$key]);
    }
}