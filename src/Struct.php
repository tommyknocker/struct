<?php
declare(strict_types=1);

namespace tommyknocker\struct;

use ArrayAccess;
use JsonSerializable;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;

abstract class Struct implements ArrayAccess, JsonSerializable
{
    public static ?ContainerInterface $container = null;

    public function __construct(array $data)
    {
        $ref = new ReflectionClass($this);

        foreach ($ref->getProperties() as $property) {
            $this->assignProperty($property, $data);
        }
    }

    protected function assignProperty(ReflectionProperty $property, array $data): void
    {
        $name = $property->getName();
        $attributes = $property->getAttributes(Field::class);

        if (empty($attributes)) {
            return;
        }

        /** @var Field $field */
        $field = $attributes[0]->newInstance();

        if (!array_key_exists($name, $data)) {
            throw new RuntimeException("Missing required field: $name");
        }

        $value = $data[$name];

        if ($value === null) {
            if (!$field->nullable) {
                throw new RuntimeException("Field $name cannot be null");
            }
            $property->setValue($this, null);
            return;
        }

        if ($field->isArray) {
            if (!is_array($value)) {
                throw new RuntimeException("Field $name must be an array of {$field->type}");
            }
            $items = [];
            foreach ($value as $item) {
                $items[] = $this->castValue($field->type, $item, $name);
            }
            $property->setValue($this, $items);
            return;
        }

        $property->setValue($this, $this->castValue($field->type, $value, $name));
    }

    protected function castValue(string $expected, mixed $value, string $fieldName): mixed
    {
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
            throw new RuntimeException("Field $fieldName must be instance of enum $expected");
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
        return property_exists($this, (string)$offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->{$offset};
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->{$offset} = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->{$offset});
    }

    // JsonSerializable
    public function jsonSerialize(): mixed
    {
        return get_object_vars($this);
    }

    public function toJson(
        bool $pretty = false,
        int $flags = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ): string {
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }
        return json_encode($this, $flags);
    }
}
