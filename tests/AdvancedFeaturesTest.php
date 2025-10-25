<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
use tommyknocker\struct\exception\FieldNotFoundException;
use tommyknocker\struct\exception\ValidationException;
use tommyknocker\struct\tests\fixtures\LegacyValidatorStruct;
use tommyknocker\struct\tests\fixtures\MultipleTransformersStruct;
use tommyknocker\struct\tests\fixtures\RangeValidatedStruct;
use tommyknocker\struct\tests\fixtures\RequiredFieldStruct;
use tommyknocker\struct\tests\fixtures\RequiredFieldWithAliasStruct;
use tommyknocker\struct\tests\fixtures\TransformedNameStruct;
use tommyknocker\struct\tests\fixtures\UnionTypeStruct;
use tommyknocker\struct\tests\fixtures\ValidatedEmailStruct;
use tommyknocker\struct\transformation\StringToLowerTransformer;
use tommyknocker\struct\transformation\StringToUpperTransformer;
use tommyknocker\struct\validation\rules\EmailRule;
use tommyknocker\struct\validation\rules\RangeRule;
use tommyknocker\struct\validation\rules\RequiredRule;
use tommyknocker\struct\validation\ValidationResult;

final class ValidationRulesTest extends TestCase
{
    public function testEmailRuleValidation(): void
    {
        $rule = new EmailRule();

        $this->assertTrue($rule->validate('test@example.com')->isValid());
        $this->assertTrue($rule->validate('user+tag@domain.co.uk')->isValid());

        $this->assertFalse($rule->validate('invalid-email')->isValid());
        $this->assertFalse($rule->validate('not@')->isValid());
        $this->assertFalse($rule->validate(123)->isValid());
    }

    public function testEmailRuleValidationEdgeCases(): void
    {
        $rule = new EmailRule();

        // Valid edge cases
        $this->assertTrue($rule->validate('a@b.co')->isValid());
        $this->assertTrue($rule->validate('test.email@example.com')->isValid());
        $this->assertTrue($rule->validate('user_name@domain-name.com')->isValid());
        $this->assertTrue($rule->validate('123@456.789')->isValid());

        // Invalid edge cases
        $this->assertFalse($rule->validate('')->isValid());
        $this->assertFalse($rule->validate('@example.com')->isValid());
        $this->assertFalse($rule->validate('test@')->isValid());
        $this->assertFalse($rule->validate('test.example.com')->isValid());
        $this->assertFalse($rule->validate('test@.com')->isValid());
        $this->assertFalse($rule->validate('test@example.')->isValid());
        $this->assertFalse($rule->validate('test@@example.com')->isValid());
        $this->assertFalse($rule->validate('test@example..com')->isValid());
        $this->assertFalse($rule->validate('test@example.com.')->isValid());
        $this->assertFalse($rule->validate('test@example.com ')->isValid());
        $this->assertFalse($rule->validate(' test@example.com')->isValid());
    }

    public function testEmailRuleValidationErrorMessage(): void
    {
        $rule = new EmailRule();

        $result = $rule->validate('invalid-email');
        $this->assertFalse($result->isValid());
        $this->assertNotNull($result->getErrorMessage());
        $this->assertStringContainsString('Invalid email format', $result->getErrorMessage());

        $result = $rule->validate(123);
        $this->assertFalse($result->isValid());
        $this->assertNotNull($result->getErrorMessage());
        $this->assertStringContainsString('Invalid email format', $result->getErrorMessage());
    }

    public function testRangeRuleValidation(): void
    {
        $rule = new RangeRule(1, 100);

        $this->assertTrue($rule->validate(50)->isValid());
        $this->assertTrue($rule->validate(1)->isValid());
        $this->assertTrue($rule->validate(100)->isValid());
        $this->assertTrue($rule->validate('50')->isValid()); // String numeric

        $this->assertFalse($rule->validate(0)->isValid());
        $this->assertFalse($rule->validate(101)->isValid());
        $this->assertFalse($rule->validate('abc')->isValid());
    }

    public function testRequiredRuleValidation(): void
    {
        $rule = new RequiredRule();

        $this->assertTrue($rule->validate('value')->isValid());
        $this->assertTrue($rule->validate(0)->isValid());
        $this->assertTrue($rule->validate(false)->isValid());

        $this->assertFalse($rule->validate(null)->isValid());
        $this->assertFalse($rule->validate('')->isValid());
    }

    public function testValidationResult(): void
    {
        $valid = ValidationResult::valid();
        $this->assertTrue($valid->isValid());
        $this->assertNull($valid->getErrorMessage());

        $invalid = ValidationResult::invalid('Test error');
        $this->assertFalse($invalid->isValid());
        $this->assertSame('Test error', $invalid->getErrorMessage());
    }
}

final class TransformationTest extends TestCase
{
    public function testStringToUpperTransformer(): void
    {
        $transformer = new StringToUpperTransformer();

        $this->assertSame('HELLO', $transformer->transform('hello'));
        $this->assertSame('WORLD', $transformer->transform('world'));
        $this->assertSame(123, $transformer->transform(123)); // Non-string unchanged
    }

    public function testStringToLowerTransformer(): void
    {
        $transformer = new StringToLowerTransformer();

        $this->assertSame('hello', $transformer->transform('HELLO'));
        $this->assertSame('world', $transformer->transform('WORLD'));
        $this->assertSame(123, $transformer->transform(123)); // Non-string unchanged
    }
}

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

    public function testLegacyValidatorStillWorks(): void
    {
        $struct = new LegacyValidatorStruct(['email' => 'test@example.com']);

        $this->assertSame('test@example.com', $struct->email);
    }

    public function testLegacyValidatorFailure(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid email format');

        new LegacyValidatorStruct(['email' => 'invalid']);
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
