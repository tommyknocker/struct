<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use tommyknocker\struct\exception\FieldNotFoundException;
use tommyknocker\struct\exception\ValidationException;
use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\transformation\TransformerInterface;
use tommyknocker\struct\validation\rules\RangeRule;
use tommyknocker\struct\validation\rules\RequiredRule;

enum DeliveryStatus: string
{
    case Ready = 'ready';
}

enum DeliveryKind
{
    case Express;
}

final class DeliveryItem extends Struct
{
    #[Field]
    public readonly string $sku;

    #[Field(validationRules: [new RangeRule(1, 100)])]
    public readonly int $quantity;
}

final class Delivery extends Struct
{
    #[Field(alias: 'delivery_id', outputAlias: 'id')]
    public readonly string $deliveryId;

    #[Field]
    public readonly DeliveryStatus $status;

    #[Field]
    public readonly DateTimeImmutable $createdAt;

    #[Field(DeliveryItem::class, collection: \tommyknocker\struct\CollectionType::Preserve)]
    public readonly array $items;

    public string $internalNote = 'not part of the payload';
}

final class MustNotRunTransformer implements TransformerInterface
{
    public function transform(mixed $value): mixed
    {
        throw new LogicException('Transformer should not run');
    }
}

final class ContractRanges extends Struct
{
    #[Field('int', collection: \tommyknocker\struct\CollectionType::Preserve, itemRules: [new RangeRule(1, 10)])]
    public readonly array $values;
}

final class MappingContractTest extends TestCase
{
    public function testInboundDataBecomesAnExplicitOutboundPayload(): void
    {
        $delivery = new Delivery([
            'delivery_id' => 'd-1',
            'status' => 'ready',
            'createdAt' => '2026-10-25T12:30:45.123456+03:00',
            'items' => ['first' => ['sku' => 'A', 'quantity' => 2]],
        ]);
        $this->assertInstanceOf(DeliveryStatus::class, $delivery->status);
        $this->assertInstanceOf(DeliveryItem::class, $delivery->items['first']);
        $this->assertSame([
            'id' => 'd-1',
            'status' => 'ready',
            'createdAt' => '2026-10-25T12:30:45.123456+03:00',
            'items' => ['first' => ['sku' => 'A', 'quantity' => 2]],
        ], $delivery->toArray());
        $this->assertSame($delivery->toArray(), json_decode($delivery->toJson(), true, 512, JSON_THROW_ON_ERROR));
        $this->assertSame('d-2', $delivery->with(['delivery_id' => 'd-2'])->deliveryId);
        $this->assertSame('d-3', $delivery->with(['deliveryId' => 'd-3'])->deliveryId);
        $this->assertSame('d-1', $delivery->deliveryId);
    }

    public function testSymmetricAliasesSupportJsonRoundTrips(): void
    {
        $dto = new class (['sent_at' => '2026-10-25T12:30:00.123456+03:00', 'status' => 'ready']) extends Struct {
            #[Field(alias: 'sent_at', outputAlias: 'sent_at')]
            public readonly DateTimeImmutable $sentAt;
            #[Field]
            public readonly DeliveryStatus $status;
        };
        $restored = $dto::fromJson($dto->toJson());
        $this->assertSame($dto->toArray(), $restored->toArray());
    }

    public function testDefaultsAreTransformedAndValidated(): void
    {
        $this->expectException(ValidationException::class);
        new class ([]) extends Struct {
            #[Field(default: -1, validationRules: [new RangeRule(1, 100)])]
            public readonly int $quantity;
        };
    }

    public function testDefaultNestedArraysBecomeStructs(): void
    {
        $dto = new class ([]) extends Struct {
            #[Field(default: ['sku' => 'A', 'quantity' => 1])]
            public readonly DeliveryItem $item;
        };
        $this->assertSame('A', $dto->item->sku);
    }

    public function testNullRunsValidationRules(): void
    {
        $this->expectException(ValidationException::class);
        new class (['name' => null]) extends Struct {
            #[Field(validationRules: [new RequiredRule()])]
            public readonly ?string $name;
        };
    }

    public function testImplicitNullAlsoRunsValidationRules(): void
    {
        $this->expectException(ValidationException::class);
        new class ([]) extends Struct {
            #[Field(required: false, validationRules: [new RequiredRule()])]
            public readonly ?string $name;
        };
    }

    public function testRequiredNullableFieldMustBePresent(): void
    {
        $this->expectException(FieldNotFoundException::class);
        new class ([]) extends Struct {
            #[Field(required: true)]
            public readonly ?string $name;
        };
    }

    public function testRequiredNullableFieldAcceptsExplicitNull(): void
    {
        $dto = new class (['name' => null]) extends Struct {
            #[Field(required: true)]
            public readonly ?string $name;
        };
        $this->assertNull($dto->name);
    }

