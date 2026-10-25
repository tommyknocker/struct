<?php

declare(strict_types=1);

namespace tommyknocker\struct\validation\rules;

use tommyknocker\struct\validation\ValidationResult;
use tommyknocker\struct\validation\ValidationRule;

/** Explicitly allow null while applying the wrapped rule to all other values. */
final class NullableRule implements ValidationRule
{
    public function __construct(private readonly ValidationRule $rule)
    {
    }

    public function validate(mixed $value): ValidationResult
    {
        return $value === null ? ValidationResult::valid() : $this->rule->validate($value);
    }
}
