<?php

declare(strict_types=1);

namespace tommyknocker\struct;

use Attribute;
use tommyknocker\struct\transformation\TransformerInterface;
use tommyknocker\struct\validation\ValidationRule;
use tommyknocker\struct\validation\validators\LegacyValidatorRule;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Field
{
    /**
     * Constructor
     * @param string|class-string|array<string> $type e.g. "string", "int", MyClass::class, ["string", "int"]
     * @param bool $nullable
     * @param bool $isArray whether this field is an array of given type
     * @param mixed $default default value if not provided in data (null means no default)
     * @param string|null $alias alternative key name in input data
     * @param string|null $validator validator class name (must have static validate method) - DEPRECATED
     * @param ValidationRule[] $validationRules array of validation rules
     * @param TransformerInterface[] $transformers array of transformers to apply
     */
    public function __construct(
        public string|array $type,
        public bool $nullable = false,
        public bool $isArray = false,
        public mixed $default = null,
        public ?string $alias = null,
        public ?string $validator = null,
        public array $validationRules = [],
        public array $transformers = []
    ) {
    }

    /**
     * Check if field has a default value
     */
    public function hasDefault(): bool
    {
        return $this->default !== null || $this->nullable;
    }

    /**
     * Get effective validation rules including legacy validator
     *
     * @return ValidationRule[]
     */
    public function getEffectiveValidationRules(): array
    {
        $rules = $this->validationRules;

        // Support legacy validator for backward compatibility
        if ($this->validator !== null) {
            $rules[] = new LegacyValidatorRule($this->validator);
        }

        return $rules;
    }
}
