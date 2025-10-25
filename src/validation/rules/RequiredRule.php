<?php

declare(strict_types=1);

namespace tommyknocker\struct\validation\rules;

use tommyknocker\struct\validation\ValidationResult;
use tommyknocker\struct\validation\ValidationRule;

/**
 * Required field validation rule
 */
final class RequiredRule implements ValidationRule
{
    /**
     * Validates that the given value is not null or empty
     * @param mixed $value The value to validate
     * @return ValidationResult The result of the validation
     */
    public function validate(mixed $value): ValidationResult
    {
        if ($value === null || $value === '') {
            return ValidationResult::invalid('Field is required');
        }

        return ValidationResult::valid();
    }
}
