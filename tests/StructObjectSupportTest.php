<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use tommyknocker\struct\tests\fixtures\Address;
use tommyknocker\struct\tests\fixtures\User;

final class StructObjectSupportTest extends TestCase
{
    public function testNestedObject(): void
    {
        $address = new Address(['city' => 'Amsterdam', 'street' => 'Damrak']);
        $user = new User([
            'name' => 'Alice',
            'address' => $address,
            'previousAddresses' => [$address],
        ]);

        $this->assertSame('Alice', $user->name);
        $this->assertInstanceOf(Address::class, $user->address);
        $this->assertSame('Amsterdam', $user->address->city);
        $this->assertCount(1, $user->previousAddresses);
    }

    public function testWrongTypeThrows(): void
    {
        $this->expectException(RuntimeException::class);
        new User([
            'name' => 'Bob',
            'address' => 'not-an-object',
            'previousAddresses' => [],
        ]);
    }

    public function testArrayOfObjectsValidation(): void
    {
        $address1 = new Address(['city' => 'Berlin', 'street' => 'Unter den Linden']);
        $address2 = new Address(['city' => 'Paris', 'street' => 'Champs-Élysées']);

        $user = new User([
            'name' => 'Charlie',
            'address' => $address1,
            'previousAddresses' => [$address1, $address2],
        ]);

        $this->assertCount(2, $user->previousAddresses);
        $this->assertSame('Paris', $user->previousAddresses[1]->city);
    }
}
