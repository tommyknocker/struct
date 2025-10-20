<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

final class SimpleStruct extends Struct
{
    #[Field('string')]
    public readonly string $name;

    #[Field('int')]
    public readonly int $age;
}

final class StructWithAlias extends Struct
{
    #[Field('string', alias: 'user_name')]
    public readonly string $name;

    #[Field('int')]
    public readonly int $age;
}

final class StructStrictModeTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset strict mode before each test
        Struct::$strictMode = false;
    }

    protected function tearDown(): void
    {
        // Ensure strict mode is disabled after tests
        Struct::$strictMode = false;
    }

    public function testNonStrictModeAllowsExtraFields(): void
    {
        Struct::$strictMode = false;

        $struct = new SimpleStruct([
            'name' => 'John',
            'age' => 30,
            'extra_field' => 'should be ignored',
            'another_field' => 123,
        ]);

        $this->assertSame('John', $struct->name);
        $this->assertSame(30, $struct->age);
    }

    public function testStrictModeThrowsOnExtraField(): void
    {
        Struct::$strictMode = true;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unknown field: extra_field');

        new SimpleStruct([
            'name' => 'John',
            'age' => 30,
            'extra_field' => 'not allowed!',
        ]);
    }

    public function testStrictModeThrowsOnMultipleExtraFields(): void
    {
        Struct::$strictMode = true;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unknown field:');

        new SimpleStruct([
            'name' => 'John',
            'age' => 30,
            'extra1' => 'value1',
            'extra2' => 'value2',
        ]);
    }

    public function testStrictModeAllowsValidFields(): void
    {
        Struct::$strictMode = true;

        $struct = new SimpleStruct([
            'name' => 'John',
            'age' => 30,
        ]);

        $this->assertSame('John', $struct->name);
        $this->assertSame(30, $struct->age);
    }

    public function testStrictModeWithAliasAllowsPropertyName(): void
    {
        Struct::$strictMode = true;

        $struct = new StructWithAlias([
            'name' => 'John',  // Using property name
            'age' => 30,
        ]);

        $this->assertSame('John', $struct->name);
    }

    public function testStrictModeWithAliasAllowsAliasName(): void
    {
        Struct::$strictMode = true;

        $struct = new StructWithAlias([
            'user_name' => 'John',  // Using alias
            'age' => 30,
        ]);

        $this->assertSame('John', $struct->name);
    }

    public function testStrictModeWithAliasThrowsOnUnknownField(): void
    {
        Struct::$strictMode = true;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unknown field: invalid_field');

        new StructWithAlias([
            'user_name' => 'John',
            'age' => 30,
            'invalid_field' => 'not allowed',
        ]);
    }

    public function testStrictModeCanBeToggledBetweenInstances(): void
    {
        // First instance: strict mode off
        Struct::$strictMode = false;
        $struct1 = new SimpleStruct([
            'name' => 'John',
            'age' => 30,
            'extra' => 'ignored',
        ]);
        $this->assertSame('John', $struct1->name);

        // Second instance: strict mode on
        Struct::$strictMode = true;
        $this->expectException(RuntimeException::class);
        new SimpleStruct([
            'name' => 'Jane',
            'age' => 25,
            'extra' => 'will throw',
        ]);
    }

    public function testStrictModeWithNullableFields(): void
    {
        Struct::$strictMode = true;

        $struct = new class (['name' => 'test', 'optional' => null]) extends Struct {
            #[Field('string')]
            public readonly string $name;

            #[Field('string', nullable: true)]
            public readonly ?string $optional;
        };

        $this->assertSame('test', $struct->name);
        $this->assertNull($struct->optional);
    }

    public function testStrictModeWithDefaultValues(): void
    {
        Struct::$strictMode = true;

        $struct = new class (['name' => 'test']) extends Struct {
            #[Field('string')]
            public readonly string $name;

            #[Field('int', default: 42)]
            public readonly int $value;
        };

        $this->assertSame('test', $struct->name);
        $this->assertSame(42, $struct->value);
    }
}
