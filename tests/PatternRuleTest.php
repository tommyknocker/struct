<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use tommyknocker\struct\validation\rules\PatternRule;

final class PatternRuleTest extends TestCase
{
    public function testAnchoredIdentifierMatchingDoesNotCastOrTrim(): void
    {
        $rule = new PatternRule('/\A[A-Z]{3}-[0-9]{4}\z/');
        $this->assertTrue($rule->validate('SKU-0042')->isValid());
        foreach (['sku-0042', ' SKU-0042', "SKU-0042\n", 'prefix-SKU-0042', '', 42, null] as $value) {
            $this->assertFalse($rule->validate($value)->isValid());
        }
    }

    public function testModifiersAndSubstringMatchingAreExplicit(): void
    {
        $this->assertTrue((new PatternRule('/hello/i'))->validate('Say HELLO')->isValid());
        $this->assertTrue((new PatternRule('/\A\p{L}+\z/u'))->validate('Привет')->isValid());
        $this->assertFalse((new PatternRule('/\A\p{L}+\z/u'))->validate("\xff")->isValid());
    }

    public function testMalformedPatternsAreConfigurationErrorsWithoutPhpWarnings(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PatternRule('/[broken/');
    }
}
