<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests\fixtures;

/**
 * Validator without validate method for testing
 */
class NoValidateMethodValidator
{
    public static function check(mixed $value): bool
    {
        return true;
    }
}
