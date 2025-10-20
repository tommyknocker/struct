<?php

declare(strict_types=1);

namespace tommyknocker\struct;

use ArrayAccess;
use JsonSerializable;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;

/**
 * @implements ArrayAccess<string, mixed>
 */
abstract class Struct implements ArrayAccess, JsonSerializable
{
    public static ?ContainerInterface $container = null;

    /**
     * Cache for reflection data to improve performance
     * @var array<string, array<ReflectionProperty>>
     */
    private static array $reflectionCache = [];

    /**
     * Construct a new struct
     * @param array<string, mixed> $data
     * @return void
     */
    public function __construct(array $data)
    {
        $className = static::class;

        if (!isset(self::$reflectionCache[$className])) {
            $ref = new ReflectionClass($this);
            self::$reflectionCache[$className] = $ref->getProperties();
        }

        foreach (self::$reflectionCache[$className] as $property) {
            $this->assignProperty($property, $data);
        }
    }

    /**
     * Assign a property to the struct
     * @param ReflectionProperty $property
     * @param array<string, mixed> $data
     * @return void
     */
    protected function assignProperty(ReflectionProperty $property, array $data): void
    {
        $name = $property->getName();
        $attributes = $property->getAttributes(Field::class);

        if (empty($attributes)) {
            return;
        }

        /** @var Field $field */
        $field = $attributes[0]->newInstance();

        // Support both property name and alias
        $hasPropertyName = array_key_exists($name, $data);
        $hasAlias = $field->alias && array_key_exists($field->alias, $data);

        // Check if value exists in data (prefer alias over property name)
        if (!$hasPropertyName && !$hasAlias) {
            // Use default value if available
            if ($field->default !== null) {
                $property->setValue($this, $field->default);

                return;
            }
            // Allow missing value if nullable
            if ($field->nullable) {
                $property->setValue($this, null);

                return;
            }

            throw new RuntimeException("Missing required field: $name" . ($field->alias ? " (alias: {$field->alias})" : ""));
        }

        // Get value (prefer alias over property name)
        $value = $hasAlias ? $data[$field->alias] : $data[$name];

        if ($value === null) {
            if (!$field->nullable) {
                throw new RuntimeException("Field $name cannot be null");
            }
            $property->setValue($this, null);

            return;
        }

        // Handle array fields
        if ($field->isArray) {
            if (!is_array($value)) {
                throw new RuntimeException("Field $name must be an array of {$field->type}");
            }
            $items = [];
            foreach ($value as $item) {
                $castedItem = $this->castValue($field->type, $item, $name);
                $this->validateValue($field, $castedItem, $name);
                $items[] = $castedItem;
            }
            $property->setValue($this, $items);

            return;
        }

        $castedValue = $this->castValue($field->type, $value, $name);
        $this->validateValue($field, $castedValue, $name);
        $property->setValue($this, $castedValue);
    }

    /**
     * Validate a value using custom validator if specified
     * @param Field $field
     * @param mixed $value
     * @param string $fieldName
     * @return void
     */
    protected function validateValue(Field $field, mixed $value, string $fieldName): void
    {
        if ($field->validator === null) {
            return;
        }

        if (!class_exists($field->validator)) {
            throw new RuntimeException("Validator class {$field->validator} does not exist");
        }

        if (!method_exists($field->validator, 'validate')) {
            throw new RuntimeException("Validator {$field->validator} must have a static validate() method");
        }

        $result = $field->validator::validate($value);
        if ($result !== true) {
            $message = is_string($result) ? $result : "Validation failed for field $fieldName";

            throw new RuntimeException($message);
        }
    }

    /**
     * Cast a value to the expected type
     * @param string $expected
     * @param mixed $value
     * @param string $fieldName
     * @return mixed
     */
    protected function castValue(string $expected, mixed $value, string $fieldName): mixed
    {
        // Mixed type - accept anything
        if ($expected === 'mixed') {
            return $value;
        }

        // Scalars
        if (in_array($expected, ['string', 'int', 'float', 'bool'], true)) {
            if (get_debug_type($value) !== $expected) {
                throw new RuntimeException("Field $fieldName must be of type $expected, got " . get_debug_type($value));
            }

            return $value;
        }

        // Enums
        if (enum_exists($expected)) {
            if ($value instanceof $expected) {
                return $value;
            }

            // Try to convert from string/int for backed enums
            if (is_string($value) || is_int($value)) {
                // Check if it's a backed enum by checking if it implements BackedEnum
                if (is_subclass_of($expected, \BackedEnum::class)) {
                    try {
                        return $expected::from($value);
                    } catch (\ValueError $e) {
                        throw new RuntimeException("Invalid value '$value' for enum $expected");
                    }
                }
            }

            throw new RuntimeException("Field $fieldName must be instance of enum $expected");
        }

        // DateTime support
        if (is_a($expected, \DateTimeInterface::class, true)) {
            if ($value instanceof \DateTimeInterface) {
                return $value;
            }
            if (is_string($value)) {
                try {
                    return new \DateTimeImmutable($value);
                } catch (\Exception $e) {
                    throw new RuntimeException("Field $fieldName: invalid datetime string: {$e->getMessage()}");
                }
            }

            throw new RuntimeException("Field $fieldName must be DateTimeInterface or string");
        }

        // Classes
        if (class_exists($expected)) {
            if ($value instanceof $expected) {
                return $value;
            }
            if (is_array($value)) {
                if (self::$container && self::$container->has($expected)) {
                    $instance = self::$container->get($expected);
                    if ($instance instanceof Struct) {
                        return new $expected($value);
                    }

                    return $instance;
                }

                return new $expected($value);
            }

            throw new RuntimeException("Field $fieldName must be instance of $expected or array");
        }

        throw new RuntimeException("Unsupported type $expected for field $fieldName");
    }

    // ArrayAccess
    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && property_exists($this, $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->{$offset};
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new RuntimeException("Cannot modify readonly struct properties via array access");
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new RuntimeException("Cannot unset readonly struct properties");
    }

    // JsonSerializable
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * Convert struct to array (recursive for nested structs)
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [];
        foreach (get_object_vars($this) as $key => $value) {
            if ($value instanceof self) {
                $result[$key] = $value->toArray();
            } elseif (is_array($value)) {
                $result[$key] = array_map(
                    fn ($item) => $item instanceof self ? $item->toArray() : $item,
                    $value
                );
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public function toJson(
        bool $pretty = false,
        int $flags = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ): string {
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }
        $result = json_encode($this, $flags);
        if ($result === false) {
            throw new RuntimeException("Failed to encode struct to JSON");
        }

        return $result;
    }

    /**
     * Create struct from JSON string
     * @param string $json
     * @param int $flags
     * @return static
     * @phpstan-return static
     */
    public static function fromJson(string $json, int $flags = JSON_THROW_ON_ERROR): static
    {
        $data = json_decode($json, true, 512, $flags);
        if (!is_array($data)) {
            throw new RuntimeException("JSON must decode to an array");
        }

        return new static($data);
    }

    /**
     * Create a copy of the struct with optional modifications
     * @param array<string, mixed> $changes array of field => value to change
     * @return static
     * @phpstan-return static
     */
    public function with(array $changes): static
    {
        $data = $this->toArray();
        foreach ($changes as $key => $value) {
            $data[$key] = $value;
        }

        return new static($data);
    }
}
