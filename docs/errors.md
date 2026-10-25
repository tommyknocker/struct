# Errors at integration boundaries

[Documentation](README.md) · [Examples](../examples/README.md)

```php
use tommyknocker\struct\exception\FieldNotFoundException;
use tommyknocker\struct\exception\ValidationException;

try {
    $order = Order::fromJson($json);
} catch (FieldNotFoundException $e) {
    // Missing field; $e->fieldName can be "items[0].quantity".
} catch (ValidationException $e) {
    // Invalid value/key; $e->fieldName is the path, $e->value is rejected input.
} catch (JsonException $e) {
    // Malformed JSON.
}
```

Invalid model declarations throw `LogicException`; these are programming errors.
Validation is fail-fast, not a form validation system with cross-field rules or
aggregate errors. See [boundary error handling](../examples/08_errors_at_the_boundary.php)
and [batch import](../examples/07_batch_import.php) for application-level policies.

## Error contracts

| Failure | Exception / context |
| --- | --- |
| Malformed JSON | `JsonException` |
| Root is not a JSON object | `ValidationException`, field `$` |
| Required field absent | `FieldNotFoundException`, with `fieldName` and optional input `alias` |
| Type, rule, unknown-key, or conflicting-key failure | `ValidationException`, with `fieldName` and rejected `value` |
| Expected transformer rejection | Wrapped `TransformationException` in a `ValidationException` |
| Invalid model or rule configuration | `LogicException` or `InvalidArgumentException` |
| Unsupported outbound value / excessive export nesting | `RuntimeException` |

Nested mapping appends paths such as `items[2].sku`, preserving exception causes.
Rules return `ValidationResult::invalid(...)`; they do not need to construct
field paths themselves. Transformers reject expected input with
`TransformationException`; errors in extension code propagate unchanged.

A failed `with()` leaves the source DTO's field assignments intact. Copies and
readonly properties are shallow; extensions should be stateless and should not
mutate nested input objects while validating or transforming them.
