<?php

declare(strict_types=1);

namespace tommyknocker\struct\metadata;

use DateTimeInterface;
use LogicException;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;
use stdClass;
use tommyknocker\struct\DefaultValue;
use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\transformation\TransformerInterface;
use tommyknocker\struct\validation\ValidationRule;
use UnitEnum;

final class MetadataFactory
{
    /** @var array<class-string, StructMetadata> */
    private static array $cache = [];

    /** @param class-string $className */
    public function getMetadata(string $className): StructMetadata
    {
        return self::$cache[$className] ??= $this->createMetadata($className);
    }

    /** @param class-string $className */
    private function createMetadata(string $className): StructMetadata
    {
        $reflection = new ReflectionClass($className);
        if (!$reflection->isSubclassOf(Struct::class)) {
            throw new LogicException('A mapped class must extend Struct: ' . $className);
        }
        $fields = [];
        $inputNames = [];
        $outputNames = [];
        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(Field::class);
            if ($attributes === []) {
                continue;
            }
            /** @var Field $definition */
            $definition = $attributes[0]->newInstance();
            $field = $this->compileField($property, $definition);
            $outputName = $field->outputAlias ?? $field->name;
            if (isset($outputNames[$outputName])) {
                throw new LogicException("Duplicate output field: $outputName");
            }
            $outputNames[$outputName] = true;
            foreach ([$field->name, $field->alias] as $inputName) {
                if ($inputName === null) {
                    continue;
                }
                if (isset($inputNames[$inputName]) && $inputNames[$inputName] !== $field->name) {
                    throw new LogicException("Ambiguous input field: $inputName");
                }
                $inputNames[$inputName] = $field->name;
            }
            $fields[$field->name] = $field;
        }

