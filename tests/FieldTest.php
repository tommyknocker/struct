<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
use tommyknocker\struct\Field;

final class FieldTest extends TestCase
{
    public function testHasDefaultReturnsTrueWhenDefaultIsNotNull(): void
    {
        $field = new Field('string', default: 'default value');
        $this->assertTrue($field->hasDefault());
    }

    public function testHasDefaultReturnsTrueWhenNullable(): void
    {
        $field = new Field('string', nullable: true);
        $this->assertTrue($field->hasDefault());
    }

    public function testHasDefaultReturnsTrueWhenBothDefaultAndNullable(): void
    {
        $field = new Field('string', nullable: true, default: 'default value');
        $this->assertTrue($field->hasDefault());
    }

    public function testHasDefaultReturnsFalseWhenNoDefaultAndNotNullable(): void
    {
        $field = new Field('string');
        $this->assertFalse($field->hasDefault());
    }

    public function testHasDefaultReturnsFalseWhenDefaultIsNullAndNotNullable(): void
    {
        $field = new Field('string', nullable: false, default: null);
        $this->assertFalse($field->hasDefault());
    }

    public function testHasDefaultWithDifferentDefaultValues(): void
    {
        $field1 = new Field('int', default: 0);
        $this->assertTrue($field1->hasDefault());

        $field2 = new Field('bool', default: false);
        $this->assertTrue($field2->hasDefault());

        $field3 = new Field('array', default: []);
        $this->assertTrue($field3->hasDefault());
    }
}
