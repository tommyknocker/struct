<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
use tommyknocker\struct\metadata\FieldMetadata;
use tommyknocker\struct\metadata\MetadataFactory;
use tommyknocker\struct\metadata\StructMetadata;
use tommyknocker\struct\tests\fixtures\TestStruct;
use tommyknocker\struct\tests\fixtures\TestStructWithNonField;
use tommyknocker\struct\transformation\StringToUpperTransformer;
use tommyknocker\struct\validation\rules\EmailRule;

final class MetadataSystemTest extends TestCase
{
    public function testFieldMetadataCreation(): void
    {
        $field = new FieldMetadata(
            'email',
            'string',
            false,
            false,
            null,
            'email_address',
            [new EmailRule()],
            [new StringToUpperTransformer()]
        );

        $this->assertSame('email', $field->name);
        $this->assertSame('string', $field->type);
        $this->assertFalse($field->nullable);
        $this->assertFalse($field->isArray);
        $this->assertNull($field->default);
        $this->assertSame('email_address', $field->alias);
        $this->assertCount(1, $field->validationRules);
        $this->assertCount(1, $field->transformers);
        $this->assertFalse($field->hasDefault());
    }

    public function testFieldMetadataWithDefault(): void
    {
        $field = new FieldMetadata(
            'name',
            'string',
            true,
            false,
            'default_value',
            null,
            [],
            []
        );

        $this->assertTrue($field->hasDefault());
        $this->assertSame('default_value', $field->default);
    }

    public function testStructMetadataCreation(): void
    {
        $field1 = new FieldMetadata('name', 'string', false, false, null, null, [], []);
        $field2 = new FieldMetadata('age', 'int', true, false, null, null, [], []);

        $metadata = new StructMetadata('TestStruct', [
            'name' => $field1,
            'age' => $field2,
        ]);

        $this->assertSame('TestStruct', $metadata->className);
        $this->assertCount(2, $metadata->fields);

        $this->assertSame($field1, $metadata->getFieldByName('name'));
        $this->assertSame($field2, $metadata->getFieldByName('age'));
        $this->assertNull($metadata->getFieldByName('nonexistent'));
    }

    public function testStructMetadataWithAlias(): void
    {
        $field = new FieldMetadata('name', 'string', false, false, null, 'user_name', [], []);

        $metadata = new StructMetadata('TestStruct', ['name' => $field]);

        $this->assertSame($field, $metadata->getFieldByAlias('user_name'));
        $this->assertNull($metadata->getFieldByAlias('nonexistent'));
    }

    public function testStructMetadataAllowedFieldNames(): void
    {
        $field1 = new FieldMetadata('name', 'string', false, false, null, null, [], []);
        $field2 = new FieldMetadata('age', 'int', false, false, null, 'user_age', [], []);

        $metadata = new StructMetadata('TestStruct', [
            'name' => $field1,
            'age' => $field2,
        ]);

        $allowedFields = $metadata->getAllowedFieldNames();
        $this->assertContains('name', $allowedFields);
        $this->assertContains('age', $allowedFields);
        $this->assertContains('user_age', $allowedFields);
        $this->assertCount(3, $allowedFields);
    }

    public function testMetadataFactory(): void
    {
        $factory = new MetadataFactory();

        $metadata = $factory->getMetadata(TestStruct::class);

        $this->assertInstanceOf(StructMetadata::class, $metadata);
        $this->assertSame(TestStruct::class, $metadata->className);

        $nameField = $metadata->getFieldByName('name');
        $this->assertInstanceOf(FieldMetadata::class, $nameField);
        $this->assertSame('string', $nameField->type);
        $this->assertSame('user_name', $nameField->alias);
    }

    public function testMetadataFactoryCaching(): void
    {
        $factory = new MetadataFactory();

        $metadata1 = $factory->getMetadata(TestStruct::class);
        $metadata2 = $factory->getMetadata(TestStruct::class);

        $this->assertSame($metadata1, $metadata2);
    }

    public function testMetadataFactoryIgnoresNonFieldProperties(): void
    {
        $factory = new MetadataFactory();

        $metadata = $factory->getMetadata(TestStructWithNonField::class);

        $this->assertCount(1, $metadata->fields);
        $this->assertArrayHasKey('name', $metadata->fields);
        $this->assertArrayNotHasKey('nonField', $metadata->fields);
    }

    public function testMetadataFactoryClearCache(): void
    {
        $factory = new MetadataFactory();

        // Get metadata to populate cache
        $metadata1 = $factory->getMetadata(TestStruct::class);
        $this->assertInstanceOf(StructMetadata::class, $metadata1);

        // Clear cache
        MetadataFactory::clearCache();

        // Get metadata again - should create new instance
        $metadata2 = $factory->getMetadata(TestStruct::class);
        $this->assertInstanceOf(StructMetadata::class, $metadata2);
        
        // Should be different instances after cache clear
        $this->assertNotSame($metadata1, $metadata2);
        
        // But should have same content
        $this->assertEquals($metadata1->className, $metadata2->className);
        $this->assertEquals($metadata1->fields, $metadata2->fields);
    }
}
