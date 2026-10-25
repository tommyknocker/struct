<?php

declare(strict_types=1);

namespace tommyknocker\struct\factory;

use tommyknocker\struct\Struct;

/**
 * Factory for creating struct instances
 */
final class StructFactory
{
    /**
     * Create a struct instance
     *
     * @template T of Struct
     * @param class-string<T> $structClass The struct class name
     * @param array<string, mixed> $data The data for the struct
     * @return T The created struct
     */
    public function create(string $structClass, array $data): Struct
    {
        return new $structClass($data);
    }

    /**
     * Create a struct from JSON
     *
     * @template T of Struct
     * @param class-string<T> $structClass The struct class name
     * @param string $json JSON string
     * @param int $flags JSON flags
     * @return T The created struct
     */
    public function createFromJson(string $structClass, string $json, int $flags = JSON_THROW_ON_ERROR): Struct
    {
        return $structClass::fromJson($json, $flags);
    }
}
