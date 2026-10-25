<?php

declare(strict_types=1);

namespace tommyknocker\struct\tools;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use tommyknocker\struct\Field;
use tommyknocker\struct\tools\exception\AttributeHelperException;
use tommyknocker\struct\tools\exception\ClassProcessingException;

/** Explicit type scaffolding only; never guesses business rules from names. */
final class AttributeHelper
{
    public function generateFieldAttribute(ReflectionProperty $property): string
    {
        $type = $property->getType();
        $types = [];
        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $part) {
                if (!$part instanceof ReflectionNamedType) {
                    throw new AttributeHelperException('Intersection types require an explicit mapping');
                }
                if ($part->getName() !== 'null') {
                    $types[] = $part->getName();
                }
            }
        } elseif ($type instanceof ReflectionNamedType) {
            $types[] = $type->getName();
        } elseif ($type === null) {
            $types[] = 'mixed';
        } else {
            throw new AttributeHelperException('Intersection types require an explicit mapping');
        }
        $encoded = array_map(static fn (string $name): string => var_export($name, true), $types);
        $parameter = count($encoded) === 1 ? $encoded[0] : '[' . implode(', ', $encoded) . ']';

        return '#[\\tommyknocker\\struct\\Field(' . $parameter . ')]';
    }

    /** @return array<string, string> */
    public function processClass(string $className): array
    {
        $reflection = $this->reflect($className);
        $result = [];
        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic()) {
                $result[$property->getName()] = $this->generateFieldAttribute($property);
            }
        }

        return $result;
    }

    public function hasFieldAttribute(ReflectionProperty $property): bool
    {
        return $property->getAttributes(Field::class) !== [];
    }

    /** @return list<ReflectionProperty> */
    public function getPropertiesNeedingAttributes(string $className): array
    {
        $result = [];
        foreach ($this->reflect($className)->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic() && !$this->hasFieldAttribute($property)) {
                $result[] = $property;
            }
        }

        return $result;
    }

    /** @return ReflectionClass<object> */
    private function reflect(string $className): ReflectionClass
    {
        if (!class_exists($className)) {
            throw new ClassProcessingException($className, 'Class does not exist');
        }

        return new ReflectionClass($className);
    }
}
