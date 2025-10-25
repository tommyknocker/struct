<?php

declare(strict_types=1);

namespace tommyknocker\struct\serialization;

use tommyknocker\struct\Struct;

/**
 * Interface for struct serialization
 */
interface SerializerInterface
{
    /**
     * Serialize a struct to array
     *
     * @param Struct $struct The struct to serialize
     * @return array<string, mixed> The serialized data
     */
    public function serialize(Struct $struct): array;

    /**
     * Deserialize array to struct
     *
     * @param array<string, mixed> $data The data to deserialize
     * @param class-string<Struct> $structClass The struct class name
     * @return Struct The deserialized struct
     */
    public function deserialize(array $data, string $structClass): Struct;
}
