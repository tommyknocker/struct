<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests\fixtures;

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

/**
 * Test struct for cache system
 */
final class TestCacheStruct extends Struct
{
    #[Field('string')]
    public readonly string $name;

    #[Field('int')]
    public readonly int $age;
}
