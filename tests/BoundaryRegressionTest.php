<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use LogicException;
use PHPUnit\Framework\TestCase;
use stdClass;
use tommyknocker\struct\CollectionType;
use tommyknocker\struct\exception\TransformationException;
use tommyknocker\struct\exception\ValidationException;
use tommyknocker\struct\Field;
use tommyknocker\struct\metadata\MetadataFactory;
use tommyknocker\struct\Struct;
use tommyknocker\struct\transformation\TransformerInterface;
use TypeError;

final class ReviewEnvelope extends Struct
{
    #[Field]
    public readonly mixed $data;
}

final class PreservedNumbers extends Struct
{
    #[Field('int', collection: CollectionType::Preserve)]
    public readonly array $values;
}

final class NumberList extends Struct
{
    #[Field('int', collection: CollectionType::List)]
    public readonly array $values;
}

final class NumberMap extends Struct
{
    #[Field('int', collection: CollectionType::Map)]
    public readonly array $values;
}

final class DecimalQuantity implements TransformerInterface
{
    public function transform(mixed $value): mixed
    {
        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        throw new TransformationException('Expected unsigned decimal digits');
    }
}

final class BrokenTransformation implements TransformerInterface
{
    public function transform(mixed $value): mixed
    {
        throw new TypeError('Programming error');
    }
}

final class TransformedLine extends Struct
{
    #[Field(transformers: [new DecimalQuantity()])]
    public readonly int $quantity;
}

final class TransformedBatch extends Struct
{
    #[Field(TransformedLine::class, collection: CollectionType::List)]
    public readonly array $lines;
}

final class InvalidCollectionSchema extends Struct
{
    #[Field('int', collection: CollectionType::List)]
    public readonly string $value;
}

final class BoundaryRegressionTest extends TestCase
{
    public function testLargeJsonIntegersArePreservedWithoutRounding(): void
    {
        foreach (['9223372036854775808', '-9223372036854775809', '123456789012345678901234567890'] as $number) {
            $dto = ReviewEnvelope::fromJson('{"data":{"id":' . $number . '}}');
            $this->assertInstanceOf(stdClass::class, $dto->data);
            $this->assertSame($number, $dto->data->id);
            $this->assertSame('{"data":{"id":"' . $number . '"}}', $dto->toJson());
        }
    }

    public function testSmallIntegersStayIntegersAndFlagsDoNotDisableBigIntegerProtection(): void
    {
        $dto = ReviewEnvelope::fromJson('{"data":{"small":42,"large":9223372036854775808}}', JSON_OBJECT_AS_ARRAY);
        $this->assertInstanceOf(stdClass::class, $dto->data);
        $this->assertSame(42, $dto->data->small);
        $this->assertSame('9223372036854775808', $dto->data->large);
    }

    public function testLargeIdsCanUseNativeStringIntUnion(): void
    {
        $dto = new class (['id' => 1]) extends Struct {
            #[Field]
            public readonly string|int $id;
        };
        $this->assertSame('9223372036854775808', $dto::fromJson('{"id":9223372036854775808}')->id);
    }

    public function testAnOversizedIntegerCannotSilentlyFitAnIntProperty(): void
    {
        $this->expectException(ValidationException::class);
        PreservedNumbers::fromJson('{"values":[9223372036854775808]}');
    }

    public function testPreserveKeepsEmptyAndNumericKeyedObjects(): void
    {
        foreach (['{"values":{}}', '{"values":{"0":10,"1":20}}', '{"values":[]}', '{"values":[10,20]}'] as $json) {
            $dto = PreservedNumbers::fromJson($json);
            $this->assertSame($json, $dto->toJson());
            $this->assertSame($json, $dto->with([])->toJson());
            $this->assertSame($json, PreservedNumbers::fromJson($dto->toJson())->toJson());
        }
    }

    public function testReplacementsDetermineANewPreservedShape(): void
    {
        $source = PreservedNumbers::fromJson('{"values":{}}');
        $this->assertSame('{"values":[]}', $source->with(['values' => []])->toJson());
        $this->assertSame('{"values":{}}', (new PreservedNumbers(['values' => []]))->with(['values' => new stdClass()])->toJson());
        $this->assertSame('{"values":{}}', $source->toJson());
    }

