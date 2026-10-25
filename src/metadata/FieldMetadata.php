<?php

declare(strict_types=1);

namespace tommyknocker\struct\metadata;

use ReflectionProperty;
use tommyknocker\struct\CollectionType;
use tommyknocker\struct\DefaultValue;
use tommyknocker\struct\transformation\TransformerInterface;
use tommyknocker\struct\validation\ValidationRule;

/** Compiled mapping definition; a single source of truth for runtime mapping. */
final class FieldMetadata
{
    /**
     * @param string|list<string> $type
     * @param list<ValidationRule> $validationRules
     * @param list<ValidationRule> $itemRules
     * @param list<TransformerInterface> $transformers
     */
    public function __construct(
        public readonly string $name,
        public readonly string|array $type,
        public readonly bool $nullable,
        public readonly ?CollectionType $collection,
        public readonly mixed $default,
        public readonly ?string $alias,
        public readonly array $validationRules,
        public readonly array $transformers,
        public readonly array $itemRules,
        public readonly ?string $outputAlias,
        public readonly bool $required,
        public readonly ReflectionProperty $property
    ) {
    }

    public function hasDefault(): bool
    {
        return $this->default !== DefaultValue::Missing;
    }
}
