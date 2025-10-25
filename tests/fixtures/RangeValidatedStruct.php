<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests\fixtures;

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\validation\rules\RangeRule;

/**
 * Test struct for range validation
 */
final class RangeValidatedStruct extends Struct
{
    #[Field('int', validationRules: [new RangeRule(1, 120)])]
    public readonly int $age;
}
