<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
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
        $this->assertFalse($rule->validate('123@456.789')->isValid());

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
        $this->assertStringContainsString('Email must be a string', $result->getErrorMessage());
    }

    public function testRangeRuleValidation(): void
    {
        $rule = new RangeRule(1, 100);

        $this->assertTrue($rule->validate(50)->isValid());
        $this->assertTrue($rule->validate(1)->isValid());
        $this->assertTrue($rule->validate(100)->isValid());
        $this->assertFalse($rule->validate('50')->isValid()); // No implicit numeric coercion.

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
