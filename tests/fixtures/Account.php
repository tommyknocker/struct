<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests\fixtures;

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

final class Account extends Struct
{
    #[Field(UserType::class)]
    public readonly UserType $type;

    #[Field('string')]
    public readonly string $email;
}
