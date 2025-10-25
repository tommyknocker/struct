<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests\fixtures;

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

/**
 * Test struct for legacy validator
 */
final class LegacyValidatorStruct extends Struct
{
    #[Field('string', validator: EmailValidator::class)]
    public readonly string $email;
}
