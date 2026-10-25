<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use JsonException;
use LogicException;
use PHPUnit\Framework\TestCase;
use stdClass;
use tommyknocker\struct\exception\FieldNotFoundException;
use tommyknocker\struct\exception\ValidationException;
use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\transformation\TransformerInterface;
use tommyknocker\struct\validation\rules\RangeRule;

final class AddSuffix implements TransformerInterface
{
    public function transform(mixed $value): mixed
    {
        return is_string($value) ? $value . '!' : $value;
    }
}

final class TransformedPayload extends Struct
{
    #[Field(alias: 'wire_name', outputAlias: 'output', transformers: [new AddSuffix()])]
    public readonly string $name;
    #[Field(default: 0)]
    public readonly int $count;
    public string $note = '';
}

final class RecursivePayload extends Struct
{
    #[Field]
    public readonly ?self $child;
}

final class ArrayConstructedObject
{
    public static bool $called = false;
    /** @param array<string, mixed> $data */
    public function __construct(public readonly array $data)
    {
        self::$called = true;
    }
}

final class ObjectPayload extends Struct
{
    #[Field]
    public readonly ArrayConstructedObject $object;
}

final class LifecycleContractTest extends TestCase
{
    public function testWithTransformsOnlyReplacementsAndPreservesOtherState(): void
    {
        $source = new TransformedPayload(['wire_name' => 'Alice']);
        $source->note = 'application state';
        $same = $source->with([]);
        $updated = $same->with(['count' => 2]);
        $renamed = $updated->with(['wire_name' => 'Bob']);
        $this->assertNotSame($source, $same);
        $this->assertSame('Alice!', $source->name);
        $this->assertSame('Alice!', $same->name);
        $this->assertSame('Alice!', $updated->name);
        $this->assertSame('Bob!', $renamed->name);
        $this->assertSame(2, $renamed->count);
        $this->assertSame('application state', $renamed->note);
        $this->assertSame(['output' => 'Bob!', 'count' => 2], $renamed->toArray());
    }

    public function testWithAlwaysRejectsMisspelledChanges(): void
    {
        $source = new TransformedPayload(['name' => 'Alice']);
        $this->expectException(ValidationException::class);
        $source->with(['naem' => 'Bob']);
    }

    public function testWithRejectsOutputAliasesAsInput(): void
    {
        $source = new TransformedPayload(['name' => 'Alice']);
        $this->expectException(ValidationException::class);
        $source->with(['output' => 'Bob']);
    }

    public function testWithValidatesChangedValuesWithoutChangingSource(): void
    {
        $source = new TransformedPayload(['name' => 'Alice']);

        try {
            $source->with(['count' => '2']);
            $this->fail('Expected invalid replacement');
        } catch (ValidationException $e) {
            $this->assertSame('count', $e->fieldName);
        }
        $this->assertSame(0, $source->count);
        $this->assertSame('Alice!', $source->name);
    }

    public function testNullableDoesNotMeanOptional(): void
    {
        $this->expectException(FieldNotFoundException::class);
        new RecursivePayload([]);
    }

    public function testOptionalNullableFieldIsExplicit(): void
    {
        $dto = new class ([]) extends Struct {
            #[Field(required: false)]
            public readonly ?string $name;
        };
        $this->assertNull($dto->name);
    }

    public function testOptionalNonNullableWithoutDefaultIsASchemaError(): void
    {
        $this->expectException(LogicException::class);
        new class ([]) extends Struct {
            #[Field(required: false)]
            public readonly string $name;
        };
    }

    public function testMutableMappedPropertiesAreASchemaError(): void
    {
        $this->expectException(LogicException::class);
        new class (['name' => 'Alice']) extends Struct {
            #[Field]
            public string $name;
        };
    }

    public function testConflictingInputKeysAreNeverSilentlyResolved(): void
    {
        $this->expectException(ValidationException::class);
        new TransformedPayload(['name' => 'Alice', 'wire_name' => 'Bob']);
    }

    public function testWithConflictingKeysIsRejected(): void
    {
        $source = new TransformedPayload(['name' => 'Alice']);
        $this->expectException(ValidationException::class);
        $source->with(['name' => 'Bob', 'wire_name' => 'Charlie']);
    }

    public function testNullableCollectionElementsAreIndependentOfCollectionNullability(): void
    {
        $dto = new class (['values' => ['a', null]]) extends Struct {
            #[Field(['string', 'null'], collection: \tommyknocker\struct\CollectionType::Preserve)]
            public readonly array $values;
        };
        $this->assertSame(['a', null], $dto->values);
    }

    public function testUnionPreservesAnExactMatchBeforeConversion(): void
    {
        $dto = new class (['value' => '2026-10-25']) extends Struct {
            #[Field([DateTimeImmutable::class, 'string'])]
            public readonly DateTimeImmutable|string $value;
        };
        $this->assertSame('2026-10-25', $dto->value);
    }

    public function testUnionCanTryAnotherDtoAfterAMissingField(): void
    {
        $dto = new class (['value' => ['child' => null]]) extends Struct {
            #[Field([TransformedPayload::class, RecursivePayload::class])]
            public readonly TransformedPayload|RecursivePayload $value;
        };
        $this->assertInstanceOf(RecursivePayload::class, $dto->value);
    }

