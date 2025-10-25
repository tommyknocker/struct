<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests\fixtures;

/**
 * Always fail validator for testing
 */
class AlwaysFailValidator
{
    public static function validate(mixed $value): bool|string
    {
        return "Always fails";
    }
}
