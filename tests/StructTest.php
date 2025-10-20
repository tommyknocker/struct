<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use tommyknocker\struct\tests\fixtures\DummyStruct;

final class StructTest extends TestCase
{
    public function testValidData(): void
    {
        $struct = new DummyStruct(['name' => 'Alice', 'age' => 30, 'isMarried' => true]);
        $this->assertSame('Alice', $struct->name);
        $this->assertSame(30, $struct->age);
    }

    public function testNullableField(): void
    {
        $struct = new DummyStruct(['name' => 'Bob', 'age' => null, 'isMarried' => false]);
        $this->assertNull($struct->age);
    }

    public function testMissingFieldThrows(): void
    {
        $this->expectException(RuntimeException::class);
        new DummyStruct(['age' => 20]);
    }

    public function testWrongTypeThrows(): void
    {
        $this->expectException(RuntimeException::class);
        new DummyStruct(['name' => 'Charlie', 'age' => 'not-int', 'isMarried' => false]);
    }

    public function testJsonSerialize(): void
    {
        $struct = new DummyStruct(['name' => 'Alice', 'age' => 25, 'isMarried' => false]);
        $this->assertSame(['name' => 'Alice', 'age' => 25, 'isMarried' => false], $struct->jsonSerialize());
    }

    public function testArrayAccess(): void
    {
        $struct = new DummyStruct(['name' => 'Alice', 'age' => 25, 'isMarried' => true]);
        $this->assertTrue(isset($struct['name']));
        $this->assertSame('Alice', $struct['name']);
        $this->assertTrue(isset($struct['age']));
        $this->assertSame(25, $struct['age']);
        $this->assertTrue(isset($struct['isMarried']));
        $this->assertTrue($struct['isMarried']);
    }

    public function testToJsonIncludesBool(): void
    {
        $s = new DummyStruct(['name' => 'Alice', 'age' => 25, 'isMarried' => true]);
        $json = $s->toJson();

        $this->assertJson($json);
        $this->assertStringContainsString('"isMarried":true', $json);
    }
}
