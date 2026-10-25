<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
use tommyknocker\struct\transformation\StringToLowerTransformer;
use tommyknocker\struct\transformation\StringToUpperTransformer;

final class TransformationTest extends TestCase
{
    public function testStringToUpperTransformer(): void
    {
        $transformer = new StringToUpperTransformer();

        $this->assertSame('HELLO', $transformer->transform('hello'));
        $this->assertSame('WORLD', $transformer->transform('world'));
        $this->assertSame(123, $transformer->transform(123)); // Non-string unchanged
    }

    public function testStringToLowerTransformer(): void
    {
        $transformer = new StringToLowerTransformer();

        $this->assertSame('hello', $transformer->transform('HELLO'));
        $this->assertSame('world', $transformer->transform('WORLD'));
        $this->assertSame(123, $transformer->transform(123)); // Non-string unchanged
    }
}
