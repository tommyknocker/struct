<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

class EmailValidator
{
    public static function validate(mixed $value): bool|string
    {
        if (!is_string($value)) {
            return "Email must be a string";
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "Invalid email format";
        }

        return true;
    }
}

class RangeValidator
{
    public static function validate(mixed $value): bool|string
    {
        if (!is_int($value)) {
            return "Value must be an integer";
        }

        if ($value < 1 || $value > 100) {
            return "Value must be between 1 and 100";
        }

        return true;
    }
}

class AlwaysFailValidator
{
    public static function validate(mixed $value): bool|string
    {
        return "Always fails";
    }
}

class NoValidateMethodValidator
{
    public static function check(mixed $value): bool
    {
        return true;
    }
}

final class ValidatedUser extends Struct
{
    #[Field('string')]
    public readonly string $username;

    #[Field('string', validator: EmailValidator::class)]
    public readonly string $email;

    #[Field('int', validator: RangeValidator::class)]
    public readonly int $score;
}

final class StructValidatorTest extends TestCase
{
    public function testValidatorPasses(): void
    {
        $user = new ValidatedUser([
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'score' => 85,
        ]);

        $this->assertSame('john_doe', $user->username);
        $this->assertSame('john@example.com', $user->email);
        $this->assertSame(85, $user->score);
    }

    public function testEmailValidatorFails(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid email format');

        new ValidatedUser([
            'username' => 'john_doe',
            'email' => 'not-an-email',
            'score' => 50,
        ]);
    }

    public function testRangeValidatorFailsTooLow(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Value must be between 1 and 100');

        new ValidatedUser([
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'score' => 0,
        ]);
    }

    public function testRangeValidatorFailsTooHigh(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Value must be between 1 and 100');

        new ValidatedUser([
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'score' => 101,
        ]);
    }

    public function testValidatorInArray(): void
    {
        $struct = new class (['emails' => ['john@example.com', 'jane@example.com']]) extends Struct {
            #[Field('string', validator: EmailValidator::class, isArray: true)]
            public readonly array $emails;
        };

        $this->assertCount(2, $struct->emails);
    }

    public function testValidatorFailsInArray(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid email format');

        new class (['emails' => ['john@example.com', 'invalid']]) extends Struct {
            #[Field('string', validator: EmailValidator::class, isArray: true)]
            public readonly array $emails;
        };
    }

    public function testNonExistentValidatorClass(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Validator class NonExistentValidator does not exist');

        new class (['value' => 'test']) extends Struct {
            #[Field('string', validator: 'NonExistentValidator')]
            public readonly string $value;
        };
    }

    public function testValidatorWithoutValidateMethod(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must have a static validate() method');

        new class (['value' => 'test']) extends Struct {
            #[Field('string', validator: NoValidateMethodValidator::class)]
            public readonly string $value;
        };
    }

    public function testValidatorReturnsErrorMessage(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Always fails');

        new class (['value' => 'test']) extends Struct {
            #[Field('string', validator: AlwaysFailValidator::class)]
            public readonly string $value;
        };
    }
}
