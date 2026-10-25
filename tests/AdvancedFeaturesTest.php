<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
use tommyknocker\struct\exception\FieldNotFoundException;
use tommyknocker\struct\exception\ValidationException;
use tommyknocker\struct\tests\fixtures\MultipleTransformersStruct;
use tommyknocker\struct\tests\fixtures\RangeValidatedStruct;
use tommyknocker\struct\tests\fixtures\RequiredFieldStruct;
use tommyknocker\struct\tests\fixtures\RequiredFieldWithAliasStruct;
use tommyknocker\struct\tests\fixtures\TransformedNameStruct;
use tommyknocker\struct\tests\fixtures\UnionTypeStruct;
use tommyknocker\struct\tests\fixtures\ValidatedEmailStruct;

final class AdvancedFeaturesTest extends TestCase
{
    public function testUnionTypes(): void
    {
        $struct = new UnionTypeStruct(['value' => 'hello']);

        $this->assertSame('hello', $struct->value);

        $struct2 = new UnionTypeStruct(['value' => 42]);

        $this->assertSame(42, $struct2->value);
    }

    public function testUnionTypesValidationFailure(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Field value must be one of: string|int');

        new UnionTypeStruct(['value' => 3.14]);
    }

    public function testValidationRules(): void
    {
        $struct = new ValidatedEmailStruct(['email' => 'test@example.com']);

        $this->assertSame('test@example.com', $struct->email);
    }

    public function testValidationRulesFailure(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid email format');

        new ValidatedEmailStruct(['email' => 'invalid-email']);
    }

    public function testTransformers(): void
    {
        $struct = new TransformedNameStruct(['name' => 'john doe']);

        $this->assertSame('JOHN DOE', $struct->name);
    }

    public function testMultipleTransformers(): void
    {
        $struct = new MultipleTransformersStruct(['name' => 'JOHN DOE']);

        $this->assertSame('JOHN DOE', $struct->name);
    }

    public function testRangeValidation(): void
    {
        $struct = new RangeValidatedStruct(['age' => 25]);

        $this->assertSame(25, $struct->age);
    }

    public function testRangeValidationFailure(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Value must be between 1 and 120');

        new RangeValidatedStruct(['age' => 150]);
    }

    public function testFieldNotFoundException(): void
    {
        $this->expectException(FieldNotFoundException::class);
        $this->expectExceptionMessage('Missing required field: name');

        new RequiredFieldStruct([]);
    }

    public function testFieldNotFoundExceptionWithAlias(): void
    {
        $this->expectException(FieldNotFoundException::class);
        $this->expectExceptionMessage('Missing required field: name (alias: user_name)');

        new RequiredFieldWithAliasStruct([]);
    }
}
