<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests\fixtures;

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\transformation\StringToUpperTransformer;

/**
 * Test struct for transformers
 */
final class TransformedNameStruct extends Struct
{
    #[Field('string', transformers: [new StringToUpperTransformer()])]
    public readonly string $name;
}
