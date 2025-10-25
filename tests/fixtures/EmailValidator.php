<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests\fixtures;

/**
 * Legacy validator for backward compatibility testing
 */
class EmailValidator
{
    public static function validate(mixed $value): bool|string
    {
        if (!is_string($value)) {
            return "Email must be a string";
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "Invalid email format";
        }

        return true;
    }
}
