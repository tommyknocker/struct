<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests\fixtures;

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

/**
 * Test struct for union types
 */
final class UnionTypeStruct extends Struct
{
    #[Field(['string', 'int'])]
    public readonly string|int $value;
}
