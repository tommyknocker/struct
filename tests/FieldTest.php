<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
use tommyknocker\struct\Field;

final class FieldTest extends TestCase
{
    public function testFieldsAreRequiredUnlessExplicitlyOptional(): void
    {
        $this->assertTrue((new Field())->required);
        $this->assertFalse((new Field(required: false))->required);
    }

    public function testDefaultsAreIndependentOfRequiredAndNullability(): void
    {
        $this->assertFalse((new Field())->hasDefault());
        $this->assertFalse((new Field(required: false))->hasDefault());
        foreach ([null, false, 0, [], 'default'] as $default) {
            $this->assertTrue((new Field(default: $default))->hasDefault());
        }
    }
}
