<?php

declare(strict_types=1);

namespace tommyknocker\struct\validation;

/**
 * Interface for validation rules
 */
interface ValidationRule
{
    /**
     * Validate a value and return the result
     *
     * @param mixed $value The value to validate
     * @return ValidationResult The validation result
     */
    public function validate(mixed $value): ValidationResult;
}
