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
    public function testGeneratedAttributesCanConstructRealStructs(): void
    {
        $helper = new AttributeHelper();
        $properties = [
            'name' => new ReflectionProperty(TestStructWithoutAttributes::class, 'name'),
            'age' => new ReflectionProperty(TestStructWithoutAttributes::class, 'age'),
            'tags' => new ReflectionProperty(TestStructWithoutAttributes::class, 'tags'),
            'value' => new ReflectionProperty(TestStructWithUnionTypes::class, 'flexibleValue'),
        ];
        $declarations = ['name' => 'string', 'age' => 'int', 'tags' => 'array', 'value' => 'string|int'];
        $source = 'return new class(["name" => "Alice", "age" => 125, "tags" => ["a"], "value" => 42]) extends \\tommyknocker\\struct\\Struct {';
        foreach ($properties as $name => $property) {
            $source .= $helper->generateFieldAttribute($property) . ' public readonly ' . $declarations[$name] . ' $' . $name . ';';
        }
        /** @var Struct $struct */
        $struct = eval($source . '};');
        $this->assertSame(['name' => 'Alice', 'age' => 125, 'tags' => ['a'], 'value' => 42], $struct->toArray());
    }

    public function testDoesNotInventRulesAliasesTransformationsOrDefaults(): void
    {
        $helper = new AttributeHelper();
        foreach ([TestStructWithValidation::class, TestStructWithDefaults::class, TestStructWithAliases::class] as $class) {
            foreach ($helper->processClass($class) as $attribute) {
                $this->assertStringNotContainsString('validationRules:', $attribute);
                $this->assertStringNotContainsString('transformers:', $attribute);
                $this->assertStringNotContainsString('default:', $attribute);
                $this->assertStringNotContainsString('alias:', $attribute);
            }
        }
    }

    public function testNullableTypeIsPreserved(): void
    {
        $helper = new AttributeHelper();
        $attribute = $helper->generateFieldAttribute(new ReflectionProperty(TestStructWithoutAttributes::class, 'email'));
        /** @var Struct $struct */
        $struct = eval('return new class(["email" => null]) extends \\tommyknocker\\struct\\Struct {' . $attribute . ' public readonly ?string $email; };');
        $this->assertNull($struct['email']);
    }

    public function testOnlyMissingAttributesAreSelected(): void
    {
        $helper = new AttributeHelper();
        $properties = $helper->getPropertiesNeedingAttributes(TestStructWithSomeAttributes::class);
        $this->assertSame(['age', 'email'], array_map(fn (ReflectionProperty $p): string => $p->getName(), $properties));
        $this->assertTrue($helper->hasFieldAttribute(new ReflectionProperty(TestStructWithSomeAttributes::class, 'name')));
    }

    public function testMissingClassProducesLibraryException(): void
    {
        $this->expectException(ClassProcessingException::class);
        (new AttributeHelper())->processClass('NonExistentClass');
    }
}
