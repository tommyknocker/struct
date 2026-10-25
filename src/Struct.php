<?php

declare(strict_types=1);

namespace tommyknocker\struct;

use ArrayAccess;
use BackedEnum;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;
use stdClass;
use Throwable;
use tommyknocker\struct\cache\ReflectionCache;
use tommyknocker\struct\exception\FieldNotFoundException;
use tommyknocker\struct\exception\TransformationException;
use tommyknocker\struct\exception\ValidationException;
use tommyknocker\struct\metadata\FieldMetadata;
use tommyknocker\struct\metadata\MetadataFactory;
use tommyknocker\struct\metadata\StructMetadata;
use tommyknocker\struct\validation\FieldValidator;
use TypeError;
use UnitEnum;
use ValueError;

/** @implements ArrayAccess<string, mixed> */
abstract class Struct implements ArrayAccess, JsonSerializable
{
    /** Override on a DTO to reject unknown keys; immutable and isolated per class. */
    protected const STRICT = false;
    /** @var array<string, bool> Whether a collection must encode as a JSON object. */
    private array $collectionMaps = [];
    private const MAX_MAPPING_DEPTH = 128;
    private const MAX_EXPORT_DEPTH = 512;

    /**
     * @param array<string, mixed> $data
     * @throws ValidationException
     * @throws FieldNotFoundException
     */
    final public function __construct(array $data)
    {
        $this->hydrate($data, 0);
    }

    private static function metadata(): StructMetadata
    {
        return (new MetadataFactory())->getMetadata(static::class);
    }

    /** @param array<string, mixed> $data */
    private function hydrate(array $data, int $depth): void
    {
        if ($depth > self::MAX_MAPPING_DEPTH) {
            throw new ValidationException('Maximum mapping depth exceeded', '$', null);
        }
        $this->validateInputKeys($data, static::STRICT);
        foreach (self::metadata()->fields as $field) {
            [$present, $value] = $this->inputValue($field, $data);
            if (!$present) {
                if ($field->hasDefault()) {
                    $value = $this->copyDefault($field->default);
                } elseif (!$field->required) {
                    $value = null;
                } else {
                    throw new FieldNotFoundException($field->name, $field->alias);
                }
            }
            $this->assign($field, $value, true, $depth);
        }
    }

