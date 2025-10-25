<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests\fixtures;

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

/**
 * Test struct for metadata system
 */
final class TestStruct extends Struct
{
    #[Field('string', alias: 'user_name')]
    public readonly string $name;

    #[Field('int', nullable: true)]
    public readonly ?int $age;
}
