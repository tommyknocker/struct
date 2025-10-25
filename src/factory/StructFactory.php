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
     * @param class-string<Struct> $structClass The struct class name
     * @param array<string, mixed> $data The data for the struct
     * @return Struct The created struct
     */
    public function create(string $structClass, array $data): Struct
    {
        return new $structClass($data);
    }

    /**
     * Create a struct from JSON
     *
     * @param class-string<Struct> $structClass The struct class name
     * @param string $json JSON string
     * @param int $flags JSON flags
     * @return Struct The created struct
     */
    public function createFromJson(string $structClass, string $json, int $flags = JSON_THROW_ON_ERROR): Struct
    {
        $data = json_decode($json, true, 512, $flags);
        if (!is_array($data)) {
            throw new \RuntimeException('JSON must decode to an array');
        }

        /** @var array<string, mixed> $data */
        return $this->create($structClass, $data);
    }
}
