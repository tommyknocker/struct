<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use tommyknocker\struct\validation\rules\UuidRule;

final class UuidRuleTest extends TestCase
{
    public function testAllSupportedVersionsAndRfcVariantValues(): void
    {
        $rule = new UuidRule();
        foreach (range(1, 8) as $version) {
            foreach (['8', '9', 'a', 'b'] as $variant) {
                $uuid = '7185bbe3-cdd7-' . $version . '154-' . $variant . '8c3-63416a76327a';
                $this->assertTrue($rule->validate($uuid)->isValid());
                $this->assertTrue($rule->validate(strtoupper($uuid))->isValid());
            }
        }
    }

    public function testVersionFilterRejectsOtherVersions(): void
    {
        $rule = new UuidRule(version: 4);
        $this->assertTrue($rule->validate('7185bbe3-cdd7-4154-88c3-63416a76327a')->isValid());
        $this->assertFalse($rule->validate('7185bbe3-cdd7-7154-88c3-63416a76327a')->isValid());
    }

    public function testNilAndMaxAreOnlyAcceptedWithoutAVersionFilter(): void
    {
        foreach (['00000000-0000-0000-0000-000000000000', 'FFFFFFFF-FFFF-FFFF-FFFF-FFFFFFFFFFFF'] as $uuid) {
            $this->assertTrue((new UuidRule())->validate($uuid)->isValid());
            $this->assertFalse((new UuidRule(4))->validate($uuid)->isValid());
        }
    }

    public function testNoncanonicalMalformedAndUnsupportedVariantsAreRejected(): void
    {
        foreach ([
            '7185bbe3cdd7415488c363416a76327a',
            '{7185bbe3-cdd7-4154-88c3-63416a76327a}',
            'urn:uuid:7185bbe3-cdd7-4154-88c3-63416a76327a',
            '7185bbe3-cdd7-0154-88c3-63416a76327a',
            '7185bbe3-cdd7-9154-88c3-63416a76327a',
            '7185bbe3-cdd7-4154-c8c3-63416a76327a',
            '7185bbe3-cdd7-4154-88c3-63416a76327z',
            "7185bbe3-cdd7-4154-88c3-63416a76327a\n", '', null, 42,
        ] as $value) {
            $this->assertFalse((new UuidRule())->validate($value)->isValid());
        }
    }

    public function testInvalidVersionIsAConfigurationError(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new UuidRule(9);
    }
}
