<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

final class ConfigStruct extends Struct
{
    #[Field('string', default: 'localhost')]
    public readonly string $host;

    #[Field('int', default: 3306)]
    public readonly int $port;

    #[Field('string')]
    public readonly string $database;

    #[Field('string', default: 'utf8mb4')]
    public readonly string $charset;

    #[Field('bool', default: false)]
    public readonly bool $debug;
}

final class StructDefaultValuesTest extends TestCase
{
    public function testAllFieldsWithDefaults(): void
    {
        $config = new ConfigStruct([
            'database' => 'mydb',
        ]);

        $this->assertSame('localhost', $config->host);
        $this->assertSame(3306, $config->port);
        $this->assertSame('mydb', $config->database);
        $this->assertSame('utf8mb4', $config->charset);
        $this->assertFalse($config->debug);
    }

    public function testOverrideDefaults(): void
    {
        $config = new ConfigStruct([
            'host' => '192.168.1.100',
            'port' => 5432,
            'database' => 'postgres',
            'charset' => 'utf8',
            'debug' => true,
        ]);

        $this->assertSame('192.168.1.100', $config->host);
        $this->assertSame(5432, $config->port);
        $this->assertSame('postgres', $config->database);
        $this->assertSame('utf8', $config->charset);
        $this->assertTrue($config->debug);
    }

    public function testPartialOverride(): void
    {
        $config = new ConfigStruct([
            'host' => 'remote.host',
            'database' => 'testdb',
            'debug' => true,
        ]);

        $this->assertSame('remote.host', $config->host);
        $this->assertSame(3306, $config->port); // default
        $this->assertSame('testdb', $config->database);
        $this->assertSame('utf8mb4', $config->charset); // default
        $this->assertTrue($config->debug);
    }

    public function testMissingRequiredFieldWithoutDefault(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing required field: database');

        new ConfigStruct([
            'host' => 'localhost',
        ]);
    }

    public function testNullableWithoutDefault(): void
    {
        $struct = new class (['name' => 'test']) extends Struct {
            #[Field('string')]
            public readonly string $name;

            #[Field('int', nullable: true)]
            public readonly ?int $age;
        };

        $this->assertSame('test', $struct->name);
        $this->assertNull($struct->age);
    }
}
