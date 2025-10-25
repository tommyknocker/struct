<?php

declare(strict_types=1);

namespace tommyknocker\struct\serialization;

use tommyknocker\struct\Struct;

/**
 * JSON serializer for structs
 */
final class JsonSerializer implements SerializerInterface
{
    /**
     * Serialize struct to array
     *
     * @param Struct $struct The struct to serialize
     * @return array<string, mixed> The serialized data
     */
    public function serialize(Struct $struct): array
    {
        return $struct->toArray();
    }

    /**
     * Deserialize array to struct
     *
     * @param array<string, mixed> $data The data to deserialize
     * @param class-string<Struct> $structClass The struct class name
     * @return Struct The deserialized struct
     */
    public function deserialize(array $data, string $structClass): Struct
    {
        return new $structClass($data);
    }

    /**
     * Serialize struct to JSON string
     *
     * @param Struct $struct The struct to serialize
     * @param bool $pretty Whether to pretty print
     * @param int $flags JSON flags
     * @return string JSON string
     */
    public function toJson(
        Struct $struct,
        bool $pretty = false,
        int $flags = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ): string {
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }
        $result = json_encode($struct, $flags);
        if ($result === false) {
            throw new \RuntimeException('Failed to encode struct to JSON');
        }

        return $result;
    }

    /**
     * Deserialize JSON string to struct
     *
     * @param string $json JSON string
     * @param class-string<Struct> $structClass Struct class name
     * @param int $flags JSON flags
     * @return Struct The deserialized struct
     */
    public function fromJson(string $json, string $structClass, int $flags = JSON_THROW_ON_ERROR): Struct
    {
        $data = json_decode($json, true, 512, $flags);
        if (!is_array($data)) {
            throw new \RuntimeException('JSON must decode to an array');
        }

        return new $structClass($data);
    }
}
