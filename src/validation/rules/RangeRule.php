<?php

declare(strict_types=1);

namespace tommyknocker\struct\validation\rules;

use tommyknocker\struct\validation\ValidationResult;
use tommyknocker\struct\validation\ValidationRule;

/**
 * Range validation rule for numeric values
 */
final class RangeRule implements ValidationRule
{
    /**
     * @var int|float The minimum value
     */
    protected readonly int|float $min;

    /**
     * @var int|float The maximum value
     */
    protected readonly int|float $max;

    public function __construct(
        int|float $min,
        int|float $max
    ) {
        $this->min = $min;
        $this->max = $max;
    }

    /**
     * Validates that the given value is within the specified range
     * @param mixed $value The value to validate
     * @return ValidationResult The result of the validation
     */
    public function validate(mixed $value): ValidationResult
    {
        if (!is_numeric($value)) {
            return ValidationResult::invalid('Value must be numeric');
        }

        $numericValue = is_string($value) ? (float) $value : $value;

        if ($numericValue < $this->min || $numericValue > $this->max) {
            return ValidationResult::invalid(
                "Value must be between {$this->min} and {$this->max}"
            );
        }

        return ValidationResult::valid();
    }
}
