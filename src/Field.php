<?php

declare(strict_types=1);

namespace tommyknocker\struct;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Field
{
    /**
     * @param string|class-string $type e.g. "string", "int", MyClass::class
     * @param bool $nullable
     * @param bool $isArray whether this field is an array of given type
     * @param mixed $default default value if not provided in data (null means no default)
     * @param string|null $alias alternative key name in input data
     * @param string|null $validator validator class name (must have static validate method)
     */
    public function __construct(
        public string $type,
        public bool $nullable = false,
        public bool $isArray = false,
        public mixed $default = null,
        public ?string $alias = null,
        public ?string $validator = null
    ) {
    }

    public function hasDefault(): bool
    {
        return $this->default !== null || $this->nullable;
    }
}
