<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests\fixtures;

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

/**
 * Test struct for missing field exception
 */
final class RequiredFieldStruct extends Struct
{
    #[Field('string')]
    public readonly string $name;
}