    public function testMapHasAnExplicitObjectRepresentationEvenWithNumericKeys(): void
    {
        $this->assertSame('{"values":{}}', (new NumberMap(['values' => []]))->toJson());
        $dto = new NumberMap(['values' => [10, 20]]);
        $this->assertSame('{"values":{"0":10,"1":20}}', $dto->toJson());
        $this->assertSame(['values' => [10, 20]], $dto->toArray());
        $this->assertSame('{"values":{}}', $dto->with(['values' => []])->toJson());
    }

    public function testListRejectsObjectsRatherThanReinterpretingTheirKeys(): void
    {
        $this->expectException(ValidationException::class);
        NumberList::fromJson('{"values":{"0":10}}');
    }

    public function testListRejectsSparseKeysRatherThanReindexing(): void
    {
        $this->expectException(ValidationException::class);
        new NumberList(['values' => [2 => 10]]);
    }

    public function testListExportsAnEmptyArray(): void
    {
        $this->assertSame('{"values":[]}', (new NumberList(['values' => []]))->toJson());
    }

    public function testNullableCollectionsStayNullWhenCopied(): void
    {
        $dto = new class (['values' => null]) extends Struct {
            #[Field('int', collection: CollectionType::Map)]
            public readonly ?array $values;
        };
        $this->assertSame('{"values":null}', $dto->with([])->toJson());
    }

    public function testImpossibleCollectionSchemaFailsWithoutInput(): void
    {
        $this->expectException(LogicException::class);
        (new MetadataFactory())->getMetadata(InvalidCollectionSchema::class);
    }

    public function testImpossibleUnionMemberIsASchemaError(): void
    {
        $this->expectException(LogicException::class);
        new class (['value' => 1]) extends Struct {
            #[Field(['int', 'string'])]
            public readonly int $value;
        };
    }

    public function testNumericWideningRemainsAValidSchema(): void
    {
        $dto = new class (['value' => 1]) extends Struct {
            #[Field('int')]
            public readonly float $value;
        };
        $this->assertSame(1.0, $dto->value);
    }

    public function testBroadMappingStillValidatesTheActualNativeType(): void
    {
        $this->expectException(ValidationException::class);
        new class (['value' => 1]) extends Struct {
            #[Field('mixed')]
            public readonly string $value;
        };
    }

    public function testTransformationRejectionReceivesNestedFieldContext(): void
    {
        try {
            new TransformedBatch(['lines' => [['quantity' => '2'], ['quantity' => 'bad']]]);
            $this->fail('Expected transformation failure');
        } catch (ValidationException $error) {
            $this->assertSame('lines[1].quantity', $error->fieldName);
            $this->assertSame('bad', $error->value);
            $inner = $error->getPrevious();
            $this->assertInstanceOf(ValidationException::class, $inner);
            $this->assertInstanceOf(TransformationException::class, $inner->getPrevious());
        }
    }

    public function testTransformationRejectionInWithHasFieldContext(): void
    {
        $dto = new TransformedLine(['quantity' => '2']);

        try {
            $dto->with(['quantity' => 'bad']);
            $this->fail('Expected transformation failure');
        } catch (ValidationException $error) {
            $this->assertSame('quantity', $error->fieldName);
            $this->assertInstanceOf(TransformationException::class, $error->getPrevious());
        }
        $this->assertSame(2, $dto->quantity);
    }

    public function testMappedFieldNamesCannotCollideWithInternalCollectionState(): void
    {
        $dto = new class (['collectionMaps' => 'application value']) extends Struct {
            #[Field]
            public readonly string $collectionMaps;
        };
        $this->assertSame(['collectionMaps' => 'application value'], $dto->toArray());
        $this->assertSame('application value', $dto['collectionMaps']);
        $this->assertSame(['collectionMaps' => 'application value'], $dto->with([])->toArray());
    }

    public function testProgrammingErrorsInTransformersRemainDistinct(): void
    {
        $this->expectException(TypeError::class);
        new class (['value' => 'bad']) extends Struct {
            #[Field(transformers: [new BrokenTransformation()])]
            public readonly string $value;
        };
    }
}
