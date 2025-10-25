<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests\fixtures;

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

/**
 * Test struct for missing field with alias
 */
final class RequiredFieldWithAliasStruct extends Struct
{
    #[Field('string', alias: 'user_name')]
    public readonly string $name;
}
