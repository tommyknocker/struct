# Struct documentation

[Documentation](README.md) · [Examples](../examples/README.md)

Struct maps external arrays/JSON into validated DTOs and exports explicit
payloads. The [project README](../README.md) provides installation and a complete
quick start. These guides define the behavior in detail.

| Guide | Read it to understand |
| --- | --- |
| [Fields and input contracts](fields.md) | Types, defaults, null, required keys, aliases, strict DTOs |
| [Collections](collections.md) | List/Map/Preserve, element types, keys, empty JSON shapes |
| [Validation](validation.md) | Every built-in rule, constructor options, custom rules |
| [Transformations and copies](transformations.md) | Pipeline ordering, explicit conversions, transformer errors, `with()` |
| [Serialization](serialization.md) | Outbound aliases, dates, enums, large integers, mixed data, round trips |
| [Errors](errors.md) | Exception classes, field paths, causes, boundary handling |
| [API reference](reference.md) | Field options, Struct methods, supported extension interfaces |
| [Tooling](tooling.md) | PHPStan extension, optional adapters/scaffolding, development commands |

Follow [fields](fields.md) → [collections](collections.md) →
[validation](validation.md) when defining a new payload. Read
[serialization](serialization.md) before designing the outgoing contract.
The [example cookbook](../examples/README.md) demonstrates complete scenarios,
including [webhook subscription rules](../examples/11_webhook_subscription.php).

The reference documents the current API. Behavior changes are recorded in the
[changelog](../CHANGELOG.md).
