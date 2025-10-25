<?php

declare(strict_types=1);

namespace tommyknocker\struct;

use ArrayAccess;
use BackedEnum;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use JsonSerializable;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use RuntimeException;
use tommyknocker\struct\cache\ReflectionCache;
use tommyknocker\struct\exception\FieldNotFoundException;
use tommyknocker\struct\exception\ValidationException;
use tommyknocker\struct\metadata\MetadataFactory;
use tommyknocker\struct\transformation\TransformerInterface;
use tommyknocker\struct\validation\FieldValidator;
use ValueError;

/**
 * @implements ArrayAccess<string, mixed>
 */
abstract class Struct implements ArrayAccess, JsonSerializable
{
    /**
     * Optional PSR-11 container for dependency injection
     * @var ?ContainerInterface
     */
    public static ?ContainerInterface $container = null;
    /**
     * Enable strict mode to throw exception on unknown fields
     * @var bool
     */
    public static bool $strictMode = false;
    /**
     * Cache for reflection data to improve performance
     * @var array<string, array<ReflectionProperty>>
     */
    protected static array $reflectionCache = [];
    /**
     * Shared field validator instance
     * @var ?FieldValidator
     */
    protected static ?FieldValidator $fieldValidator = null;
    /**
     * Shared metadata factory instance
     * @var ?MetadataFactory
     */
    protected static ?MetadataFactory $metadataFactory = null;

    /**
     * Construct a new struct
     * @param array<string, mixed> $data
     * @return void
     */
    public function __construct(array $data)
    {
        $className = static::class;

        // Initialize services if not already done
        if (self::$fieldValidator === null) {
            self::$fieldValidator = new FieldValidator();
        }
        if (self::$metadataFactory === null) {
            self::$metadataFactory = new MetadataFactory();
        }

        $properties = ReflectionCache::getProperties($className);

        foreach ($properties as $property) {
            $this->assignProperty($property, $data);
        }

        // Check for unknown fields in strict mode
        if (self::$strictMode) {
            $this->validateNoExtraFields($data);
        }
    }

    /**
     * Validate that no extra fields are present in data
     * @param array<string, mixed> $data
     * @return void
     */
    protected function validateNoExtraFields(array $data): void
    {
        $metadata = self::$metadataFactory?->getMetadata(static::class);
        if ($metadata === null) {
            throw new RuntimeException('Metadata factory not initialized');
        }
        $allowedFields = $metadata->getAllowedFieldNames();

        foreach (array_keys($data) as $key) {
            if (!in_array($key, $allowedFields, true)) {
                throw new RuntimeException("Unknown field: $key");
            }
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

            throw new FieldNotFoundException($name, $field->alias);
        }

        // Get value (prefer alias over property name)
        $value = $hasAlias ? $data[$field->alias] : $data[$name];

        if ($value === null) {
            if (!$field->nullable) {
                throw new ValidationException("Field $name cannot be null", $name, $value);
            }
            $property->setValue($this, null);

            return;
        }

        // Apply transformations first
        $transformedValue = $this->applyTransformations($value, $field->transformers ?? []);

        // Handle array fields
        if ($field->isArray) {
            if (!is_array($transformedValue)) {
                $typeStr = is_array($field->type) ? implode('|', $field->type) : $field->type;

                throw new ValidationException("Field $name must be an array of $typeStr", $name, $transformedValue);
            }
            $items = [];
            foreach ($transformedValue as $item) {
                $castedItem = $this->castValue($field->type, $item, $name);

                // Apply validation to each array item
                try {
                    if (self::$fieldValidator === null) {
                        throw new RuntimeException('Field validator not initialized');
                    }
                    self::$fieldValidator->validateField($field, $castedItem, $name);
                } catch (ValidationException $e) {
                    throw $e;
                }

                $items[] = $castedItem;
            }
            $property->setValue($this, $items);

            return;
        }

        $castedValue = $this->castValue($field->type, $transformedValue, $name);

        // Use new validation system on the casted value
        try {
            if (self::$fieldValidator === null) {
                throw new RuntimeException('Field validator not initialized');
            }
            self::$fieldValidator->validateField($field, $castedValue, $name);
        } catch (ValidationException $e) {
            throw $e;
        }

        $property->setValue($this, $castedValue);
    }

    /**
     * Cast a value to the expected type
     * @param string|array<string> $expected
     * @param mixed $value
     * @param string $fieldName
     * @return mixed
     */
    protected function castValue(string|array $expected, mixed $value, string $fieldName): mixed
    {
        // Handle union types
        if (is_array($expected)) {
            foreach ($expected as $type) {
                try {
                    return $this->castSingleValue($type, $value, $fieldName);
                } catch (ValidationException) {
                    // Try next type
                    continue;
                }
            }
            $types = implode('|', $expected);

            throw new ValidationException("Field $fieldName must be one of: $types", $fieldName, $value);
        }

        return $this->castSingleValue($expected, $value, $fieldName);
    }

    /**
     * Cast a single value to the expected type
     * @param string $expected
     * @param mixed $value
     * @param string $fieldName
     * @return mixed
     */
    protected function castSingleValue(string $expected, mixed $value, string $fieldName): mixed
    {
        // Mixed type - accept anything
        if ($expected === 'mixed') {
            return $value;
        }

        // Scalars
        if (in_array($expected, ['string', 'int', 'float', 'bool'], true)) {
            if (get_debug_type($value) !== $expected) {
                throw new ValidationException("Field $fieldName must be of type $expected, got " . get_debug_type($value), $fieldName, $value);
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
                if (is_subclass_of($expected, BackedEnum::class)) {
                    try {
                        return $expected::from($value);
                    } catch (ValueError $e) {
                        throw new ValidationException("Invalid value '$value' for enum $expected", $fieldName, $value);
                    }
                }
            }

            throw new ValidationException("Field $fieldName must be instance of enum $expected", $fieldName, $value);
        }

        // DateTime support
        if (is_a($expected, DateTimeInterface::class, true)) {
            if ($value instanceof DateTimeInterface) {
                return $value;
            }
            if (is_string($value)) {
                try {
                    return new DateTimeImmutable($value);
                } catch (Exception $e) {
                    throw new ValidationException("Field $fieldName: invalid datetime string: {$e->getMessage()}", $fieldName, $value);
                }
            }

            throw new ValidationException("Field $fieldName must be DateTimeInterface or string", $fieldName, $value);
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

            throw new ValidationException("Field $fieldName must be instance of $expected or array", $fieldName, $value);
        }

        throw new ValidationException("Unsupported type $expected for field $fieldName", $fieldName, $value);
    }

    /**
     * Apply transformations to a value
     * @param mixed $value
     * @param TransformerInterface[] $transformers
     * @return mixed
     */
    protected function applyTransformations(mixed $value, array $transformers): mixed
    {
        foreach ($transformers as $transformer) {
            $value = $transformer->transform($value);
        }

        return $value;
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

    /**
     * Convert struct to JSON string
     * @param bool $pretty
     * @param int $flags
     * @return string
     */
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
