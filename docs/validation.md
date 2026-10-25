# Validation and built-in rules

[Documentation](README.md) · [Rule example](../examples/11_webhook_subscription.php)

Rules implement `tommyknocker\struct\validation\ValidationRule` and return a
`ValidationResult`. They see mapped values, not raw JSON. Input transformers run
before mapping; native property assignment occurs after validation.

- `validationRules` checks the complete field value.
- `itemRules` checks each typed collection element, retaining its key in errors.
- Defaults and nulls run through the same rules as supplied values.
- Validation stops at the first error. Invalid configuration is a programming error.

Property snippets below belong inside a Struct subclass, with the shown classes
imported. For a complete executable scenario using the four format/membership
rules, see [webhook subscriptions](../examples/11_webhook_subscription.php).

## Rule reference

| Rule | Constructor | Accepted values |
| --- | --- | --- |
| `EmailRule` | `()` | Email strings through PHP's email validation filter |
| `RangeRule` | `(int\|float $min, int\|float $max)` | Finite native numbers in an inclusive interval |
| `LengthRule` | `(int $min, int $max)` | UTF-8 strings with an inclusive Unicode code-point count |
| `CountRule` | `(int $min = 0, ?int $max = null)` | Arrays with an inclusive element count |
| `RequiredRule` | `()` | Any value except null or an empty string |
| `NullableRule` | `(ValidationRule $rule)` | Null, or values accepted by the wrapped rule |
| `PatternRule` | `(string $pattern)` | Strings matched by the configured PCRE pattern |
| `OneOfRule` | `(array $values)` | A strict, case-sensitive match in a nonempty set |
| `UuidRule` | `(?int $version = null)` | Canonical hex-and-dash UUIDs, optionally restricted by version |
| `UrlRule` | `(array $schemes = ['http', 'https'])` | Absolute URLs with a host and an allowed scheme |

## PatternRule

```php
use tommyknocker\struct\validation\rules\PatternRule;

#[Field(validationRules: [new PatternRule('/\A[A-Z]{3}-[0-9]{4}\z/')])]
public readonly string $sku;
```

The pattern includes its delimiters and modifiers. `\A` and `\z` above require a
whole-string match, so `SKU-0042` passes while leading/trailing text and newlines
do not. Anchors are not added automatically: `/order/i` can match a substring.
Use `/u` when matching Unicode text. Non-string values are rejected without
coercion or trimming. Malformed patterns throw `InvalidArgumentException` when
the rule is created; matching failures, invalid UTF-8 under `/u`, and PCRE match
limit failures produce an invalid validation result.

## OneOfRule

```php
use tommyknocker\struct\validation\rules\OneOfRule;

#[Field(validationRules: [new OneOfRule(['standard', 'express'])])]
public readonly string $shippingMethod;
```

Membership uses strict comparison: `1`, `'1'`, and `1.0` are distinct, as are
`'ready'` and `'READY'`. Allowed values must be finite scalars, null, or enum cases;
configuration keys are ignored. An empty set or unsupported configured value
throws `InvalidArgumentException`.

Rules run after mapping. A float field sees `1.0` even when the input was integer
`1`; an enum field sees the enum case, not its backing string. Use enum cases in
the allowed set when restricting a native enum field. When the entire allowed
set is part of your domain model, a PHP enum can replace this rule. OneOfRule is
also useful for external string vocabularies and subsets of a larger enum.

Null is accepted only when it is explicitly in the allowed set or the rule is
wrapped in NullableRule; the native property must also permit null.

## UuidRule

```php
use tommyknocker\struct\validation\rules\UuidRule;

#[Field(validationRules: [new UuidRule(version: 4)])]
public readonly string $id;
```

Accepts canonical `8-4-4-4-12` hexadecimal text, case-insensitively, with the RFC
variant and versions 1–8. With no version filter, Nil and Max UUIDs are also
accepted. A version filter rejects other versions and those special forms.
Versions outside 1–8 are invalid configuration.

Braces, `urn:uuid:` prefixes, compact UUIDs, whitespace, and non-string inputs
are rejected. Values are validated without normalizing case or generating an ID.
This checks representation/version/variant, not uniqueness or generation history.
The layout and special forms follow [RFC 9562](https://www.rfc-editor.org/rfc/rfc9562.html).

## UrlRule

```php
use tommyknocker\struct\validation\rules\UrlRule;

#[Field(validationRules: [new UrlRule(schemes: ['https'])])]
public readonly string $callbackUrl;
```

Defaults to HTTP and HTTPS. Scheme names are case-insensitive; custom schemes
such as FTP can be configured. The scheme list must be nonempty and contain
scheme names without `:` or `://`.

The rule delegates syntax checking to PHP's
[URL validation filter](https://www.php.net/manual/en/filter.constants.validation.php),
then requires a host and an allowed scheme. It accepts query strings, fragments,
ports, IP hosts, and ASCII/punycode domains. Relative URLs and hostless URIs are
rejected. Unicode domains need explicit IDN conversion before validation.
The rule performs no DNS resolution, network requests, or reachability checks.
It does not trim or rewrite the URL.

## EmailRule

Accepts strings recognized by `FILTER_VALIDATE_EMAIL`, with no DNS lookup or
normalization. Null, numbers, and malformed email strings are rejected. Wrap it
in NullableRule for an explicitly nullable email field.

## Numeric and size rules

`RangeRule(min, max)` accepts finite native integers/floats, including its bounds.
Numeric strings and booleans are rejected. Bounds must be finite and ordered.
Type mapping can widen an integer to a float before this rule runs.

`LengthRule(min, max)` counts Unicode code points in valid UTF-8, including
newlines. It does not count bytes or grapheme clusters. Empty strings pass when
`min` is zero. Both bounds are required and must satisfy `0 <= min <= max`.

`CountRule(min: 0, max: null)` counts array elements, including keyed maps. A null
maximum is unbounded. An empty array passes when `min` is zero. For a nonempty
collection use `CountRule(min: 1)` in `validationRules`, not `itemRules`.

## RequiredRule and NullableRule

Field key presence is controlled by `Field(required: ...)`, independently of
nullability. RequiredRule is a value constraint: it rejects only null and `''`;
it accepts zero, false, whitespace-only strings, and empty arrays. Use LengthRule,
CountRule, or an explicit pattern for other notions of emptiness.

NullableRule accepts null and otherwise delegates to its wrapped rule:

```php
use tommyknocker\struct\validation\rules\EmailRule;
use tommyknocker\struct\validation\rules\NullableRule;

#[Field(default: null, validationRules: [new NullableRule(new EmailRule())])]
public readonly ?string $replyTo;
```

The wrapper does not make the input key optional or change the PHP property type.

## Custom rules

```php
use tommyknocker\struct\validation\ValidationResult;
use tommyknocker\struct\validation\ValidationRule;

final class CorporateEmailRule implements ValidationRule
{
    public function validate(mixed $value): ValidationResult
    {
        return is_string($value) && str_ends_with($value, '@example.com')
            ? ValidationResult::valid()
            : ValidationResult::invalid('Use an example.com address');
    }
}
```

Compose this with EmailRule to check both syntax and a project-specific domain.
Struct supplies the field path when a rule rejects a value. Rules should be
stateless because compiled definitions are cached. Cross-field checks belong in
application code; a field rule receives only its mapped value.
