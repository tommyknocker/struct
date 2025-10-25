<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests\fixtures;

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

/**
 * Test struct for metadata system with non-field property
 */
final class TestStructWithNonField extends Struct
{
    #[Field('string')]
    public readonly string $name;

    public string $nonField = 'test';
}
