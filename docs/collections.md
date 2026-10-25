# Typed lists and maps

[Documentation](README.md) · [Examples](../examples/README.md)

A native `array` field checks only that the mapped value is an array. To map or
validate each element, declare both an element type and a collection policy.
Element types are native type names, enum/Struct/value-object class names, or a
list of alternative mapping types. They are not inferred from PHPDoc.

```php
use tommyknocker\struct\CollectionType;
use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\validation\rules\CountRule;
use tommyknocker\struct\validation\rules\EmailRule;

final class Recipients extends Struct
{
    /** @var list<string> */
    #[Field(
        'string',
        collection: CollectionType::List,
        validationRules: [new CountRule(min: 1, max: 10)],
        itemRules: [new EmailRule()]
    )]
    public readonly array $emails;
}
```

| Policy | Input | JSON output |
| --- | --- | --- |
| `List` | PHP list/JSON array with consecutive numeric keys; objects and sparse/associative arrays are rejected | Array, including `[]` |
| `Map` | PHP array or JSON object; keys are preserved | Object, including `{}` and objects with consecutive numeric keys |
| `Preserve` | List or map; JSON object shape is recorded even for empty/numeric-keyed objects | Original mapped shape, also retained by `with([])` |

For example, `#[Field(Item::class, collection: CollectionType::Map)]` exports an
empty collection as `{}`. Map mode deliberately interprets a PHP list as a
numeric-keyed map. For a PHP array in Preserve mode, shape follows its keys;
`[]` is a list. Use Map mode if an empty PHP array must represent a wire object.

`with()` retains the shape of unchanged collections. A replacement in Preserve
mode determines its own shape after input transformations. `toArray()` always
returns PHP arrays; use `toJson()` to retain an empty/numeric-keyed map's wire
shape. Nested DTOs use their own collection policies.

## Whole collection versus elements

Transformers receive the complete incoming field. After mapping,
`itemRules` validate each element and report paths such as `emails[2]`.
`validationRules` validate the complete collection, including an empty one.
Use `CountRule` for size and `OneOfRule`/`PatternRule`/other rules for elements.
Empty collections have no elements to validate, so item rules cannot require
nonemptiness.

A nullable array type controls nullability of the collection itself. Nullable
elements are separate: for example, `Field(['string', 'null'], collection: ...)`.
Rules still decide whether an otherwise allowed null satisfies a constraint.

See [nested orders](../examples/03_nested_payloads.php),
[collection validation](../examples/02_validation_rules.php), and
[allowed webhook events](../examples/11_webhook_subscription.php).
