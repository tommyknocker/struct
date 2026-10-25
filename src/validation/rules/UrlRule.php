<?php

declare(strict_types=1);

namespace tommyknocker\struct\validation\rules;

use InvalidArgumentException;
use tommyknocker\struct\validation\ValidationResult;
use tommyknocker\struct\validation\ValidationRule;

/** Absolute URL syntax with an explicit scheme allowlist; performs no network requests. */
final class UrlRule implements ValidationRule
{
    /** @var list<string> */
    private readonly array $schemes;

    /** @param array<string> $schemes Allowed schemes; names are case-insensitive. */
    public function __construct(array $schemes = ['http', 'https'])
    {
        $this->schemes = $this->normalizeSchemes($schemes);
    }

    /** @param array<mixed> $schemes
     * @return list<string>
     */
    private function normalizeSchemes(array $schemes): array
    {
        if ($schemes === []) {
            throw new InvalidArgumentException('URL schemes must not be empty');
        }
        $result = [];
        foreach ($schemes as $scheme) {
            if (!is_string($scheme) || preg_match('/\A[a-z][a-z0-9+.-]*\z/i', $scheme) !== 1) {
                throw new InvalidArgumentException('Each URL scheme must be a valid scheme name without a colon');
            }
            $result[] = strtolower($scheme);
        }

        return array_values(array_unique($result));
    }

    public function validate(mixed $value): ValidationResult
    {
        if (!is_string($value)) {
            return ValidationResult::invalid('URL must be a string');
        }
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            return ValidationResult::invalid('Invalid absolute URL');
        }
        $scheme = parse_url($value, PHP_URL_SCHEME);
        $host = parse_url($value, PHP_URL_HOST);
        if (!is_string($scheme) || !is_string($host) || $host === '' || !in_array(strtolower($scheme), $this->schemes, true)) {
            return ValidationResult::invalid('URL must have a host and an allowed scheme');
        }

        return ValidationResult::valid();
    }
}