        return new StructMetadata($className, $fields);
    }

    private function compileField(ReflectionProperty $property, Field $field): FieldMetadata
    {
        $name = $property->getName();
        // PHP 8.1 needs a reflector created in the declaring class's scope to
        // initialize inherited readonly properties.
        $property = new ReflectionProperty($property->getDeclaringClass()->getName(), $name);
        $native = $property->getType();
        if (!$property->isPublic() || $property->isStatic() || !$property->isReadOnly() || $native === null) {
            throw new LogicException("Field must be a public readonly typed instance property: $name");
        }
        foreach ([$field->alias, $field->outputAlias] as $alias) {
            if ($alias === '') {
                throw new LogicException("Field aliases must not be empty: $name");
            }
        }
        $rules = $this->compileRules($field->validationRules, $name);
        $itemRules = $this->compileRules($field->itemRules, $name . '[]');
        if ($field->collection === null && $itemRules !== []) {
            throw new LogicException("Item rules require an explicit typed collection: $name");
        }
        $transformers = $this->compileTransformers($field->transformers, $name);
        $type = $field->type;
        if ($type === null) {
            if ($field->collection !== null) {
                throw new LogicException("Collection element type must be explicit: $name");
            }
            if ($native instanceof ReflectionNamedType) {
                $type = $native->getName();
            } elseif ($native instanceof ReflectionUnionType) {
                $type = [];
                foreach ($native->getTypes() as $part) {
                    if (!$part instanceof ReflectionNamedType) {
                        throw new LogicException("Intersection types require an explicit mapping type: $name");
                    }
                    if ($part->getName() !== 'null') {
                        $type[] = $part->getName();
                    }
                }
            } else {
                throw new LogicException("Intersection types require an explicit mapping type: $name");
            }
        }
        $types = is_array($type) ? $type : [$type];
        if ($types === [] || !array_is_list($types)) {
            throw new LogicException("Mapping types must be a nonempty list: $name");
        }
        foreach ($types as &$part) {
            $part = $this->validateTypeName($part, $name);
            if ($part === 'self') {
                $part = $property->getDeclaringClass()->getName();
            } elseif ($part === 'parent') {
                $parent = $property->getDeclaringClass()->getParentClass();
                if ($parent === false) {
                    throw new LogicException("Parent type without a parent class: $name");
                }
                $part = $parent->getName();
            }
            if (!in_array($part, ['string', 'int', 'float', 'bool', 'array', 'mixed', 'null', 'true', 'false', 'object', 'iterable'], true)
                && !class_exists($part) && !interface_exists($part)) {
                throw new LogicException("Unsupported mapping type $part for field $name");
            }
        }
        unset($part);
        foreach ($field->collection !== null ? ['array'] : $types as $resultType) {
            if (!$this->mappingCanMatch($resultType, $native, $property)) {
                throw new LogicException("Mapping type $resultType cannot match PHP property $name of type $native");
            }
        }
        $default = $field->default;
        if ($default === DefaultValue::Missing && $property->hasDefaultValue()) {
            $default = $property->getDefaultValue();
        }
        if ($default !== DefaultValue::Missing) {
            $this->validateDefault($default);
        }
        if (!$field->required && !$native->allowsNull() && $default === DefaultValue::Missing) {
            throw new LogicException("An optional non-nullable field needs a default: $name");
        }

        return new FieldMetadata(
            $name,
            is_array($type) ? $types : $types[0],
            $native->allowsNull(),
            $field->collection,
            $default,
            $field->alias,
            $rules,
            $transformers,
            $itemRules,
            $field->outputAlias,
            $field->required,
            $property
        );
    }

    /** Reject provably disjoint mapping/native types, allowing narrowing and PHP numeric widening. */
    private function mappingCanMatch(string $mapped, ReflectionType $native, ReflectionProperty $property): bool
    {
        if ($mapped === 'mixed') {
            return true;
        }
        if ($mapped === 'null') {
            return $native->allowsNull();
        }
        if ($native instanceof ReflectionUnionType) {
            foreach ($native->getTypes() as $part) {
                if ($this->mappingCanMatch($mapped, $part, $property)) {
                    return true;
                }
            }

            return false;
        }
        if ($native instanceof ReflectionIntersectionType) {
            foreach ($native->getTypes() as $part) {
                if (!$this->mappingCanMatch($mapped, $part, $property)) {
                    return false;
                }
            }

            return true;
        }
        if (!$native instanceof ReflectionNamedType) {
            return false;
        }
        $name = $native->getName();
        if ($name === 'self') {
            $name = $property->getDeclaringClass()->getName();
        } elseif ($name === 'parent') {
            $parent = $property->getDeclaringClass()->getParentClass();
            $name = $parent === false ? '' : $parent->getName();
        }
        if ($name === 'mixed' || $name === $mapped) {
            return true;
        }
        if ($mapped === 'int' && $name === 'float') {
            return true;
        }
        if (in_array($mapped, ['true', 'false'], true) && $name === 'bool') {
            return true;
        }
        if ($mapped === 'bool' && in_array($name, ['true', 'false'], true)) {
            return true;
        }
        if ($mapped === 'array') {
            return $name === 'iterable';
        }
        $mappedObject = $mapped === 'object' || class_exists($mapped) || interface_exists($mapped);
        $nativeObject = $name === 'object' || class_exists($name) || interface_exists($name);
        if ($mapped === 'iterable') {
            return $name === 'array' || $name === 'object' || ($nativeObject && $this->objectsCanOverlap(\Traversable::class, $name));
        }
        if ($name === 'iterable') {
            return $mappedObject && ($mapped === 'object' || $this->objectsCanOverlap($mapped, \Traversable::class));
        }
        if (!$mappedObject || !$nativeObject) {
            return false;
        }

        return $mapped === 'object' || $name === 'object' || $this->objectsCanOverlap($mapped, $name);
    }

    private function objectsCanOverlap(string $left, string $right): bool
    {
        if ((!class_exists($left) && !interface_exists($left)) || (!class_exists($right) && !interface_exists($right))) {
            return false;
        }
        if (is_a($left, $right, true) || is_a($right, $left, true)) {
            return true;
        }
        // Unrelated concrete classes cannot share an instance. Interfaces can
        // overlap unless a final concrete class rules out that implementation.
        $leftClass = new ReflectionClass($left);
        $rightClass = new ReflectionClass($right);
        if (!$leftClass->isInterface() && !$rightClass->isInterface()) {
            return false;
        }

        return !$leftClass->isFinal() && !$rightClass->isFinal();
    }

    private function validateTypeName(mixed $type, string $name): string
    {
        if (!is_string($type)) {
            throw new LogicException("Mapping type must be a string: $name");
        }

        return $type;
    }

    /** @param array<mixed> $values
     * @return list<ValidationRule>
     */
    private function compileRules(array $values, string $name): array
    {
        $result = [];
        foreach ($values as $value) {
            if (!$value instanceof ValidationRule) {
                throw new LogicException("Field rules must implement ValidationRule: $name");
            }
            $result[] = $value;
        }

        return $result;
    }

    /** @param array<mixed> $values
     * @return list<TransformerInterface>
     */
    private function compileTransformers(array $values, string $name): array
    {
        $result = [];
        foreach ($values as $value) {
            if (!$value instanceof TransformerInterface) {
                throw new LogicException("Field transformers must implement TransformerInterface: $name");
            }
            $result[] = $value;
        }

        return $result;
    }

    private function validateDefault(mixed $value, int $depth = 0): void
    {
        if ($depth > 128) {
            throw new LogicException('Default value is circular or exceeds maximum depth');
        }
        if ($value === null || is_scalar($value) || $value instanceof UnitEnum || $value instanceof DateTimeInterface) {
            return;
        }
        if (is_array($value) || $value instanceof stdClass) {
            foreach ((array) $value as $item) {
                $this->validateDefault($item, $depth + 1);
            }

            return;
        }

        throw new LogicException('Defaults must be data, enum cases, or dates; use raw arrays for nested DTO defaults');
    }

    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
