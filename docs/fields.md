# Fields, types, and input contracts

[Documentation](README.md) · [Examples](../examples/README.md)

Property declaration snippets in this guide belong inside a Struct subclass,
with `tommyknocker\struct\Field` and `tommyknocker\struct\Struct` imported.

Only properties marked with `#[Field]` participate in mapping and export. Fields
must be public, readonly, typed instance properties. Unmarked properties remain
ordinary application state and are not exported. Readonly is shallow: a nested
mutable date or object can still change internally.

`#[Field]` infers the mapping type from the property. Explicit types are useful
for collection elements or narrower mappings. Both the mapping type and native
PHP type must accept the value. Nullability comes exclusively from the PHP type.
The constructor and mapping/export methods are final; put application logic in
services or additional methods, rather than overriding hydration.

| Declaration | Missing input | Explicit null |
| --- | --- | --- |
| `#[Field] public readonly string $name` | Error | Error |
| `#[Field] public readonly ?string $name` | Error | Accepted by the type |
| `#[Field(required: false)] public readonly ?string $name` | Uses null | Accepted by the type |
| `#[Field(default: 'guest')] public readonly string $name` | Uses default | Error |
| `#[Field(default: null)] public readonly ?string $name` | Uses null | Accepted by the type |

Defaults satisfy required presence. Optional non-nullable fields need a default.
All chosen values, including defaults and nulls, go through the same pipeline.
Rules can reject a null that the type permits. The resulting object does not
track whether a null came from missing input; use separate presence tracking for
PATCH semantics.

Scalars are strict: numeric strings are not numbers and `1` is not a boolean.
An integer is accepted for a float field and becomes a float, matching native PHP
numeric widening. Nonfinite numeric values are rejected by numeric mappings.
Unions preserve an exact matching type before trying conversions; remaining
conversion candidates are tried in declaration order.

Nested arrays/JSON objects hydrate Struct subclasses. Other classes/interfaces
accept existing instances; use explicit input transformers to construct custom
value objects. Struct never invokes arbitrary array constructors. Provably
incompatible mapping and native property types are rejected as model errors
before data is processed. Inferred intersection types are unsupported; supply
an explicit mapping type if needed.

## Names and unknown keys

`alias` is an input name; `outputAlias` is an output name. Property names are also
accepted on input. Providing both names for one field is an error. Duplicate
output names and aliases shadowing another field are schema errors.

```php
#[Field(alias: 'user_name', outputAlias: 'displayName')]
public readonly string $name;
```

For symmetric import/export, declare identical input and output aliases. Supported
typed values can then be restored with `Dto::fromJson($dto->toJson())`.
Asymmetric output contracts are not automatically accepted as input.

Unknown input keys are ignored by default, allowing additive upstream changes.
Reject them for a specific DTO with a class constant:

```php
final class StrictRequest extends Struct
{
    protected const STRICT = true;

    #[Field]
    public readonly string $name;
}
```

The policy is immutable and isolated per DTO. Unknown keys and conflicting names
are checked before transformations run. Nested DTOs apply their own policy.
