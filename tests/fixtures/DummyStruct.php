<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests\fixtures;

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

final class DummyStruct extends Struct
{
    #[Field('string')]
    public readonly string $name;

    #[Field('int', nullable: true)]
    public readonly ?int $age;

    #[Field('bool')]
    public readonly bool $isMarried;
}
