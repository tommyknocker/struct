<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests\fixtures;

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

/**
 * Test struct for validation testing
 */
final class ValidatedUser extends Struct
{
    #[Field('string')]
    public readonly string $username;

    #[Field('string', validator: EmailValidator::class)]
    public readonly string $email;

    #[Field('int', validator: RangeValidator::class)]
    public readonly int $score;
}
