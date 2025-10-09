<?php
declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use tommyknocker\struct\struct;
use tommyknocker\struct\field;
use tommyknocker\struct\tests\fixtures\Address;
use tommyknocker\struct\tests\fixtures\User;

final class StructContainerTest extends TestCase
{
    public function testUsesContainerForObjectCreation(): void
    {
        // Simple container mock
        $container = new class implements ContainerInterface {
            private array $services = [];

            public function set(string $id, mixed $service): void
            {
                $this->services[$id] = $service;
            }

            public function get(string $id): mixed
            {
                return $this->services[$id];
            }

            public function has(string $id): bool
            {
                return isset($this->services[$id]);
            }
        };

        // Register Address in container
        $container->set(Address::class, new Address(['city' => 'Berlin', 'street' => 'Unter den Linden']));

        Struct::$container = $container;

        $user = new User([
            'name' => 'Alice',
            'address' => ['city' => 'Moscow', 'street' => 'Tverskaya st.'],
            'previousAddresses' => [
                ['city' => 'Amsterdam', 'street' => 'Keizersgracht 317, 1016 EE'],
                ['city' => 'Paris', 'street' => '12 Rue de Rivoli, 75004'],
            ]
        ]);

        $this->assertInstanceOf(Address::class, $user->address);
        $this->assertSame('Moscow', $user->address->city);
        $this->assertSame('Tverskaya st.', $user->address->street);

        $this->assertInstanceOf(Address::class, $user->previousAddresses[0]);
        $this->assertSame('Amsterdam', $user->previousAddresses[0]->city);
        $this->assertSame('Keizersgracht 317, 1016 EE', $user->previousAddresses[0]->street);

        $this->assertInstanceOf(Address::class, $user->previousAddresses[1]);
        $this->assertSame('Paris', $user->previousAddresses[1]->city);
        $this->assertSame('12 Rue de Rivoli, 75004', $user->previousAddresses[1]->street);
    }
}
