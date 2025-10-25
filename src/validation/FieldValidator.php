<?php

declare(strict_types=1);

namespace tommyknocker\struct\validation;

use tommyknocker\struct\exception\ValidationException;
use tommyknocker\struct\Field;

/**
 * Validator for struct fields
 */
final class FieldValidator
{
    /**
     * Validates a field's value against its validation rules
     *
     * @param Field $field The field to validate
     * @param mixed $value The value to validate
     * @param string $fieldName The name of the field (for error reporting)
     * @throws ValidationException if validation fails
     */
    public function validateField(Field $field, mixed $value, string $fieldName): void
    {
        // Apply custom validation rules (including legacy validator)
        $rules = $field->getEffectiveValidationRules();
        $this->applyValidationRules($value, $rules, $fieldName);

        // If we get here, validation passed
    }

    /**
     * Applies the given validation rules to the value
     *
     * @param mixed $value The value to validate
     * @param ValidationRule[] $rules The validation rules to apply
     * @param string $fieldName The name of the field (for error reporting)
     * @throws ValidationException if any rule fails
     */
    protected function applyValidationRules(mixed $value, array $rules, string $fieldName): void
    {
        foreach ($rules as $rule) {
            $result = $rule->validate($value);
            if (!$result->isValid()) {
                throw new ValidationException(
                    $result->getErrorMessage() ?? "Validation failed for field $fieldName",
                    $fieldName,
                    $value
                );
            }
        }
    }
}
