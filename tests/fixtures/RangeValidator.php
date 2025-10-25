<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests\fixtures;

/**
 * Range validator for testing
 */
class RangeValidator
{
    public static function validate(mixed $value): bool|string
    {
        if (!is_int($value)) {
            return "Value must be an integer";
        }

        if ($value < 1 || $value > 100) {
            return "Value must be between 1 and 100";
        }

        return true;
    }
}
