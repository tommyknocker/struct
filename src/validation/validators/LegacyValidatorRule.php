<?php

declare(strict_types=1);

namespace tommyknocker\struct\validation\validators;

use tommyknocker\struct\validation\ValidationResult;
use tommyknocker\struct\validation\ValidationRule;

/**
 * Legacy validator rule for backward compatibility with old validator classes.
 *
 * This rule wraps legacy validator classes that have a static validate() method
 * and converts them to the new ValidationRule interface.
 */
final class LegacyValidatorRule implements ValidationRule
{
    /**
     * @var string The validator class name
     */
    protected readonly string $validatorClass;

    public function __construct(
        string $validatorClass
    ) {
        $this->validatorClass = $validatorClass;
    }

    /**
     * Validates the given value using the legacy validator class.
     *
     * @param mixed $value The value to validate
     * @return ValidationResult The result of the validation
     */
    public function validate(mixed $value): ValidationResult
    {
        if (!class_exists($this->validatorClass)) {
            return ValidationResult::invalid(
                "Validator class {$this->validatorClass} does not exist"
            );
        }

        if (!method_exists($this->validatorClass, 'validate')) {
            return ValidationResult::invalid(
                "Validator {$this->validatorClass} must have a static validate() method"
            );
        }

        $result = $this->validatorClass::validate($value);

        if ($result === true) {
            return ValidationResult::valid();
        }

        $message = is_string($result) ? $result : "Validation failed";

        return ValidationResult::invalid($message);
    }
}
