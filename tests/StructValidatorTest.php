<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\tests\fixtures\AlwaysFailValidator;
use tommyknocker\struct\tests\fixtures\EmailValidator;
use tommyknocker\struct\tests\fixtures\NoValidateMethodValidator;
use tommyknocker\struct\tests\fixtures\ValidatedUser;

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
        $this->expectException(\tommyknocker\struct\exception\ValidationException::class);
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