    public function testExplicitNullDefaultIsSupported(): void
    {
        $dto = new class ([]) extends Struct {
            #[Field(default: null, required: true)]
            public readonly ?string $name;
        };
        $this->assertNull($dto->name);
    }

    public function testNullDefaultIsRejectedForNonNullableType(): void
    {
        $this->expectException(ValidationException::class);
        new class ([]) extends Struct {
            #[Field(default: null)]
            public readonly string $name;
        };
    }

    public function testInferredUnionAndArrayTypes(): void
    {
        $dto = new class (['value' => 42, 'tags' => ['x' => 'y']]) extends Struct {
            #[Field]
            public readonly string|int $value;
            #[Field]
            public readonly array $tags;
        };
        $this->assertSame(42, $dto->value);
        $this->assertSame(['x' => 'y'], $dto->tags);
    }

    public function testNativeTypeDisagreementDoesNotCoerceThroughReflection(): void
    {
        $this->expectException(LogicException::class);
        new class (['value' => 42]) extends Struct {
            #[Field('int')]
            public readonly string $value;
        };
    }

    public function testScalarStringsAreNotImplicitlyCast(): void
    {
        $this->expectException(ValidationException::class);
        new DeliveryItem(['sku' => 'A', 'quantity' => '2']);
    }

    public function testCollectionKeysArePreserved(): void
    {
        $dto = new class (['values' => [2 => 1, 5 => 2, 'other' => 3]]) extends Struct {
            #[Field('int', collection: \tommyknocker\struct\CollectionType::Preserve)]
            public readonly array $values;
        };
        $this->assertSame([2 => 1, 5 => 2, 'other' => 3], $dto->toArray()['values']);
    }

    public function testCollectionRulesValidateEachItem(): void
    {
        try {
            new ContractRanges(['values' => ['bad' => -1]]);
            $this->fail('Expected collection validation to fail');
        } catch (ValidationException $e) {
            $this->assertSame('values[bad]', $e->fieldName);
        }
    }

    public function testNestedValidationContainsFullPath(): void
    {
        try {
            new Delivery([
                'delivery_id' => 'd-1', 'status' => 'ready', 'createdAt' => '2026-10-25',
                'items' => ['first' => ['sku' => 'A', 'quantity' => 0]],
            ]);
            $this->fail('Expected nested validation to fail');
        } catch (ValidationException $e) {
            $this->assertSame('items[first].quantity', $e->fieldName);
            $this->assertSame(0, $e->value);
            $this->assertInstanceOf(ValidationException::class, $e->getPrevious());
        }
    }

    public function testNestedMissingFieldContainsFullPath(): void
    {
        try {
            new Delivery([
                'delivery_id' => 'd-1', 'status' => 'ready', 'createdAt' => '2026-10-25',
                'items' => [['sku' => 'A']],
            ]);
            $this->fail('Expected missing field error');
        } catch (FieldNotFoundException $e) {
            $this->assertSame('items[0].quantity', $e->fieldName);
        }
    }

    public function testDateTimeUsesTheDeclaredClass(): void
    {
        $dto = new class (['date' => '2026-10-25T12:00:00+03:00']) extends Struct {
            #[Field]
            public readonly DateTime $date;
        };
        $this->assertInstanceOf(DateTime::class, $dto->date);
        $this->assertSame($dto->toArray(), $dto::fromJson($dto->toJson())->toArray());
    }

    public function testDateTimeInterfaceUsesImmutableDates(): void
    {
        $dto = new class (['date' => '2026-10-25']) extends Struct {
            #[Field]
            public readonly DateTimeInterface $date;
        };
        $this->assertInstanceOf(DateTimeImmutable::class, $dto->date);
    }

    public function testMutableDateCanBecomeImmutable(): void
    {
        $dto = new class (['date' => new DateTime('2026-10-25')]) extends Struct {
            #[Field]
            public readonly DateTimeImmutable $date;
        };
        $this->assertInstanceOf(DateTimeImmutable::class, $dto->date);
    }

    public function testImpossibleCalendarDatesAreRejected(): void
    {
        $this->expectException(ValidationException::class);
        new class (['date' => '2026-02-30']) extends Struct {
            #[Field]
            public readonly DateTimeImmutable $date;
        };
    }

    public function testWrongEnumBackingTypeUsesValidationException(): void
    {
        $this->expectException(ValidationException::class);
        new class (['status' => 123]) extends Struct {
            #[Field]
            public readonly DeliveryStatus $status;
        };
    }

    public function testPureEnumsSupportRoundTripsByCaseName(): void
    {
        $dto = new class (['kind' => DeliveryKind::Express]) extends Struct {
            #[Field]
            public readonly DeliveryKind $kind;
        };
        $this->assertSame(['kind' => 'Express'], $dto->toArray());
        $this->assertSame(DeliveryKind::Express, $dto::fromJson($dto->toJson())->kind);
    }

