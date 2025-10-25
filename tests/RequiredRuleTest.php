<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
use tommyknocker\struct\validation\rules\RequiredRule;

final class RequiredRuleTest extends TestCase
{
    private RequiredRule $rule;

    protected function setUp(): void
    {
        $this->rule = new RequiredRule();
    }

    public function testValidatesNonNullNonEmptyValue(): void
    {
        $result = $this->rule->validate('test');
        $this->assertTrue($result->isValid);
        $this->assertNull($result->errorMessage);
    }

    public function testValidatesNonEmptyString(): void
    {
        $result = $this->rule->validate('hello world');
        $this->assertTrue($result->isValid);
        $this->assertNull($result->errorMessage);
    }

    public function testValidatesNonEmptyArray(): void
    {
        $result = $this->rule->validate([1, 2, 3]);
        $this->assertTrue($result->isValid);
        $this->assertNull($result->errorMessage);
    }

    public function testValidatesNonEmptyObject(): void
    {
        $result = $this->rule->validate(new \stdClass());
        $this->assertTrue($result->isValid);
        $this->assertNull($result->errorMessage);
    }

    public function testValidatesZeroAsValid(): void
    {
        $result = $this->rule->validate(0);
        $this->assertTrue($result->isValid);
        $this->assertNull($result->errorMessage);
    }

    public function testValidatesFalseAsValid(): void
    {
        $result = $this->rule->validate(false);
        $this->assertTrue($result->isValid);
        $this->assertNull($result->errorMessage);
    }

    public function testFailsForNullValue(): void
    {
        $result = $this->rule->validate(null);
        $this->assertFalse($result->isValid);
        $this->assertEquals('Field is required', $result->errorMessage);
    }

    public function testFailsForEmptyString(): void
    {
        $result = $this->rule->validate('');
        $this->assertFalse($result->isValid);
        $this->assertEquals('Field is required', $result->errorMessage);
    }

    public function testValidatesEmptyArrayAsValid(): void
    {
        $result = $this->rule->validate([]);
        $this->assertTrue($result->isValid);
        $this->assertNull($result->errorMessage);
    }
}
