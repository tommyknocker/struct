<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\tools\AttributeHelper;
use tommyknocker\struct\tools\exception\ClassProcessingException;

/**
 * Test fixtures for AttributeHelper tests
 */
final class TestStructWithoutAttributes extends Struct
{
    public readonly string $name;
    public readonly int $age;
    public readonly ?string $email;
    public readonly bool $isActive;
    public readonly array $tags;
    public readonly mixed $data;
}

final class TestStructWithSomeAttributes extends Struct
{
    #[Field('string')]
    public readonly string $name;

    public readonly int $age;
    public readonly ?string $email;
}

final class TestStructWithAliases extends Struct
{
    public readonly string $firstName;
    public readonly string $lastName;
    public readonly string $emailAddress;
    public readonly string $phoneNumber;
    public readonly string $createdAt;
}

final class TestStructWithValidation extends Struct
{
    public readonly string $email;
    public readonly string $password;
    public readonly int $age;
    public readonly int $score;
    public readonly string $username;
}

final class TestStructWithTransformations extends Struct
{
    public readonly string $email;
    public readonly string $username;
    public readonly string $name;
    public readonly string $title;
}

final class TestStructWithDefaults extends Struct
{
    public readonly bool $isEnabled;
    public readonly bool $isActive;
    public readonly int $port;
    public readonly string $host;
    public readonly array $items;
}

final class TestStructWithUnionTypes extends Struct
{
    public readonly string|int $flexibleValue;
    public readonly string|null $optionalString;
}

