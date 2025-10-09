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
     */
    public function __construct(
        public string $type,
        public bool $nullable = false,
        public bool $isArray = false
    ) {
    }
}
