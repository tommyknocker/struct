<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests\fixtures;

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\transformation\StringToLowerTransformer;
use tommyknocker\struct\transformation\StringToUpperTransformer;

/**
 * Test struct for multiple transformers
 */
final class MultipleTransformersStruct extends Struct
{
    #[Field('string', transformers: [
        new StringToLowerTransformer(),
        new StringToUpperTransformer(),
    ])]
    public readonly string $name;
}
