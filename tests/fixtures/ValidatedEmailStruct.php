<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests\fixtures;

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\validation\rules\EmailRule;

/**
 * Test struct for validation rules
 */
final class ValidatedEmailStruct extends Struct
{
    #[Field('string', validationRules: [new EmailRule()])]
    public readonly string $email;
}
