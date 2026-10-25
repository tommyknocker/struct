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

    #[Field('string', validationRules: [new \tommyknocker\struct\validation\rules\EmailRule()])]
    public readonly string $email;

    #[Field('int', validationRules: [new \tommyknocker\struct\validation\rules\RangeRule(1, 100)])]
    public readonly int $score;
}
