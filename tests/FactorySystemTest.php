<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
use tommyknocker\struct\exception\ValidationException;
use tommyknocker\struct\factory\StructFactory;
use tommyknocker\struct\tests\fixtures\TestFactoryStruct;

final class FactorySystemTest extends TestCase
{
    public function testStructFactoryCreate(): void
    {
        $factory = new StructFactory();

        $struct = $factory->create(TestFactoryStruct::class, ['name' => 'John', 'age' => 30]);

        $this->assertInstanceOf(TestFactoryStruct::class, $struct);
        $this->assertSame('John', $struct->name);
        $this->assertSame(30, $struct->age);
    }

    public function testStructFactoryCreateFromJson(): void
    {
        $factory = new StructFactory();

        $json = '{"name":"Jane","age":25}';
        $struct = $factory->createFromJson(TestFactoryStruct::class, $json);

        $this->assertInstanceOf(TestFactoryStruct::class, $struct);
        $this->assertSame('Jane', $struct->name);
        $this->assertSame(25, $struct->age);
    }

    public function testStructFactoryCreateFromJsonWithFlags(): void
    {
        $factory = new StructFactory();

        $json = '{"name":"Bob","age":35}';
        $struct = $factory->createFromJson(TestFactoryStruct::class, $json, JSON_THROW_ON_ERROR);

        $this->assertInstanceOf(TestFactoryStruct::class, $struct);
        $this->assertSame('Bob', $struct->name);
        $this->assertSame(35, $struct->age);
    }

    public function testStructFactoryCreateFromJsonInvalidJson(): void
    {
        $factory = new StructFactory();

        $this->expectException(\JsonException::class);

        $factory->createFromJson(TestFactoryStruct::class, 'invalid json');
    }

    public function testStructFactoryCreateFromJsonNonArray(): void
    {
        $factory = new StructFactory();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('JSON must decode to an array');

        $factory->createFromJson(TestFactoryStruct::class, '"not an array"');
    }

    public function testStructFactoryCreateFromJsonEmptyObject(): void
    {
        $factory = new StructFactory();

        $this->expectException(\tommyknocker\struct\exception\FieldNotFoundException::class);
        $this->expectExceptionMessage('Missing required field: name');

        $factory->createFromJson(TestFactoryStruct::class, '{}');
    }

    public function testStructFactoryCreateFromJsonWithNestedData(): void
    {
        $factory = new StructFactory();

        $json = '{"name":"Alice","age":28,"extra":"ignored"}';
        $struct = $factory->createFromJson(TestFactoryStruct::class, $json);

        $this->assertInstanceOf(TestFactoryStruct::class, $struct);
        $this->assertSame('Alice', $struct->name);
        $this->assertSame(28, $struct->age);
    }

    public function testStructFactoryValidationFailure(): void
    {
        $factory = new StructFactory();

        $this->expectException(ValidationException::class);

        $factory->create(TestFactoryStruct::class, ['name' => 'John', 'age' => 'not-an-int']);
    }
}
