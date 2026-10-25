<?php

declare(strict_types=1);

namespace tommyknocker\struct\validation\rules;

use InvalidArgumentException;
use tommyknocker\struct\validation\ValidationResult;
use tommyknocker\struct\validation\ValidationRule;

/** Canonical hex-and-dash UUIDs: RFC variant, versions 1–8, plus Nil/Max without a version filter. */
final class UuidRule implements ValidationRule
{
    public function __construct(private readonly ?int $version = null)
    {
        if ($version !== null && ($version < 1 || $version > 8)) {
            throw new InvalidArgumentException('UUID version must be between 1 and 8');
        }
    }

    public function validate(mixed $value): ValidationResult
    {
        if (!is_string($value)) {
            return ValidationResult::invalid('UUID must be a string');
        }
        if ($this->version === null && in_array(strtolower($value), [
            '00000000-0000-0000-0000-000000000000',
            'ffffffff-ffff-ffff-ffff-ffffffffffff',
        ], true)) {
            return ValidationResult::valid();
        }
        if (preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-([1-8])[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/i', $value, $match) !== 1
            || ($this->version !== null && (int) $match[1] !== $this->version)) {
            return ValidationResult::invalid('Value must be a canonical UUID' . ($this->version === null ? '' : ' of version ' . $this->version));
        }

        return ValidationResult::valid();
    }
}
