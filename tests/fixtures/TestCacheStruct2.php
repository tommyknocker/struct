<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests\fixtures;

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

/**
 * Test struct for cache system (second variant)
 */
final class TestCacheStruct2 extends Struct
{
    #[Field('string')]
    public readonly string $email;
}
