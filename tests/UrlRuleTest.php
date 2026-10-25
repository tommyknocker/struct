<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use tommyknocker\struct\validation\rules\UrlRule;

final class UrlRuleTest extends TestCase
{
    public function testDefaultSchemesAndAbsoluteUrlComponents(): void
    {
        $rule = new UrlRule();
        foreach ([
            'https://example.test/hooks?event=paid#details',
            'http://example.test:8080/path',
            'HTTPS://EXAMPLE.TEST/path',
            'https://127.0.0.1/path',
            'https://[::1]/path',
            'https://xn--e1afmkfd.xn--p1ai/path',
        ] as $value) {
            $this->assertTrue($rule->validate($value)->isValid(), $value);
        }
    }

    public function testMalformedRelativeAndDisallowedSchemesAreRejected(): void
    {
        $rule = new UrlRule();
        foreach (['/path', '//example.test/path', 'example.test', 'ftp://example.test/file', 'mailto:a@example.test',
            'javascript:alert(1)', 'https://', 'https://example.test:99999', 'https://exa mple.test',
            "https://example.test\n", 'https://пример.рф/path', '', null, 42,
        ] as $value) {
            $this->assertFalse($rule->validate($value)->isValid());
        }
    }

    public function testAllowedSchemesAreConfigurableAndCaseInsensitive(): void
    {
        $rule = new UrlRule(schemes: ['HTTPS', 'https']);
        $this->assertTrue($rule->validate('https://example.test')->isValid());
        $this->assertFalse($rule->validate('http://example.test')->isValid());
        $this->assertTrue((new UrlRule(['ftp']))->validate('ftp://example.test/file')->isValid());
    }

    public function testInvalidSchemeConfigurationIsRejected(): void
    {
        foreach ([[], [''], ['https://'], ['1http'], ['white space']] as $schemes) {
            try {
                new UrlRule($schemes);
                $this->fail('Expected invalid scheme configuration');
            } catch (InvalidArgumentException $error) {
                $this->assertNotSame('', $error->getMessage());
            }
        }
    }
}