    public function testDeepMixedArraysNormalizeTransportValues(): void
    {
        $dto = new class (['data' => ['nested' => [[DeliveryStatus::Ready, DeliveryKind::Express]]]]) extends Struct {
            #[Field]
            public readonly mixed $data;
        };
        $this->assertSame(['data' => ['nested' => [['ready', 'Express']]]], $dto->toArray());
    }

    public function testOutputNeverLeaksUnmarkedProperties(): void
    {
        $dto = new class (['name' => 'Alice']) extends Struct {
            #[Field]
            public readonly string $name;
            public string $secret = 'hidden';
        };
        $this->assertSame(['name' => 'Alice'], $dto->toArray());
    }

    public function testDuplicateOutputNamesAreRejected(): void
    {
        $this->expectException(LogicException::class);
        new class (['a' => 'a', 'b' => 'b']) extends Struct {
            #[Field(outputAlias: 'same')]
            public readonly string $a;
            #[Field(outputAlias: 'same')]
            public readonly string $b;
        };
    }

    public function testCollectionWithoutElementTypeIsRejected(): void
    {
        $this->expectException(LogicException::class);
        new class ([]) extends Struct {
            #[Field(collection: \tommyknocker\struct\CollectionType::Preserve)]
            public readonly array $items;
        };
    }

    public function testUnsupportedObjectsAreNotSilentlySerialized(): void
    {
        $dto = new class (['data' => new class () {}]) extends Struct {
            #[Field]
            public readonly mixed $data;
        };
        $this->expectException(RuntimeException::class);
        $dto->toArray();
    }

    public function testJsonSerializableObjectsAreNormalizedRecursively(): void
    {
        $object = new class () implements \JsonSerializable {
            public function jsonSerialize(): mixed
            {
                return ['status' => DeliveryStatus::Ready];
            }
        };
        $dto = new class (['data' => $object]) extends Struct {
            #[Field]
            public readonly mixed $data;
        };
        $this->assertSame(['data' => ['status' => 'ready']], $dto->toArray());
    }

    public function testSelfReferencesUseTheDeclaringClass(): void
    {
        $dto = new class (['child' => null]) extends Struct {
            #[Field]
            public readonly ?self $child;
        };
        $parent = new ($dto::class)(['child' => $dto]);
        $this->assertSame(['child' => ['child' => null]], $parent->toArray());
    }

    public function testIdenticalAliasAndPropertyNameWorkInCopies(): void
    {
        $dto = new class (['name' => 'Alice']) extends Struct {
            #[Field(alias: 'name')]
            public readonly string $name;
        };
        $this->assertSame('Bob', $dto->with(['name' => 'Bob'])->name);
    }

    public function testInputAliasCannotShadowAnotherProperty(): void
    {
        $this->expectException(LogicException::class);
        new class (['a' => 'value']) extends Struct {
            #[Field]
            public readonly string $a;
            #[Field(alias: 'a')]
            public readonly string $b;
        };
    }

    public function testCircularObjectsProduceAnExceptionInsteadOfInfiniteRecursion(): void
    {
        $object = new \stdClass();
        $object->child = $object;
        $dto = new class (['child' => $object]) extends Struct {
            #[Field]
            public readonly mixed $child;
        };
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Maximum serialization depth');
        $dto->toArray();
    }

    public function testEmptyAndNumericFieldNamesStillEncodeJsonObjects(): void
    {
        $empty = new class ([]) extends Struct {};
        $this->assertSame('{}', $empty->toJson());
        $outer = new class (['child' => $empty]) extends Struct {
            #[Field]
            public readonly Struct $child;
        };
        $this->assertSame('{"child":{}}', $outer->toJson());
        $numeric = new class (['name' => 'Alice']) extends Struct {
            #[Field(outputAlias: '0')]
            public readonly string $name;
        };
        $this->assertSame('{"0":"Alice"}', $numeric->toJson());
    }

    public function testJsonRoundTripPreservesWholeNumberFloats(): void
    {
        $dto = new class (['amount' => 1.0]) extends Struct {
            #[Field]
            public readonly float $amount;
        };
        $this->assertSame('{"amount":1.0}', $dto->toJson());
        $this->assertSame(1.0, $dto::fromJson($dto->toJson())->amount);
    }

    public function testStrictModeIsCheckedBeforeTransformations(): void
    {

        try {
            new class (['name' => 'Alice', 'extra' => true]) extends Struct {
                protected const STRICT = true;
                #[Field(transformers: [new MustNotRunTransformer()])]
                public readonly string $name;
            };
            $this->fail('Expected unknown key error');
        } catch (RuntimeException $e) {
            $this->assertSame('Unknown field: extra', $e->getMessage());
        }
    }
}
