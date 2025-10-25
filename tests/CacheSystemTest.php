<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
use tommyknocker\struct\cache\ReflectionCache;
use tommyknocker\struct\tests\fixtures\TestCacheStruct;
use tommyknocker\struct\tests\fixtures\TestCacheStruct2;

final class CacheSystemTest extends TestCase
{
    public function testReflectionCacheGetProperties(): void
    {
        $properties = ReflectionCache::getProperties(TestCacheStruct::class);

        $this->assertIsArray($properties);
        // Should include both declared properties plus any inherited ones
        $this->assertGreaterThanOrEqual(2, count($properties));

        $propertyNames = array_map(fn ($prop) => $prop->getName(), $properties);
        $this->assertContains('name', $propertyNames);
        $this->assertContains('age', $propertyNames);
    }

    public function testReflectionCacheCaching(): void
    {
        $properties1 = ReflectionCache::getProperties(TestCacheStruct::class);
        $properties2 = ReflectionCache::getProperties(TestCacheStruct::class);

        $this->assertSame($properties1, $properties2);
    }

    public function testReflectionCacheClearCache(): void
    {
        $properties1 = ReflectionCache::getProperties(TestCacheStruct::class);

        ReflectionCache::clearCache();

        $properties2 = ReflectionCache::getProperties(TestCacheStruct::class);

        // Should be different instances but same content
        $this->assertNotSame($properties1, $properties2);
        $this->assertCount(count($properties1), $properties2);
    }

    public function testReflectionCacheDifferentClasses(): void
    {
        $properties1 = ReflectionCache::getProperties(TestCacheStruct::class);
        $properties2 = ReflectionCache::getProperties(TestCacheStruct2::class);

        $this->assertNotSame($properties1, $properties2);
        $this->assertGreaterThanOrEqual(2, count($properties1));
        $this->assertGreaterThanOrEqual(1, count($properties2));
    }
}
