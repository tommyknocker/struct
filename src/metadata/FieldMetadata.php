<?php

declare(strict_types=1);

namespace tommyknocker\struct\metadata;

use tommyknocker\struct\transformation\TransformerInterface;
use tommyknocker\struct\validation\ValidationRule;

/**
 * Metadata for a single field in a struct
 */
final class FieldMetadata
{
    /**
     * @var string The name of the field
     */
    public readonly string $name;
    /**
     * @var string|array<string> The type(s) of the field
     */
    public readonly string|array $type;
    /**
     * @var bool Whether the field is nullable
     */
    public readonly bool $nullable;
    /**
     * @var bool Whether the field is an array
     */
    public readonly bool $isArray;
    /**
     * @var mixed The default value of the field
     */
    public readonly mixed $default;
    /**
     * @var string|null The alias of the field
     */
    public readonly ?string $alias;
    /**
     * @var ValidationRule[] The validation rules for the field
     */
    public readonly array $validationRules;
    /**
     * @var TransformerInterface[] The transformers for the field
     */
    public readonly array $transformers;

    /**
     * @param string|array<string> $type
     * @param ValidationRule[] $validationRules
     * @param TransformerInterface[] $transformers
     */
    public function __construct(
        string $name,
        string|array $type,
        bool $nullable,
        bool $isArray,
        mixed $default,
        ?string $alias,
        array $validationRules,
        array $transformers
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->nullable = $nullable;
        $this->isArray = $isArray;
        $this->default = $default;
        $this->alias = $alias;
        $this->validationRules = $validationRules;
        $this->transformers = $transformers;
    }

    /**
     * Check if field has a default value
     */
    public function hasDefault(): bool
    {
        return $this->default !== null || $this->nullable;
    }
}
