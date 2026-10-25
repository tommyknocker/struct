# API reference

[Documentation](README.md) · [Examples](../examples/README.md)

## Field attribute

Namespace: `tommyknocker\struct`. Target: public readonly typed instance properties
on Struct subclasses. Native types define nullability. Parameters are best
supplied by name:

| Parameter | Type / default | Purpose |
| --- | --- | --- |
| `type` | `string\|array<string>\|null = null` | Mapping type; for collections, element type. Null infers the native field type |
| `collection` | `?CollectionType = null` | No typed collection, or List/Map/Preserve policy |
| `default` | `mixed = DefaultValue::Missing` | Missing input value; explicit null is a real default |
| `alias` | `?string = null` | Alternative input key; property name remains accepted |
| `validationRules` | `ValidationRule[] = []` | Rules for the whole mapped field |
| `transformers` | `TransformerInterface[] = []` | Ordered input transformations |
| `itemRules` | `ValidationRule[] = []` | Rules for each typed collection element |
| `outputAlias` | `?string = null` | Outbound name; defaults to the property name |
| `required` | `bool = true` | Require an input/default value; optional fields without a default must allow null |

Typed collections need an explicit element type. A list of types expresses a
mapping union. Provably incompatible mapping/native types and invalid options
are schema errors. See [fields](fields.md) and [collections](collections.md).

`Field::hasDefault(): bool` distinguishes an omitted default from any configured
value, including null. `DefaultValue::Missing` is the explicit programmatic marker.

## Struct

All mapping, copying, serialization, and array-access methods are final.
Subclasses declare fields, optional `protected const STRICT = true`, and
additional application methods. Construction itself cannot be overridden.

| Method | Behavior |
| --- | --- |
| `__construct(array $data)` | Map an associative input record into fields |
| `static fromJson(string $json, int $flags = 0): static` | Decode an object root and map it |
| `with(array $changes): static` | Create a validated shallow copy, transforming only replacements |
| `toArray(): array` | Recursively normalized outbound field values as PHP arrays |
| `toJson(bool $pretty = false, int $flags = ...): string` | Normalize and JSON-encode the DTO |
| `jsonSerialize(): mixed` | Object representation used by JsonSerializable |
| `offsetExists(mixed $offset): bool` | Whether a mapped property name exists, including nullable fields |
| `offsetGet(mixed $offset): mixed` | Read a mapped field; unknown offsets throw FieldNotFoundException |
| `offsetSet(...)` / `offsetUnset(...)` | Throw; array access cannot modify fields |

`fromJson()` always enables `JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING` and
retains JSON objects for mapping. Integers outside PHP's range become exact
strings. Other decode flags can be supplied.

`toJson()` default formatting flags are `JSON_UNESCAPED_UNICODE |
JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION`. It adds
`JSON_THROW_ON_ERROR` and optionally `JSON_PRETTY_PRINT`. Custom flags otherwise
follow `json_encode()` behavior.

See [serialization](serialization.md) for wire types, date formatting, mixed
object/list identity, round-trip limitations, and depth limits.

## Validation extensions

`validation\ValidationRule`:

```php
public function validate(mixed $value): ValidationResult;
```

Use `ValidationResult::valid()` or `ValidationResult::invalid(string $message)`.
The result exposes readonly `isValid`/`errorMessage` properties and equivalent
`isValid()` / `getErrorMessage()` accessors.

All built-in constructors and behavior are in the [rule reference](validation.md).

## Transformation extensions

`transformation\TransformerInterface`:

```php
public function transform(mixed $value): mixed;
```

Return normalized input or throw `exception\TransformationException` for expected
rejection. The mapper supplies field context. See
[transformations](transformations.md) and [errors](errors.md).

## Optional construction/serialization adapters

`factory\StructFactory`:

- `create(class-string<T> $structClass, array $data): T`
- `createFromJson(class-string<T> $structClass, string $json, int $flags = JSON_THROW_ON_ERROR): T`

`serialization\JsonSerializer` implements `SerializerInterface`:

- `serialize(Struct $struct): array` and `deserialize(array $data, class-string<Struct> $structClass): Struct`
- `toJson(Struct $struct, bool $pretty = false, int $flags = ...): string`
- `fromJson(string $json, class-string<Struct> $structClass, int $flags = JSON_THROW_ON_ERROR): Struct`

These delegate to Struct and do not define a separate wire contract. Despite the
adapter's name, `serialize()` returns an array; `toJson()` returns JSON text.
Neither adapter requires a container. Optional scaffolding and PHPStan integration
are covered in [tooling](tooling.md).
