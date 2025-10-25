<?php

declare(strict_types=1);

namespace tommyknocker\struct\cache;

use ReflectionClass;
use ReflectionProperty;

/**
 * Cache for reflection data to improve performance
 */
final class ReflectionCache
{
    /**
     * @var array<string, ReflectionProperty[]>
     */
    protected static array $propertyCache = [];

    /**
     * Get cached properties for a class
     * @return ReflectionProperty[]
     */
    public static function getProperties(string $className): array
    {
        if (!isset(self::$propertyCache[$className])) {
            /** @var class-string $className */
            $reflection = new ReflectionClass($className);
            self::$propertyCache[$className] = $reflection->getProperties();
        }

        return self::$propertyCache[$className];
    }

    /**
     * Clear the reflection cache
     */
    public static function clearCache(): void
    {
        self::$propertyCache = [];
    }
}
