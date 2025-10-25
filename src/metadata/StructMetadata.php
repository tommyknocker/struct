<?php

declare(strict_types=1);

namespace tommyknocker\struct\metadata;

/**
 * Metadata for a struct class
 */
final class StructMetadata
{
    /**
     * @var string The class name of the struct
     */
    public readonly string $className;

    /**
     * @var FieldMetadata[] The fields of the struct
     */
    public readonly array $fields;

    /**
     * @param FieldMetadata[] $fields
     */
    public function __construct(
        string $className,
        array $fields
    ) {
        $this->className = $className;
        $this->fields = $fields;
    }

    public function getFieldByName(string $name): ?FieldMetadata
    {
        return $this->fields[$name] ?? null;
    }

    public function getFieldByAlias(string $alias): ?FieldMetadata
    {
        foreach ($this->fields as $field) {
            if ($field->alias === $alias) {
                return $field;
            }
        }

        return null;
    }

    /**
     * @return array<string>
     */
    public function getAllowedFieldNames(): array
    {
        $names = [];
        foreach ($this->fields as $field) {
            $names[] = $field->name;
            if ($field->alias) {
                $names[] = $field->alias;
            }
        }

        return $names;
    }
}