    /** @param array<string, mixed> $data */
    private function validateInputKeys(array $data, bool $strict): void
    {
        $metadata = self::metadata();
        foreach ($metadata->fields as $field) {
            if ($field->alias !== null && $field->alias !== $field->name
                && array_key_exists($field->name, $data) && array_key_exists($field->alias, $data)) {
                throw new ValidationException('Conflicting input keys for field ' . $field->name, $field->name, $data);
            }
        }
        if ($strict) {
            $allowed = $metadata->getAllowedFieldNames();
            foreach ($data as $key => $value) {
                if (!in_array((string) $key, $allowed, true)) {
                    throw new ValidationException("Unknown field: $key", (string) $key, $value);
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array{bool, mixed}
     */
    private function inputValue(FieldMetadata $field, array $data): array
    {
        if ($field->alias !== null && array_key_exists($field->alias, $data)) {
            return [true, $data[$field->alias]];
        }

        return [array_key_exists($field->name, $data), $data[$field->name] ?? null];
    }

    private function copyDefault(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->copyDefault($item), $value);
        }
        if ($value instanceof stdClass) {
            $copy = new stdClass();
            foreach (get_object_vars($value) as $key => $item) {
                $copy->{$key} = $this->copyDefault($item);
            }

            return $copy;
        }

        return $value instanceof DateTimeInterface ? clone $value : $value;
    }

    private function assign(FieldMetadata $field, mixed $value, bool $transform, int $depth, ?bool $preservedMap = null): void
    {
        $name = $field->name;
        if ($transform) {
            foreach ($field->transformers as $transformer) {
                try {
                    $value = $transformer->transform($value);
                } catch (TransformationException $e) {
                    throw new ValidationException($e->getMessage(), $name, $value, $e);
                }
            }
        }
        $validator = new FieldValidator();
        if ($value === null) {
            if (!$field->nullable) {
                throw new ValidationException("Field $name cannot be null", $name, $value);
            }
        } elseif ($field->collection !== null) {
            $fromObject = $value instanceof stdClass;
            if ($fromObject && $field->collection === CollectionType::List) {
                throw new ValidationException("Field $name must be a list", $name, $value);
            }
            if ($value instanceof stdClass) {
                $value = get_object_vars($value);
            }
            if (!is_array($value)) {
                throw new ValidationException("Field $name must be an array", $name, $value);
            }
            if ($field->collection === CollectionType::List && !array_is_list($value)) {
                throw new ValidationException("Field $name must be a list with consecutive keys", $name, $value);
            }
            $this->collectionMaps[$name] = match ($field->collection) {
                CollectionType::List => false,
                CollectionType::Map => true,
                CollectionType::Preserve => $preservedMap ?? ($fromObject || !array_is_list($value)),
            };
            $items = [];
            foreach ($value as $key => $item) {
                $path = $name . '[' . $key . ']';
                $casted = $this->castValue($field->type, $item, $path, $depth);
                $validator->validateRules($casted, $field->itemRules, $path);
                $items[$key] = $casted;
            }
            $value = $items;
        } else {
            $value = $this->castValue($field->type, $value, $name, $depth);
        }
        $validator->validateField($field, $value, $name);

        try {
            $this->assertNativeType($field->property, $value);
            $field->property->setValue($this, $value);
        } catch (TypeError $e) {
            throw new ValidationException("Field $name does not match its PHP property type", $name, $value, $e);
        }
    }

    private function assertNativeType(ReflectionProperty $property, mixed $value): void
    {
        $type = $property->getType();
        if ($type !== null && !$this->matchesNativeType($type, $value, $property->getDeclaringClass()->getName())) {
            throw new TypeError('Value does not match ' . $type);
        }
    }

    private function matchesNativeType(\ReflectionType $type, mixed $value, string $declaringClass): bool
    {
        if ($value === null) {
            return $type->allowsNull();
        }
        if ($type instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $part) {
                if ($this->matchesNativeType($part, $value, $declaringClass)) {
                    return true;
                }
            }

            return false;
        }
        if ($type instanceof \ReflectionIntersectionType) {
            foreach ($type->getTypes() as $part) {
                if (!$this->matchesNativeType($part, $value, $declaringClass)) {
                    return false;
                }
            }

            return true;
        }
        if (!$type instanceof \ReflectionNamedType) {
            return false;
        }
        $name = $type->getName();

        return match ($name) {
            'mixed' => true,
            'object' => is_object($value),
            'iterable' => is_iterable($value),
            'float' => is_float($value) || is_int($value),
            'true' => $value === true,
            'false' => $value === false,
            'self' => is_object($value) && is_a($value, $declaringClass),
            'parent' => is_object($value) && ($parent = get_parent_class($declaringClass)) !== false && is_a($value, $parent),
            default => get_debug_type($value) === $name || (is_object($value) && is_a($value, $name)),
        };
    }

    /** @param string|list<string> $expected */
    private function castValue(string|array $expected, mixed $value, string $name, int $depth): mixed
    {
        if (!is_array($expected)) {
            return $this->castSingleValue($expected, $value, $name, $depth);
        }
        // Preserve an already matching runtime type before trying conversions.
        foreach ($expected as $type) {
            if ($type !== 'mixed' && $this->matchesMappingType($type, $value)) {
                return $this->castSingleValue($type, $value, $name, $depth);
            }
        }
        $previous = null;
        foreach ($expected as $type) {
            try {
                return $this->castSingleValue($type, $value, $name, $depth);
            } catch (ValidationException|FieldNotFoundException $e) {
                $previous = $e;
            }
        }

        throw new ValidationException('Field ' . $name . ' must be one of: ' . implode('|', $expected), $name, $value, $previous);
    }

    private function matchesMappingType(string $type, mixed $value): bool
    {
        return $type === get_debug_type($value)
            || ($type === 'true' && $value === true) || ($type === 'false' && $value === false)
            || (is_object($value) && is_a($value, $type));
    }

    private function castSingleValue(string $expected, mixed $value, string $name, int $depth): mixed
    {
        if ($expected === 'mixed' || ($expected === 'null' && $value === null)) {
            return $value;
        }
        if ($expected === 'array') {
            if ($value instanceof stdClass) {
                return get_object_vars($value);
            }
            if (is_array($value)) {
                return $value;
            }
        }
        if (($expected === 'object' && is_object($value)) || ($expected === 'iterable' && is_iterable($value))
            || ($expected === 'true' && $value === true) || ($expected === 'false' && $value === false)) {
            return $value;
        }
        if ($expected === 'float' && is_int($value)) {
            return (float) $value;
        }
        if (in_array($expected, ['string', 'int', 'float', 'bool'], true)) {
            if (get_debug_type($value) !== $expected || (is_float($value) && !is_finite($value))) {
                throw new ValidationException("Field $name must be of type $expected, got " . get_debug_type($value), $name, $value);
            }

            return $value;
        }
        if (enum_exists($expected)) {
            if ($value instanceof $expected) {
                return $value;
            }
            if (is_subclass_of($expected, BackedEnum::class) && (is_string($value) || is_int($value))) {
                try {
                    return $expected::from($value);
                } catch (ValueError|TypeError $e) {
                    throw new ValidationException("Invalid value for enum $expected", $name, $value, $e);
                }
            }
            if (is_string($value) && !is_subclass_of($expected, BackedEnum::class)) {
                foreach ($expected::cases() as $case) {
                    if ($case->name === $value) {
                        return $case;
                    }
                }
            }

            throw new ValidationException("Field $name must be instance of enum $expected", $name, $value);
        }
        if (is_a($expected, DateTimeInterface::class, true)) {
            if ($value instanceof $expected) {
                return $value;
            }
            if ($value instanceof DateTimeInterface) {
                if ($expected === DateTime::class) {
                    return DateTime::createFromInterface($value);
                }
                if ($expected === DateTimeImmutable::class) {
                    return DateTimeImmutable::createFromInterface($value);
                }
            }
            if (is_string($value) && in_array($expected, [DateTime::class, DateTimeImmutable::class, DateTimeInterface::class], true)) {
                try {
                    $class = $expected === DateTime::class ? DateTime::class : DateTimeImmutable::class;
                    $date = new $class($value);
                    $errors = DateTimeImmutable::getLastErrors();
                    if ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
                        throw new RuntimeException('Invalid calendar date');
                    }

                    return $date;
                } catch (Throwable $e) {
                    throw new ValidationException("Field $name: invalid datetime string: {$e->getMessage()}", $name, $value, $e);
                }
            }

            throw new ValidationException("Field $name must be DateTimeInterface or string", $name, $value);
        }
        if ((class_exists($expected) || interface_exists($expected)) && $value instanceof $expected) {
            return $value;
        }
        if (is_subclass_of($expected, self::class) && (is_array($value) || $value instanceof stdClass)) {
            $reflection = new ReflectionClass($expected);
            if (!$reflection->isInstantiable()) {
                throw new ValidationException("Field $name needs a concrete Struct type", $name, $value);
            }
            /** @var Struct $nested */
            $nested = $reflection->newInstanceWithoutConstructor();

            try {
                $nested->hydrate($value instanceof stdClass ? get_object_vars($value) : $value, $depth + 1);
            } catch (ValidationException $e) {
                throw new ValidationException($e->getMessage(), $name . '.' . $e->fieldName, $e->value, $e);
            } catch (FieldNotFoundException $e) {
                throw new FieldNotFoundException($name . '.' . $e->fieldName, $e->alias, $e);
            }

            return $nested;
        }

        throw new ValidationException("Field $name must be of type $expected, got " . get_debug_type($value), $name, $value);
    }

