<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests\fixtures;

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

/**
 * Test struct for nested serialization
 */
final class TestNestedStruct extends Struct
{
    #[Field('string')]
    public readonly string $name;

    #[Field(TestSerializationStruct::class)]
    public readonly TestSerializationStruct $child;
}
