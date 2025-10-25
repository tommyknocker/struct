<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
use tommyknocker\struct\tests\fixtures\DummyStruct;

final class StructToJsonTest extends TestCase
{
    public function testDefaultToJson(): void
    {
        $s = new DummyStruct(['name' => 'Alice', 'age' => 25, 'isMarried' => true]);

        $json = $s->toJson();

        $this->assertJson($json);
        $this->assertSame(
            '{"name":"Alice","age":25,"isMarried":true}',
            $json,
            'Default toJson() should return compact JSON'
        );
    }

    public function testPrettyToJson(): void
    {
        $s = new DummyStruct(['name' => 'Bob', 'age' => 30, 'isMarried' => false]);

        $json = $s->toJson(pretty: true);

        $this->assertJson($json);
        $this->assertStringContainsString("\n", $json, 'Pretty JSON should contain line breaks');
        $this->assertStringContainsString('"name": "Bob"', $json);
        $this->assertStringContainsString('"age": 30', $json);
        $this->assertStringContainsString('"isMarried": false', $json);
    }


    public function testCustomFlagsToJson(): void
    {
        $s = new DummyStruct(['name' => 'Charlie', 'age' => 35, 'isMarried' => true]);

        // Force escaping certain characters
        $json = $s->toJson(flags: JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

        $this->assertJson($json);
        $this->assertStringContainsString('Charlie', $json);
        $this->assertStringContainsString('35', $json);
        $this->assertStringContainsString('"isMarried":true', $json);
    }

    public function testPrettyAndCustomFlagsTogether(): void
    {
        $s = new DummyStruct(['name' => 'Diana', 'age' => 40, 'isMarried' => false]);

        $json = $s->toJson(pretty: true, flags: JSON_HEX_TAG);

        $this->assertJson($json);
        $this->assertStringContainsString("\n", $json, 'Pretty JSON should contain line breaks');
        $this->assertStringContainsString('Diana', $json);
        $this->assertStringContainsString('40', $json);
        $this->assertStringContainsString('"isMarried": false', $json);
    }

    public function testToJsonWithNullValues(): void
    {
        $s = new DummyStruct(['name' => 'Test', 'age' => null, 'isMarried' => false]);

        $json = $s->toJson();

        $this->assertJson($json);
        $this->assertStringContainsString('"age":null', $json);
        $this->assertStringContainsString('"isMarried":false', $json);
    }

    public function testToJsonWithSpecialCharacters(): void
    {
        $s = new DummyStruct(['name' => 'Test "quoted" & <tagged>', 'age' => 25, 'isMarried' => true]);

        $json = $s->toJson();

        $this->assertJson($json);
        $this->assertStringContainsString('Test \"quoted\" & <tagged>', $json);
    }

    public function testToJsonWithUnicodeCharacters(): void
    {
        $s = new DummyStruct(['name' => 'Тест 测试 🚀', 'age' => 25, 'isMarried' => false]);

        $json = $s->toJson();

        $this->assertJson($json);
        $this->assertStringContainsString('Тест 测试 🚀', $json);
    }

    public function testToJsonWithEmptyString(): void
    {
        $s = new DummyStruct(['name' => '', 'age' => 0, 'isMarried' => false]);

        $json = $s->toJson();

        $this->assertJson($json);
        $this->assertStringContainsString('"name":""', $json);
        $this->assertStringContainsString('"age":0', $json);
    }

    public function testToJsonWithZeroValues(): void
    {
        $s = new DummyStruct(['name' => 'Zero', 'age' => 0, 'isMarried' => false]);

        $json = $s->toJson();

        $this->assertJson($json);
        $this->assertStringContainsString('"age":0', $json);
        $this->assertStringContainsString('"isMarried":false', $json);
    }
}
