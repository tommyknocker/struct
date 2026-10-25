# Transformations and copies

[Documentation](README.md) · [Executable example](../examples/06_transformations_and_copies.php)

The construction pipeline is:

1. Resolve the input key or default; reject missing required fields.
2. Apply input transformers in their declared order.
3. Map the transformed value to the declared type, including nested DTOs.
4. Run item rules for typed collections and whole-field validation rules.
5. Check the native property type and assign the readonly field.

Unknown/conflicting keys and model schema errors are checked before input
transformers run. Defaults use the same pipeline. For a collection, a transformer
receives the complete field value, not each element separately.

## Explicit conversions

```php
use tommyknocker\struct\exception\TransformationException;
use tommyknocker\struct\transformation\TransformerInterface;

final class DecimalInteger implements TransformerInterface
{
    public function transform(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }
        $number = filter_var($value, FILTER_VALIDATE_INT);
        if ($number === false) {
            throw new TransformationException('Expected a decimal integer');
        }
        return $number;
    }
}
```

Use it in `Field(transformers: [new DecimalInteger()])` on an integer property.
Input strings are not otherwise coerced to numbers. Existing built-in
StringToLowerTransformer and StringToUpperTransformer use PHP's ASCII casing
functions and leave non-string input unchanged. Use a custom transformer for
Unicode casing or other normalization policies.

To construct a custom value object, return an instance accepted by the native
property type. Struct does not invoke arbitrary class constructors on arrays.
For expected invalid input, throw TransformationException; the mapper wraps it
in ValidationException with the field path, failed step's input, and cause.
Unrelated exceptions and programming errors propagate unchanged.

Transformers are input normalization, not output formatting. Output follows the
[serialization contract](serialization.md); custom value objects can implement
JsonSerializable to define their wire representation. Extensions should be
stateless and should avoid mutating incoming objects.

## with() operates on mapped values

```php
$updated = $original->with(['attempts' => '1']);
```

The method creates a new DTO, revalidates all field values, and applies input
transformers only to explicit replacements. An unchanged prefixed reference
will not be prefixed a second time. Failed replacements leave source field
assignments intact. Property names or input aliases are accepted; simultaneous
names and unknown replacement keys are rejected, even on permissive DTOs.
Output aliases do not become input names automatically.

Unchanged collection shapes are retained, including numeric-keyed and empty maps
in Preserve mode. A replacement determines its new shape after transformation.

Unmarked application state is copied. Copies are shallow: nested objects are
shared, and readonly does not freeze their internals. A subsequent
`fromJson($dto->toJson())` is a new input mapping operation and applies input
transformers again; it has different semantics from `with()`.
