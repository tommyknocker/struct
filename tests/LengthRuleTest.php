<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use tommyknocker\struct\validation\rules\LengthRule;

final class LengthRuleTest extends TestCase
{
    public function testInclusiveLengthBoundsAndUnicode(): void
    {
        $rule = new LengthRule(2, 3);
        $this->assertTrue($rule->validate('аб')->isValid());
        $this->assertTrue($rule->validate('abc')->isValid());
        $this->assertFalse($rule->validate('a')->isValid());
        $this->assertFalse($rule->validate('abcd')->isValid());
        $this->assertFalse($rule->validate('')->isValid());
        $this->assertFalse($rule->validate(null)->isValid());
        $this->assertFalse($rule->validate(123)->isValid());
        $this->assertFalse($rule->validate("\xff")->isValid());
    }

    public function testEmptyStringsCanBeExplicitlyAllowed(): void
    {
        $this->assertTrue((new LengthRule(0, 0))->validate('')->isValid());
    }

    public function testInvalidBoundsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new LengthRule(3, 2);
    }
}
