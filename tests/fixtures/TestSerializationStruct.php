<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests\fixtures;

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

/**
 * Test struct for serialization system
 */
final class TestSerializationStruct extends Struct
{
    #[Field('string')]
    public readonly string $name;

    #[Field('int')]
    public readonly int $age;
}
