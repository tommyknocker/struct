<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

final class MixedTypeStruct extends Struct
{
    #[Field('string')]
    public readonly string $type;

    #[Field('mixed')]
    public readonly mixed $data;
}

final class DateTimeStruct extends Struct
{
    #[Field('string')]
    public readonly string $name;

    #[Field(DateTimeImmutable::class)]
    public readonly DateTimeImmutable $createdAt;

    #[Field(DateTimeImmutable::class, nullable: true)]
    public readonly ?DateTimeImmutable $updatedAt;
}

final class StructAdvancedFeaturesTest extends TestCase
{
    // Mixed type tests
    public function testMixedTypeString(): void
    {
        $struct = new MixedTypeStruct([
            'type' => 'string',
            'data' => 'hello world',
        ]);

        $this->assertSame('hello world', $struct->data);
        $this->assertIsString($struct->data);
    }

    public function testMixedTypeArray(): void
    {
        $struct = new MixedTypeStruct([
            'type' => 'array',
            'data' => ['key' => 'value'],
        ]);

        $this->assertIsArray($struct->data);
        $this->assertSame(['key' => 'value'], $struct->data);
    }

    public function testMixedTypeInt(): void
    {
        $struct = new MixedTypeStruct([
            'type' => 'int',
            'data' => 42,
        ]);

        $this->assertSame(42, $struct->data);
        $this->assertIsInt($struct->data);
    }

    public function testMixedTypeNull(): void
    {
        $struct = new class (['type' => 'null', 'data' => null]) extends Struct {
            #[Field('string')]
            public readonly string $type;

            #[Field('mixed', nullable: true)]
            public readonly mixed $data;
        };

        $this->assertNull($struct->data);
    }

    // DateTime tests
    public function testDateTimeFromString(): void
    {
        $struct = new DateTimeStruct([
            'name' => 'Event',
            'createdAt' => '2024-01-15 10:30:00',
            'updatedAt' => null,
        ]);

        $this->assertInstanceOf(DateTimeImmutable::class, $struct->createdAt);
        $this->assertSame('2024-01-15', $struct->createdAt->format('Y-m-d'));
        $this->assertNull($struct->updatedAt);
    }

    public function testDateTimeFromObject(): void
    {
        $date = new DateTimeImmutable('2024-01-15 10:30:00');
        $struct = new DateTimeStruct([
            'name' => 'Event',
            'createdAt' => $date,
            'updatedAt' => null,
        ]);

        $this->assertSame($date, $struct->createdAt);
    }

    public function testDateTimeInvalidString(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('invalid datetime string');

        new DateTimeStruct([
            'name' => 'Event',
            'createdAt' => 'invalid-date',
            'updatedAt' => null,
        ]);
    }

    public function testDateTimeInvalidType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must be DateTimeInterface or string');

        new DateTimeStruct([
            'name' => 'Event',
            'createdAt' => 12345,
            'updatedAt' => null,
        ]);
    }

    // fromJson tests
    public function testFromJson(): void
    {
        $json = '{"type":"test","data":"value"}';
        $struct = MixedTypeStruct::fromJson($json);

        $this->assertSame('test', $struct->type);
        $this->assertSame('value', $struct->data);
    }

    public function testFromJsonInvalid(): void
    {
        $this->expectException(\JsonException::class);

        MixedTypeStruct::fromJson('invalid json');
    }

    public function testFromJsonNotArray(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('JSON must decode to an array');

        MixedTypeStruct::fromJson('"just a string"');
    }

    // with() method tests
    public function testWithMethod(): void
    {
        $original = new MixedTypeStruct([
            'type' => 'original',
            'data' => 'original data',
        ]);

        $modified = $original->with(['data' => 'modified data']);

        $this->assertSame('original data', $original->data);
        $this->assertSame('modified data', $modified->data);
        $this->assertNotSame($original, $modified);
    }

    public function testWithMethodMultipleFields(): void
    {
        $original = new MixedTypeStruct([
            'type' => 'original',
            'data' => 'original data',
        ]);

        $modified = $original->with([
            'type' => 'modified',
            'data' => 'modified data',
        ]);

        $this->assertSame('original', $original->type);
        $this->assertSame('modified', $modified->type);
    }

    // toArray tests
    public function testToArrayFlat(): void
    {
        $struct = new MixedTypeStruct([
            'type' => 'test',
            'data' => ['key' => 'value'],
        ]);

        $array = $struct->toArray();

        $this->assertIsArray($array);
        $this->assertSame('test', $array['type']);
        $this->assertSame(['key' => 'value'], $array['data']);
    }

    // ArrayAccess readonly tests
    public function testOffsetSetThrows(): void
    {
        $struct = new MixedTypeStruct([
            'type' => 'test',
            'data' => 'value',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot modify readonly struct properties');

        $struct['type'] = 'modified';
    }

    public function testOffsetUnsetThrows(): void
    {
        $struct = new MixedTypeStruct([
            'type' => 'test',
            'data' => 'value',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot unset readonly struct properties');

        unset($struct['type']);
    }

    public function testOffsetExistsWithNonString(): void
    {
        $struct = new MixedTypeStruct([
            'type' => 'test',
            'data' => 'value',
        ]);

        $this->assertFalse(isset($struct[123]));
        $this->assertFalse(isset($struct[null]));
    }
}
