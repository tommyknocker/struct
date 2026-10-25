<?php

declare(strict_types=1);

namespace tommyknocker\struct\validation\rules;

use InvalidArgumentException;
use tommyknocker\struct\validation\ValidationResult;
use tommyknocker\struct\validation\ValidationRule;
use UnitEnum;

/** Strict, case-sensitive membership for scalar/null values and enum cases. */
final class OneOfRule implements ValidationRule
{
    /** @var list<scalar|null|UnitEnum> */
    private readonly array $values;

    /** @param array<mixed> $values Nonempty allowed values; keys are ignored. */
    public function __construct(array $values)
    {
        if ($values === []) {
            throw new InvalidArgumentException('Allowed values must not be empty');
        }
        $allowed = [];
        foreach ($values as $value) {
            if ((!is_scalar($value) && $value !== null && !$value instanceof UnitEnum)
                || (is_float($value) && !is_finite($value))) {
                throw new InvalidArgumentException('Allowed values must be finite scalars, null, or enum cases');
            }
            $allowed[] = $value;
        }
        $this->values = $allowed;
    }

    public function validate(mixed $value): ValidationResult
    {
        return in_array($value, $this->values, true)
            ? ValidationResult::valid()
            : ValidationResult::invalid('Value must be one of the allowed values');
    }
}