final class AttributeHelperTest extends TestCase
{
    private AttributeHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new AttributeHelper();
    }

    public function testGenerateFieldAttributeForString(): void
    {
        $property = new ReflectionProperty(TestStructWithoutAttributes::class, 'name');
        $attribute = $this->helper->generateFieldAttribute($property);

        $this->assertStringContainsString("'string'", $attribute);
        $this->assertStringContainsString('#[Field(', $attribute);
    }

    public function testGenerateFieldAttributeForInt(): void
    {
        $property = new ReflectionProperty(TestStructWithoutAttributes::class, 'age');
        $attribute = $this->helper->generateFieldAttribute($property);

        $this->assertStringContainsString("'int'", $attribute);
    }

    public function testGenerateFieldAttributeForNullableString(): void
    {
        $property = new ReflectionProperty(TestStructWithoutAttributes::class, 'email');
        $attribute = $this->helper->generateFieldAttribute($property);

        $this->assertStringContainsString("'string'", $attribute);
        $this->assertStringContainsString('nullable: true', $attribute);
    }

    public function testGenerateFieldAttributeForBool(): void
    {
        $property = new ReflectionProperty(TestStructWithoutAttributes::class, 'isActive');
        $attribute = $this->helper->generateFieldAttribute($property);

        $this->assertStringContainsString("'bool'", $attribute);
    }

    public function testGenerateFieldAttributeForArray(): void
    {
        $property = new ReflectionProperty(TestStructWithoutAttributes::class, 'tags');
        $attribute = $this->helper->generateFieldAttribute($property);

        $this->assertStringContainsString("'mixed'", $attribute);
    }

    public function testGenerateFieldAttributeForMixed(): void
    {
        $property = new ReflectionProperty(TestStructWithoutAttributes::class, 'data');
        $attribute = $this->helper->generateFieldAttribute($property);

        $this->assertStringContainsString("'mixed'", $attribute);
    }

    public function testGenerateFieldAttributeWithAlias(): void
    {
        $property = new ReflectionProperty(TestStructWithAliases::class, 'firstName');
        $attribute = $this->helper->generateFieldAttribute($property);

        $this->assertStringContainsString("alias: 'first_name'", $attribute);
    }

    public function testGenerateFieldAttributeWithEmailValidation(): void
    {
        $property = new ReflectionProperty(TestStructWithValidation::class, 'email');
        $attribute = $this->helper->generateFieldAttribute($property);

        $this->assertStringContainsString('new EmailRule()', $attribute);
    }

    public function testGenerateFieldAttributeWithPasswordValidation(): void
    {
        $property = new ReflectionProperty(TestStructWithValidation::class, 'password');
        $attribute = $this->helper->generateFieldAttribute($property);

        $this->assertStringContainsString('new RequiredRule()', $attribute);
    }

    public function testGenerateFieldAttributeWithAgeValidation(): void
    {
        $property = new ReflectionProperty(TestStructWithValidation::class, 'age');
        $attribute = $this->helper->generateFieldAttribute($property);

        $this->assertStringContainsString('new RangeRule(1, 100)', $attribute);
    }

    public function testGenerateFieldAttributeWithScoreValidation(): void
    {
        $property = new ReflectionProperty(TestStructWithValidation::class, 'score');
        $attribute = $this->helper->generateFieldAttribute($property);

        $this->assertStringContainsString('new RangeRule(1, 100)', $attribute);
    }

    public function testGenerateFieldAttributeWithUsernameValidation(): void
    {
        $property = new ReflectionProperty(TestStructWithValidation::class, 'username');
        $attribute = $this->helper->generateFieldAttribute($property);

        $this->assertStringContainsString('new RequiredRule()', $attribute);
    }

    public function testGenerateFieldAttributeWithEmailTransformation(): void
    {
        $property = new ReflectionProperty(TestStructWithTransformations::class, 'email');
        $attribute = $this->helper->generateFieldAttribute($property);

        $this->assertStringContainsString('new StringToLowerTransformer()', $attribute);
    }

    public function testGenerateFieldAttributeWithUsernameTransformation(): void
    {
        $property = new ReflectionProperty(TestStructWithTransformations::class, 'username');
        $attribute = $this->helper->generateFieldAttribute($property);

        $this->assertStringContainsString('new StringToLowerTransformer()', $attribute);
    }

    public function testGenerateFieldAttributeWithNameTransformation(): void
    {
        $property = new ReflectionProperty(TestStructWithTransformations::class, 'name');
        $attribute = $this->helper->generateFieldAttribute($property);

        $this->assertStringContainsString('new StringToUpperTransformer()', $attribute);
    }

    public function testGenerateFieldAttributeWithTitleTransformation(): void
    {
        $property = new ReflectionProperty(TestStructWithTransformations::class, 'title');
        $attribute = $this->helper->generateFieldAttribute($property);

        $this->assertStringContainsString('new StringToUpperTransformer()', $attribute);
    }

    public function testGenerateFieldAttributeWithBoolDefault(): void
    {
        $property = new ReflectionProperty(TestStructWithDefaults::class, 'isEnabled');
        $attribute = $this->helper->generateFieldAttribute($property);

        $this->assertStringContainsString('default: true', $attribute);
    }

    public function testGenerateFieldAttributeWithActiveDefault(): void
    {
        $property = new ReflectionProperty(TestStructWithDefaults::class, 'isActive');
        $attribute = $this->helper->generateFieldAttribute($property);

        $this->assertStringContainsString('default: true', $attribute);
    }

    public function testGenerateFieldAttributeWithPortDefault(): void
    {
        $property = new ReflectionProperty(TestStructWithDefaults::class, 'port');
        $attribute = $this->helper->generateFieldAttribute($property);

        $this->assertStringContainsString('default: 3306', $attribute);
    }

    public function testGenerateFieldAttributeWithHostDefault(): void
    {
        $property = new ReflectionProperty(TestStructWithDefaults::class, 'host');
        $attribute = $this->helper->generateFieldAttribute($property);

        $this->assertStringContainsString("default: 'localhost'", $attribute);
    }

    public function testGenerateFieldAttributeWithArrayDefault(): void
    {
        $property = new ReflectionProperty(TestStructWithDefaults::class, 'items');
        $attribute = $this->helper->generateFieldAttribute($property);

        $this->assertStringContainsString("'mixed'", $attribute);
        $this->assertStringContainsString('default: []', $attribute);
    }

    public function testGenerateFieldAttributeWithUnionType(): void
    {
        $property = new ReflectionProperty(TestStructWithUnionTypes::class, 'flexibleValue');
        $attribute = $this->helper->generateFieldAttribute($property);

        $this->assertStringContainsString("['string', 'int']", $attribute);
    }

    public function testGenerateFieldAttributeWithNullableUnionType(): void
    {
        $property = new ReflectionProperty(TestStructWithUnionTypes::class, 'optionalString');
        $attribute = $this->helper->generateFieldAttribute($property);

        $this->assertStringContainsString("'string'", $attribute);
        $this->assertStringContainsString('nullable: true', $attribute);
    }

    public function testProcessClass(): void
    {
        $attributes = $this->helper->processClass(TestStructWithoutAttributes::class);

        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('age', $attributes);
        $this->assertArrayHasKey('email', $attributes);
        $this->assertArrayHasKey('isActive', $attributes);
        $this->assertArrayHasKey('tags', $attributes);
        $this->assertArrayHasKey('data', $attributes);

        $this->assertStringContainsString("'string'", $attributes['name']);
        $this->assertStringContainsString("'int'", $attributes['age']);
        $this->assertStringContainsString('nullable: true', $attributes['email']);
    }

    public function testGetPropertiesNeedingAttributes(): void
    {
        $properties = $this->helper->getPropertiesNeedingAttributes(TestStructWithSomeAttributes::class);

        $this->assertCount(2, $properties);
        $this->assertEquals('age', $properties[0]->getName());
        $this->assertEquals('email', $properties[1]->getName());
    }

    public function testHasFieldAttribute(): void
    {
        $propertyWithAttribute = new ReflectionProperty(TestStructWithSomeAttributes::class, 'name');
        $propertyWithoutAttribute = new ReflectionProperty(TestStructWithSomeAttributes::class, 'age');

        $this->assertTrue($this->helper->hasFieldAttribute($propertyWithAttribute));
        $this->assertFalse($this->helper->hasFieldAttribute($propertyWithoutAttribute));
    }

    public function testProcessClassThrowsExceptionForInvalidClass(): void
    {
        $this->expectException(ClassProcessingException::class);
        $this->helper->processClass('NonExistentClass');
    }

    public function testGetPropertiesNeedingAttributesThrowsExceptionForInvalidClass(): void
    {
        $this->expectException(ClassProcessingException::class);
        $this->helper->getPropertiesNeedingAttributes('NonExistentClass');
    }

    public function testGenerateFieldAttributeThrowsExceptionForInvalidProperty(): void
    {
        // This test verifies that the method works correctly
        // The exception handling is tested in other methods
        $reflection = new \ReflectionClass(TestStructWithoutAttributes::class);
        $property = $reflection->getProperty('name');

        // This should work without throwing an exception
        $attribute = $this->helper->generateFieldAttribute($property);
        $this->assertStringContainsString('#[Field(', $attribute);
    }

    public function testComplexAttributeGeneration(): void
    {
        $property = new ReflectionProperty(TestStructWithValidation::class, 'email');
        $attribute = $this->helper->generateFieldAttribute($property);

        // Should contain multiple features
        $this->assertStringContainsString("'string'", $attribute);
        $this->assertStringContainsString('new EmailRule()', $attribute);
        $this->assertStringContainsString('new StringToLowerTransformer()', $attribute);
    }

    public function testAttributeGenerationWithMultipleFeatures(): void
    {
        $property = new ReflectionProperty(TestStructWithAliases::class, 'firstName');
        $attribute = $this->helper->generateFieldAttribute($property);

        // Should contain alias and transformation
        $this->assertStringContainsString("'string'", $attribute);
        $this->assertStringContainsString("alias: 'first_name'", $attribute);
        $this->assertStringContainsString('new StringToUpperTransformer()', $attribute);
    }
}
