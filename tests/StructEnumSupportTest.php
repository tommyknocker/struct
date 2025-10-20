<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use tommyknocker\struct\tests\fixtures\Account;
use tommyknocker\struct\tests\fixtures\UserType;

final class StructEnumSupportTest extends TestCase
{
    public function testValidEnum(): void
    {
        $account = new Account([
            'type' => UserType::Admin,
            'email' => 'admin@example.com',
        ]);

        $this->assertSame(UserType::Admin, $account->type);
        $this->assertSame('admin@example.com', $account->email);
    }

    public function testInvalidEnumThrows(): void
    {
        $this->expectException(RuntimeException::class);

        new Account([
            'type' => 'not-an-enum',
            'email' => 'user@example.com',
        ]);
    }
}
