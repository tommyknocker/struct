<?php

declare(strict_types=1);

namespace tommyknocker\struct\validation\rules;

use InvalidArgumentException;
use tommyknocker\struct\validation\ValidationResult;
use tommyknocker\struct\validation\ValidationRule;

/** Inclusive range for finite native numbers. */
final class RangeRule implements ValidationRule
{
    public function __construct(private readonly int|float $min, private readonly int|float $max)
    {
        if (!is_finite((float) $min) || !is_finite((float) $max) || $min > $max) {
            throw new InvalidArgumentException('Range bounds must be finite and min <= max');
        }
    }

    public function validate(mixed $value): ValidationResult
    {
        if ((!is_int($value) && !is_float($value)) || !is_finite((float) $value)) {
            return ValidationResult::invalid('Value must be a finite number');
        }
        if ($value < $this->min || $value > $this->max) {
            return ValidationResult::invalid("Value must be between {$this->min} and {$this->max}");
        }

        return ValidationResult::valid();
    }
}