    public function testFloatMappingAcceptsJsonWholeNumbersButNotNumericStrings(): void
    {
        $dto = new class (['amount' => 1]) extends Struct {
            #[Field]
            public readonly float $amount;
        };
        $this->assertSame(1.0, $dto->amount);
        $this->expectException(ValidationException::class);
        $dto->with(['amount' => '1']);
    }

    public function testNonFiniteNumericInputIsRejected(): void
    {
        $this->expectException(ValidationException::class);
        new class (['amount' => NAN]) extends Struct {
            #[Field]
            public readonly float $amount;
        };
    }

    public function testJsonPreservesObjectsAndArraysInsideMixed(): void
    {
        $dto = new class (['data' => null]) extends Struct {
            #[Field]
            public readonly mixed $data;
        };
        $json = '{"data":{"object":{},"array":[],"numeric":{"0":"first"}}}';
        $restored = $dto::fromJson($json);
        $this->assertInstanceOf(stdClass::class, $restored->data);
        $this->assertSame($json, $restored->toJson());
        $this->assertSame(['data' => ['object' => [], 'array' => [], 'numeric' => ['first']]], $restored->toArray());
    }

    public function testJsonRootListsAndScalarsAreRejected(): void
    {
        foreach (['[]', '[1]', 'null', '"text"', '1'] as $json) {
            try {
                RecursivePayload::fromJson($json);
                $this->fail('Expected invalid root');
            } catch (ValidationException $e) {
                $this->assertSame('$', $e->fieldName);
            }
        }
    }

    public function testMalformedJsonAlwaysThrows(): void
    {
        $this->expectException(JsonException::class);
        RecursivePayload::fromJson('{', flags: 0);
    }

    public function testNestedStrictErrorsIncludeAPath(): void
    {
        $dto = new class (['child' => new StrictPayload(['name' => 'Alice'])]) extends Struct {
            #[Field]
            public readonly StrictPayload $child;
        };

        try {
            $dto::fromJson('{"child":{"name":"Alice","extra":1}}');
            $this->fail('Expected nested unknown key');
        } catch (ValidationException $e) {
            $this->assertSame('child.extra', $e->fieldName);
            $this->assertSame(1, $e->value);
        }
    }

    public function testArbitraryArrayConstructorsAreNotInvoked(): void
    {
        ArrayConstructedObject::$called = false;

        try {
            new ObjectPayload(['object' => []]);
            $this->fail('Expected explicit transformer to be required');
        } catch (ValidationException $e) {
            $this->assertSame('object', $e->fieldName);
        }
        $this->assertFalse(ArrayConstructedObject::$called);
    }

    public function testMutableDateDefaultsAreNotSharedBetweenInstances(): void
    {
        $first = new class ([]) extends Struct {
            #[Field(default: new DateTime('2026-10-25'))]
            public readonly DateTime $date;
        };
        $second = new ($first::class)([]);
        $first->date->modify('+1 day');
        $this->assertSame('2026-10-25', $second->date->format('Y-m-d'));
    }

    public function testRecursiveInputHasABoundedMappingDepth(): void
    {
        $data = [];
        $data['child'] = &$data;
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Maximum mapping depth exceeded');
        new RecursivePayload($data);
    }

    public function testArrayAccessDoesNotExposeUnmarkedState(): void
    {
        $dto = new TransformedPayload(['name' => 'Alice']);
        $this->assertFalse(isset($dto['note']));
        $this->expectException(FieldNotFoundException::class);
        $dto['note'];
    }

    public function testInvalidSchemaRulesAreReportedBeforeMapping(): void
    {
        $this->expectException(LogicException::class);
        new class (['name' => 'Alice']) extends Struct {
            #[Field(validationRules: ['not a rule'])] // @phpstan-ignore argument.type (deliberately invalid runtime schema)
            public readonly string $name;
        };
    }

    public function testInvalidSchemaTransformersAreReportedBeforeMapping(): void
    {
        $this->expectException(LogicException::class);
        new class (['name' => 'Alice']) extends Struct {
            #[Field(transformers: ['not a transformer'])] // @phpstan-ignore argument.type (deliberately invalid runtime schema)
            public readonly string $name;
        };
    }

    public function testEmptyUnionIsASchemaError(): void
    {
        $this->expectException(LogicException::class);
        new class (['name' => 'Alice']) extends Struct {
            #[Field([])]
            public readonly string $name;
        };
    }

    public function testUnknownMappingTypeIsASchemaError(): void
    {
        $this->expectException(LogicException::class);
        new class (['name' => 'Alice']) extends Struct {
            #[Field('no_such_type')]
            public readonly string $name;
        };
    }

    public function testEmptyAliasIsASchemaError(): void
    {
        $this->expectException(LogicException::class);
        new class (['name' => 'Alice']) extends Struct {
            #[Field(alias: '')]
            public readonly string $name;
        };
    }

    public function testFiniteNumericRangeRulesRejectNaNInfinityAndNumericStrings(): void
    {
        $rule = new RangeRule(0, 100);
        foreach ([NAN, INF, -INF, '50', true] as $value) {
            $this->assertFalse($rule->validate($value)->isValid());
        }
        $this->assertTrue($rule->validate(50.5)->isValid());
        $this->expectException(InvalidArgumentException::class);
        new RangeRule(100, 1);
    }
}
