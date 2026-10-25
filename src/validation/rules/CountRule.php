<?php

declare(strict_types=1);

namespace tommyknocker\struct\validation\rules;

use InvalidArgumentException;
use tommyknocker\struct\validation\ValidationResult;
use tommyknocker\struct\validation\ValidationRule;

/** Inclusive array size bounds, independent of per-element validation. */
final class CountRule implements ValidationRule
{
    public function __construct(private readonly int $min = 0, private readonly ?int $max = null)
    {
        if ($min < 0 || ($max !== null && $max < $min)) {
            throw new InvalidArgumentException('Count bounds must satisfy 0 <= min <= max');
        }
    }

    public function validate(mixed $value): ValidationResult
    {
        if (!is_array($value)) {
            return ValidationResult::invalid('Value must be an array');
        }
        $count = count($value);
        if ($count < $this->min || ($this->max !== null && $count > $this->max)) {
            return ValidationResult::invalid('Array size must be at least ' . $this->min . ($this->max === null ? '' : ' and at most ' . $this->max));
        }

        return ValidationResult::valid();
    }
}
