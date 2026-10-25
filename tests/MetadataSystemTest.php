<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
use tommyknocker\struct\metadata\MetadataFactory;
use tommyknocker\struct\tests\fixtures\TestStruct;
use tommyknocker\struct\tests\fixtures\TestStructWithNonField;

final class MetadataSystemTest extends TestCase
{
    public function testCompiledDefinitionsAreCachedAndCanBeCleared(): void
    {
        $factory = new MetadataFactory();
        $metadata = $factory->getMetadata(TestStruct::class);
        $this->assertSame($metadata, $factory->getMetadata(TestStruct::class));
        $field = $metadata->getFieldByName('name');
        $this->assertNotNull($field);
        $this->assertSame('string', $field->type);
        $this->assertSame('user_name', $field->alias);
        $this->assertSame($field, $metadata->getFieldByAlias('user_name'));
        $this->assertSame('name', $field->property->getName());
        $this->assertFalse($field->hasDefault());
        $this->assertContains('name', $metadata->getAllowedFieldNames());
        $this->assertContains('user_name', $metadata->getAllowedFieldNames());
        MetadataFactory::clearCache();
        $this->assertNotSame($metadata, $factory->getMetadata(TestStruct::class));
    }

    public function testUnmarkedPropertiesAreExcluded(): void
    {
        $metadata = (new MetadataFactory())->getMetadata(TestStructWithNonField::class);
        $this->assertSame(['name'], array_keys($metadata->fields));
        $this->assertNull($metadata->getFieldByName('nonField'));
        $this->assertNull($metadata->getFieldByAlias('unknown'));
    }
}
