<?php

declare(strict_types=1);

namespace tommyknocker\struct\validation\rules;

use InvalidArgumentException;
use tommyknocker\struct\validation\ValidationResult;
use tommyknocker\struct\validation\ValidationRule;

/** PCRE string matching; callers choose anchoring and modifiers explicitly. */
final class PatternRule implements ValidationRule
{
    public function __construct(private readonly string $pattern)
    {
        // Invalid configuration is a programming error, not rejected user data.
        if (@preg_match($pattern, '') === false) {
            throw new InvalidArgumentException('Invalid PCRE pattern: ' . preg_last_error_msg());
        }
    }

    public function validate(mixed $value): ValidationResult
    {
        if (!is_string($value)) {
            return ValidationResult::invalid('Value must be a string');
        }

        return preg_match($this->pattern, $value) === 1
            ? ValidationResult::valid()
            : ValidationResult::invalid('Value does not match the required pattern');
    }
}
