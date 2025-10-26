<?php

declare(strict_types=1);

namespace tommyknocker\struct\tools;

use ReflectionClass;
use ReflectionProperty;
use ReflectionUnionType;
use tommyknocker\struct\Field;
use tommyknocker\struct\tools\exception\AttributeHelperException;
use tommyknocker\struct\tools\exception\ClassProcessingException;

/**
 * Attribute Helper for Struct Library
 *
 * Automatically generates Field attributes based on property definitions
 * and intelligent type inference.
 */
final class AttributeHelper
{
    private const COMMON_ALIAS_PATTERNS = [
        'userId' => 'user_id',
        'firstName' => 'first_name',
        'lastName' => 'last_name',
        'emailAddress' => 'email_address',
        'phoneNumber' => 'phone_number',
        'createdAt' => 'created_at',
        'updatedAt' => 'updated_at',
        'fullName' => 'full_name',
        'userName' => 'user_name',
    ];

    /**
     * Generate Field attribute for a property
     *
     * @param ReflectionProperty $property
     * @return string Generated attribute string
     * @throws AttributeHelperException
     */
    public function generateFieldAttribute(ReflectionProperty $property): string
    {
        try {
            $type = $this->inferType($property);
            $nullable = $this->isNullable($property);
            $alias = $this->suggestAlias($property);
            $validationRules = $this->suggestValidationRules($property);
            $transformers = $this->suggestTransformers($property);
            $default = $this->suggestDefault($property);
            $isArray = $this->isArrayType($property);

            $params = $this->buildAttributeParameters([
                'type' => $type,
                'nullable' => $nullable,
                'alias' => $alias,
                'validationRules' => $validationRules,
                'transformers' => $transformers,
                'default' => $default,
                'isArray' => $isArray,
            ]);

            return "#[Field({$params})]";
        } catch (\Exception $e) {
            throw new AttributeHelperException(
                "Failed to generate attribute for property {$property->getName()}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Infer type from property declaration
     *
     * @param ReflectionProperty $property
     * @return string
     */
    private function inferType(ReflectionProperty $property): string
    {
        $type = $property->getType();

        if ($type === null) {
            return 'mixed';
        }

        // Handle union types
        if ($type instanceof ReflectionUnionType) {
            $types = [];
            foreach ($type->getTypes() as $unionType) {
                if ($unionType instanceof \ReflectionNamedType) {
                    $types[] = $unionType->getName();
                }
            }

            return '[' . implode(', ', array_map(fn ($t) => "'{$t}'", $types)) . ']';
        }

        if ($type instanceof \ReflectionNamedType) {
            $typeName = $type->getName();

            // For generic arrays, use 'mixed' type
            if ($typeName === 'array') {
                return 'mixed';
            }

            return $typeName;
        }

        return 'mixed';
    }

    /**
     * Check if property is nullable
     *
     * @param ReflectionProperty $property
     * @return bool
     */
    private function isNullable(ReflectionProperty $property): bool
    {
        $type = $property->getType();

        return $type !== null && $type->allowsNull();
    }

    /**
     * Check if property is array type
     *
     * @param ReflectionProperty $property
     * @return bool
     */
    private function isArrayType(ReflectionProperty $property): bool
    {
        // For generic arrays, we use 'mixed' type instead of isArray: true
        // isArray: true is only used for arrays of specific types
        return false;
    }

    /**
     * Suggest alias based on property name patterns
     *
     * @param ReflectionProperty $property
     * @return string|null
     */
    private function suggestAlias(ReflectionProperty $property): ?string
    {
        $name = $property->getName();

        // Check predefined patterns first
        if (isset(self::COMMON_ALIAS_PATTERNS[$name])) {
            return self::COMMON_ALIAS_PATTERNS[$name];
        }

        // Convert camelCase to snake_case
        if (preg_match('/[A-Z]/', $name)) {
            $result = preg_replace('/([a-z])([A-Z])/', '$1_$2', $name);

            return $result !== null ? strtolower($result) : null;
        }

        return null;
    }

    /**
     * Suggest validation rules based on property name and type
     *
     * @param ReflectionProperty $property
     * @return array<string>
     */
    private function suggestValidationRules(ReflectionProperty $property): array
    {
        $name = strtolower($property->getName());
        $rules = [];

        // Email validation
        if (str_contains($name, 'email')) {
            $rules[] = 'new EmailRule()';
        }

        // Range validation for numeric fields
        if (in_array($name, ['age', 'score', 'rating'], true)) {
            $rules[] = 'new RangeRule(1, 100)';
        }

        // Required validation for critical fields
        if (in_array($name, ['username', 'email', 'password', 'name'], true)) {
            $rules[] = 'new RequiredRule()';
        }

        return $rules;
    }

    /**
     * Suggest transformers based on property name
     *
     * @param ReflectionProperty $property
     * @return array<string>
     */
    private function suggestTransformers(ReflectionProperty $property): array
    {
        $name = strtolower($property->getName());
        $transformers = [];

        if (str_contains($name, 'email') || str_contains($name, 'username')) {
            $transformers[] = 'new StringToLowerTransformer()';
        }

        if ((str_contains($name, 'name') && !str_contains($name, 'username')) || str_contains($name, 'title')) {
            $transformers[] = 'new StringToUpperTransformer()';
        }

        return $transformers;
    }

    /**
     * Suggest default value based on type and name
     *
     * @param ReflectionProperty $property
     * @return string|null
     */
    private function suggestDefault(ReflectionProperty $property): ?string
    {
        $name = strtolower($property->getName());
        $type = $this->inferType($property);

        // Common defaults
        if (str_contains($name, 'enabled') || str_contains($name, 'active')) {
            return 'true';
        }

        if ($type === 'bool') {
            return 'false';
        }

        // Check if the original property type was array
        $originalType = $property->getType();
        if ($originalType !== null && !($originalType instanceof ReflectionUnionType) && $originalType instanceof \ReflectionNamedType && $originalType->getName() === 'array') {
            return '[]';
        }

        if (str_contains($name, 'port') && $type === 'int') {
            return '3306';
        }

        if (str_contains($name, 'host') && $type === 'string') {
            return "'localhost'";
        }

        return null;
    }

    /**
     * Build attribute parameter string
     *
     * @param array{type: string|array<string>, nullable: bool, isArray: bool, alias: string|null, validationRules: array<string>, transformers: array<string>, default: string|null} $params
     * @return string
     */
    private function buildAttributeParameters(array $params): string
    {
        $parts = [];

        // Type (required)
        if (is_array($params['type'])) {
            $parts[] = implode(', ', array_map(fn ($t) => "'{$t}'", $params['type']));
        } else {
            $parts[] = "'" . $params['type'] . "'";
        }

        // Optional parameters
        if ($params['nullable']) {
            $parts[] = 'nullable: true';
        }

        if ($params['isArray']) {
            $parts[] = 'isArray: true';
        }

        if ($params['alias'] !== null) {
            $parts[] = "alias: '" . $params['alias'] . "'";
        }

        if (!empty($params['validationRules'])) {
            $rules = implode(', ', $params['validationRules']);
            $parts[] = "validationRules: [{$rules}]";
        }

        if (!empty($params['transformers'])) {
            $transformers = implode(', ', $params['transformers']);
            $parts[] = "transformers: [{$transformers}]";
        }

        if ($params['default'] !== null) {
            $parts[] = "default: " . $params['default'];
        }

        return implode(', ', $parts);
    }

    /**
     * Process entire class and generate attributes
     *
     * @param string $className
     * @return array<string, string>
     * @throws AttributeHelperException
     */
    public function processClass(string $className): array
    {
        try {
            if (!class_exists($className)) {
                throw new ClassProcessingException($className, 'Class does not exist');
            }

            $reflection = new ReflectionClass($className);
            $results = [];

            foreach ($reflection->getProperties() as $property) {
                if ($property->isPublic() && !$property->isStatic()) {
                    $results[$property->getName()] = $this->generateFieldAttribute($property);
                }
            }

            return $results;
        } catch (\Exception $e) {
            throw new ClassProcessingException(
                $className,
                $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Check if property already has Field attribute
     *
     * @param ReflectionProperty $property
     * @return bool
     */
    public function hasFieldAttribute(ReflectionProperty $property): bool
    {
        $attributes = $property->getAttributes(Field::class);

        return !empty($attributes);
    }

    /**
     * Get properties that need Field attributes
     *
     * @param string $className
     * @return array<ReflectionProperty>
     * @throws AttributeHelperException
     */
    public function getPropertiesNeedingAttributes(string $className): array
    {
        try {
            if (!class_exists($className)) {
                throw new ClassProcessingException($className, 'Class does not exist');
            }

            $reflection = new ReflectionClass($className);
            $properties = [];

            foreach ($reflection->getProperties() as $property) {
                if ($property->isPublic() && !$property->isStatic() && !$this->hasFieldAttribute($property)) {
                    $properties[] = $property;
                }
            }

            return $properties;
        } catch (\Exception $e) {
            throw new ClassProcessingException(
                $className,
                $e->getMessage(),
                0,
                $e
            );
        }
    }
}
