# Arrays, JSON, and outbound contracts

[Documentation](README.md) · [Examples](../examples/README.md)

`toArray()`, `jsonSerialize()`, and `toJson()` use the same value normalization:

- Export only Field properties, with their output names.
- Recursively normalize nested DTOs, arrays, and JSON objects.
- Backed enums become backing values; pure enums become case names.
- Dates become `Y-m-d\TH:i:s.uP` strings, retaining microseconds and UTC offset.
- Normalize other `JsonSerializable` results recursively.
- Reject unsupported objects, resources, nonfinite floats, and excessive nesting.

`fromJson()` requires an object at the root and always throws on malformed JSON.
Integers outside PHP's integer range are decoded as exact decimal strings,
including inside `mixed` and `string|int` fields. Their JSON output is a string;
strict `int` fields reject them. This policy is always enabled, preventing silent
conversion to imprecise floats. Ordinary integers stay integers.
Within `mixed`, it preserves objects as `stdClass` and lists as PHP arrays.
`toJson()` preserves that distinction, including `{}` and `[]`; `toArray()`
converts objects to arrays. JSON DTOs remain objects even with no fields or
numeric output names. Whole-number floats retain a fractional part under the
default JSON flags. Explicit JSON flags can change formatting.

Date strings use PHP's date parser with calendar warnings rejected. Specify a
timezone for deterministic parsing. `DateTimeInterface` string fields become
`DateTimeImmutable`; concrete DateTime/DateTimeImmutable types are honored.
This is flexible parsing rather than exact-format validation. For custom date
classes/formats, use explicit input transformers and JsonSerializable values.

Mapping has a depth limit of 128; export has a limit of 512, including cycles.
A value accepted into `mixed` may still be impossible to export. Default data
supports scalars, arrays, JSON objects, enums, and dates; mutable default dates
and JSON objects are copied per construction. Use raw arrays for nested DTO
defaults, so their values run through mapping and validation.

## Round trips

Use the same input/output names when one DTO represents a symmetric wire
contract. `Dto::fromJson($dto->toJson())` restores supported, unambiguous mapped
values. It does not retain ignored fields, original whitespace, date spelling,
or named timezone identity beyond the exported offset.

Exact branch identity cannot be recovered from ambiguous unions: for example,
an enum case and a string can serialize to the same string. Input transformers
run again during `fromJson()`. Use explicit discriminators or separate DTOs when
those distinctions matter. `with()` has a different contract: unchanged mapped
values are not transformed again.
