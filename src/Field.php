<?php

declare(strict_types=1);

namespace tommyknocker\struct;

use Attribute;
use tommyknocker\struct\transformation\TransformerInterface;
use tommyknocker\struct\validation\ValidationRule;

/** Mapping options; native PHP properties define nullability. */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Field
{
    /**
     * @param string|array<string>|null $type Mapping type, or collection element type with collection.
     * @param ValidationRule[] $validationRules Rules for the complete mapped value.
     * @param ValidationRule[] $itemRules Rules for each typed collection element.
     * @param TransformerInterface[] $transformers Input transformations, in declaration order.
     */
    public function __construct(
        public readonly string|array|null $type = null,
        public readonly ?CollectionType $collection = null,
        public readonly mixed $default = DefaultValue::Missing,
        public readonly ?string $alias = null,
        public readonly array $validationRules = [],
        public readonly array $transformers = [],
        public readonly array $itemRules = [],
        public readonly ?string $outputAlias = null,
        public readonly bool $required = true
    ) {
    }

    public function hasDefault(): bool
    {
        return $this->default !== DefaultValue::Missing;
    }
}
