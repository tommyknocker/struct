<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
use tommyknocker\struct\serialization\JsonSerializer;
use tommyknocker\struct\serialization\SerializerInterface;
use tommyknocker\struct\tests\fixtures\TestNestedStruct;
use tommyknocker\struct\tests\fixtures\TestSerializationStruct;

final class SerializationSystemTest extends TestCase
{
    public function testJsonSerializerImplementsInterface(): void
    {
        $serializer = new JsonSerializer();
        $this->assertInstanceOf(SerializerInterface::class, $serializer);
    }

    public function testSerializeToArray(): void
    {
        $serializer = new JsonSerializer();
        $struct = new TestSerializationStruct(['name' => 'John', 'age' => 30]);

        $array = $serializer->serialize($struct);

        $this->assertIsArray($array);
        $this->assertSame('John', $array['name']);
        $this->assertSame(30, $array['age']);
    }

    public function testDeserializeFromArray(): void
    {
        $serializer = new JsonSerializer();
        $data = ['name' => 'Jane', 'age' => 25];

        $struct = $serializer->deserialize($data, TestSerializationStruct::class);

        $this->assertInstanceOf(TestSerializationStruct::class, $struct);
        $this->assertSame('Jane', $struct->name);
        $this->assertSame(25, $struct->age);
    }

    public function testToJson(): void
    {
        $serializer = new JsonSerializer();
        $struct = new TestSerializationStruct(['name' => 'Bob', 'age' => 35]);

        $json = $serializer->toJson($struct);

        $this->assertJson($json);
        $this->assertStringContainsString('"name":"Bob"', $json);
        $this->assertStringContainsString('"age":35', $json);
    }

    public function testToJsonPretty(): void
    {
        $serializer = new JsonSerializer();
        $struct = new TestSerializationStruct(['name' => 'Alice', 'age' => 28]);

        $json = $serializer->toJson($struct, true);

        $this->assertJson($json);
        $this->assertStringContainsString("\n", $json); // Pretty print should have newlines
    }

    public function testFromJson(): void
    {
        $serializer = new JsonSerializer();
        $json = '{"name":"Charlie","age":40}';

        $struct = $serializer->fromJson($json, TestSerializationStruct::class);

        $this->assertInstanceOf(TestSerializationStruct::class, $struct);
        $this->assertSame('Charlie', $struct->name);
        $this->assertSame(40, $struct->age);
    }

    public function testFromJsonInvalidJson(): void
    {
        $serializer = new JsonSerializer();

        $this->expectException(\JsonException::class);

        $serializer->fromJson('invalid json', TestSerializationStruct::class);
    }

    public function testFromJsonNotArray(): void
    {
        $serializer = new JsonSerializer();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('JSON must decode to an array');

        $serializer->fromJson('"just a string"', TestSerializationStruct::class);
    }

    public function testSerializeWithNestedStruct(): void
    {
        $serializer = new JsonSerializer();
        $struct = new TestNestedStruct([
            'name' => 'Parent',
            'child' => ['name' => 'Child', 'age' => 10],
        ]);

        $array = $serializer->serialize($struct);

        $this->assertIsArray($array);
        $this->assertSame('Parent', $array['name']);
        $this->assertIsArray($array['child']);
        $this->assertSame('Child', $array['child']['name']);
        $this->assertSame(10, $array['child']['age']);
    }

    public function testDeserializeWithNestedStruct(): void
    {
        $serializer = new JsonSerializer();
        $data = [
            'name' => 'Parent',
            'child' => ['name' => 'Child', 'age' => 10],
        ];

        $struct = $serializer->deserialize($data, TestNestedStruct::class);

        /** @var TestNestedStruct $struct */
        $this->assertInstanceOf(TestSerializationStruct::class, $struct->child);
        $this->assertSame('Child', $struct->child->name);
        $this->assertSame(10, $struct->child->age);
    }
}
