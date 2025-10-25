<?php

declare(strict_types=1);

namespace tommyknocker\struct\metadata;

use ReflectionClass;
use ReflectionProperty;
use tommyknocker\struct\Field;

/**
 * Factory for creating struct metadata
 */
final class MetadataFactory
{
    /**
     * @var array<string, StructMetadata>
     */
    protected static array $cache = [];

    /**
     * Get metadata for a struct class
     *
     * @param class-string $className The struct class name
     * @return StructMetadata The struct metadata
     */
    public function getMetadata(string $className): StructMetadata
    {
        if (!isset(self::$cache[$className])) {
            self::$cache[$className] = $this->createMetadata($className);
        }

        return self::$cache[$className];
    }

    /**
     * Create metadata for a struct class
     *
     * @param class-string $className The struct class name
     * @return StructMetadata The struct metadata
     */
    protected function createMetadata(string $className): StructMetadata
    {
        /** @var class-string $className */
        $reflection = new ReflectionClass($className);
        $fields = [];

        foreach ($reflection->getProperties() as $property) {
            $fieldMetadata = $this->createFieldMetadata($property);
            if ($fieldMetadata) {
                $fields[$property->getName()] = $fieldMetadata;
            }
        }

        return new StructMetadata($className, $fields);
    }

    /**
     * Create field metadata from a reflection property
     *
     * @param ReflectionProperty $property The reflection property
     * @return FieldMetadata|null The field metadata or null if not a field
     */
    protected function createFieldMetadata(ReflectionProperty $property): ?FieldMetadata
    {
        $attributes = $property->getAttributes(Field::class);
        if (empty($attributes)) {
            return null;
        }

        /** @var Field $field */
        $field = $attributes[0]->newInstance();

        return new FieldMetadata(
            $property->getName(),
            $field->type,
            $field->nullable,
            $field->isArray,
            $field->default,
            $field->alias,
            $field->validationRules ?? [],
            $field->transformers ?? []
        );
    }

    /**
     * Clear the metadata cache
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
