<?php

declare(strict_types=1);

namespace tommyknocker\struct\validation\rules;

use InvalidArgumentException;
use tommyknocker\struct\validation\ValidationResult;
use tommyknocker\struct\validation\ValidationRule;

/** Inclusive Unicode code-point length; does not count grapheme clusters. */
final class LengthRule implements ValidationRule
{
    public function __construct(private readonly int $min, private readonly int $max)
    {
        if ($min < 0 || $max < $min) {
            throw new InvalidArgumentException('Length bounds must satisfy 0 <= min <= max');
        }
    }

    public function validate(mixed $value): ValidationResult
    {
        if (!is_string($value)) {
            return ValidationResult::invalid('Value must be a string');
        }
        $length = preg_match_all('/./us', $value);
        if ($length === false) {
            return ValidationResult::invalid('Value must be valid UTF-8');
        }
        if ($length < $this->min || $length > $this->max) {
            return ValidationResult::invalid("Length must be between {$this->min} and {$this->max}");
        }

        return ValidationResult::valid();
    }
}
