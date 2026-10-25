<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
use tommyknocker\struct\exception\ValidationException;
use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

class PermissivePayload extends Struct
{
    #[Field(alias: 'user_name')]
    public readonly string $name;
}

final class StrictPayload extends PermissivePayload
{
    protected const STRICT = true;
}

final class StructStrictModeTest extends TestCase
{
    public function testPolicyIsIsolatedPerDtoWithoutGlobalState(): void
    {
        $this->assertSame('Alice', (new PermissivePayload(['name' => 'Alice', 'extra' => 1]))->name);

        try {
            new StrictPayload(['name' => 'Bob', 'extra' => 1]);
            $this->fail('Expected unknown key to be rejected');
        } catch (ValidationException $e) {
            $this->assertSame('extra', $e->fieldName);
            $this->assertSame(1, $e->value);
        }
        $this->assertSame('Charlie', (new PermissivePayload(['name' => 'Charlie', 'extra' => 1]))->name);
    }

    public function testStrictDtoAcceptsEitherInputName(): void
    {
        $this->assertSame('Alice', (new StrictPayload(['name' => 'Alice']))->name);
        $this->assertSame('Bob', (new StrictPayload(['user_name' => 'Bob']))->name);
    }

    public function testStrictDtoRejectsSimultaneousInputNames(): void
    {
        $this->expectException(ValidationException::class);
        new StrictPayload(['name' => 'Alice', 'user_name' => 'Bob']);
    }
}