    final public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && self::metadata()->getFieldByName($offset) !== null;
    }

    final public function offsetGet(mixed $offset): mixed
    {
        $field = is_string($offset) ? self::metadata()->getFieldByName($offset) : null;
        if ($field === null) {
            throw new FieldNotFoundException(is_string($offset) ? $offset : get_debug_type($offset));
        }

        return $field->property->getValue($this);
    }

    final public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new RuntimeException('Cannot modify readonly struct properties via array access');
    }

    final public function offsetUnset(mixed $offset): void
    {
        throw new RuntimeException('Cannot unset readonly struct properties');
    }

    final public function jsonSerialize(): mixed
    {
        return (object) $this->export(0, true);
    }

    /** @return array<string, mixed> */
    final public function toArray(): array
    {
        return $this->export(0, false);
    }

    /** @return array<string, mixed> */
    private function export(int $depth, bool $jsonObjects): array
    {
        $result = [];
        foreach (self::metadata()->fields as $field) {
            $value = $this->normalizeValue($field->property->getValue($this), $depth + 1, $jsonObjects);
            if ($jsonObjects && $value !== null && ($this->collectionMaps[$field->name] ?? false)) {
                $value = (object) $value;
            }
            $result[$field->outputAlias ?? $field->name] = $value;
        }

        return $result;
    }

    private function normalizeValue(mixed $value, int $depth, bool $jsonObjects): mixed
    {
        if ($depth > self::MAX_EXPORT_DEPTH) {
            throw new RuntimeException('Maximum serialization depth exceeded (possible circular reference)');
        }
        if ($value instanceof self) {
            $result = $value->export($depth, $jsonObjects);

            return $jsonObjects ? (object) $result : $result;
        }
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d\TH:i:s.uP');
        }
        if ($value instanceof BackedEnum) {
            return $value->value;
        }
        if ($value instanceof UnitEnum) {
            return $value->name;
        }
        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->normalizeValue($item, $depth + 1, $jsonObjects), $value);
        }
        if ($value instanceof stdClass) {
            $result = [];
            foreach (get_object_vars($value) as $key => $item) {
                $result[$key] = $this->normalizeValue($item, $depth + 1, $jsonObjects);
            }

            return $jsonObjects ? (object) $result : $result;
        }
        if ($value instanceof JsonSerializable) {
            return $this->normalizeValue($value->jsonSerialize(), $depth + 1, $jsonObjects);
        }
        if (is_object($value) || is_resource($value) || (is_float($value) && !is_finite($value))) {
            throw new RuntimeException('Cannot serialize value of type ' . get_debug_type($value));
        }

        return $value;
    }

    final public function toJson(
        bool $pretty = false,
        int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
    ): string {
        return json_encode($this, $flags | JSON_THROW_ON_ERROR | ($pretty ? JSON_PRETTY_PRINT : 0));
    }

    /** @return static */
    final public static function fromJson(string $json, int $flags = 0): static
    {
        $value = json_decode($json, false, 512, $flags | JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        if (!$value instanceof stdClass) {
            throw new ValidationException('JSON root must be an object', '$', $value);
        }

        return new static(get_object_vars($value));
    }

    /**
     * Revalidate mapped values; transform only explicitly replaced fields.
     * @param array<string, mixed> $changes Property names or input aliases.
     * @return static
     */
    final public function with(array $changes): static
    {
        // A typo in a replacement is always an error, even for permissive input DTOs.
        $this->validateInputKeys($changes, true);
        $reflection = new ReflectionClass($this);
        $copy = $reflection->newInstanceWithoutConstructor();
        $metadata = self::metadata();
        foreach ($metadata->fields as $field) {
            [$present, $value] = $this->inputValue($field, $changes);
            $copy->assign($field, $present ? $value : $field->property->getValue($this), $present, 0, $present ? null : ($this->collectionMaps[$field->name] ?? null));
        }
        foreach (ReflectionCache::getProperties(static::class) as $property) {
            if ($property->getDeclaringClass()->getName() !== self::class && !$property->isStatic() && $metadata->getFieldByName($property->getName()) === null && $property->isInitialized($this)) {
                $property->setValue($copy, $property->getValue($this));
            }
        }

        return $copy;
    }
}
