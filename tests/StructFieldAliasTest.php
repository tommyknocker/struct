<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

final class UserProfile extends Struct
{
    #[Field('string', alias: 'user_id')]
    public readonly string $userId;

    #[Field('string', alias: 'first_name')]
    public readonly string $firstName;

    #[Field('string', alias: 'last_name')]
    public readonly string $lastName;

    #[Field('int', alias: 'age_years', nullable: true)]
    public readonly ?int $age;
}

final class ApiResponse extends Struct
{
    #[Field('string', alias: 'STATUS')]
    public readonly string $status;

    #[Field('float', alias: 'AMOUNT')]
    public readonly float $amount;
}

final class StructFieldAliasTest extends TestCase
{
    public function testAliasFieldAccess(): void
    {
        $profile = new UserProfile([
            'user_id' => 'usr_123',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'age_years' => 30,
        ]);

        $this->assertSame('usr_123', $profile->userId);
        $this->assertSame('John', $profile->firstName);
        $this->assertSame('Doe', $profile->lastName);
        $this->assertSame(30, $profile->age);
    }

    public function testPropertyNameAlsoWorks(): void
    {
        // Should accept both alias and property name
        $profile = new UserProfile([
            'userId' => 'usr_456',
            'firstName' => 'Jane',
            'lastName' => 'Smith',
            'age' => 25,
        ]);

        $this->assertSame('usr_456', $profile->userId);
        $this->assertSame('Jane', $profile->firstName);
        $this->assertSame('Smith', $profile->lastName);
        $this->assertSame(25, $profile->age);
    }

    public function testMixedAliasAndPropertyNames(): void
    {
        $profile = new UserProfile([
            'user_id' => 'usr_789', // alias
            'firstName' => 'Bob',    // property name
            'last_name' => 'Johnson', // alias
            'age' => 35,              // property name
        ]);

        $this->assertSame('usr_789', $profile->userId);
        $this->assertSame('Bob', $profile->firstName);
        $this->assertSame('Johnson', $profile->lastName);
        $this->assertSame(35, $profile->age);
    }

    public function testAliasPreferredOverPropertyName(): void
    {
        // If both provided, alias should win
        $profile = new UserProfile([
            'user_id' => 'from_alias',
            'userId' => 'from_property',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'age_years' => null,
        ]);

        $this->assertSame('from_alias', $profile->userId);
    }

    public function testUppercaseAlias(): void
    {
        $response = new ApiResponse([
            'STATUS' => 'success',
            'AMOUNT' => 99.99,
        ]);

        $this->assertSame('success', $response->status);
        $this->assertSame(99.99, $response->amount);
    }

    public function testMissingAliasFieldThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing required field: userId (alias: user_id)');

        new UserProfile([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'age_years' => 30,
        ]);
    }

    public function testToArrayReturnsPropertyNames(): void
    {
        $profile = new UserProfile([
            'user_id' => 'usr_123',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'age_years' => 30,
        ]);

        $array = $profile->toArray();

        $this->assertArrayHasKey('userId', $array);
        $this->assertArrayHasKey('firstName', $array);
        $this->assertArrayHasKey('lastName', $array);
        $this->assertArrayHasKey('age', $array);
        $this->assertSame('usr_123', $array['userId']);
    }

    public function testWithMethodWorksWithAliases(): void
    {
        $profile = new UserProfile([
            'user_id' => 'usr_123',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'age_years' => 30,
        ]);

        $updated = $profile->with(['age' => 31]);

        $this->assertSame(30, $profile->age);
        $this->assertSame(31, $updated->age);
    }
}
