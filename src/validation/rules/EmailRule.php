<?php

declare(strict_types=1);

namespace tommyknocker\struct\validation\rules;

use tommyknocker\struct\validation\ValidationResult;
use tommyknocker\struct\validation\ValidationRule;

/**
 * Email validation rule
 */
final class EmailRule implements ValidationRule
{
    /**
     * Validates that the given value is a valid email address
     * @param mixed $value The value to validate
     * @return ValidationResult The result of the validation
     */
    public function validate(mixed $value): ValidationResult
    {
        if (!is_string($value)) {
            return ValidationResult::invalid('Email must be a string');
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return ValidationResult::invalid('Invalid email format');
        }

        return ValidationResult::valid();
    }
}
