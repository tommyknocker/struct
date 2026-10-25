<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\tests\fixtures\ValidatedUser;
use tommyknocker\struct\validation\rules\EmailRule;

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
            #[Field('string', itemRules: [new EmailRule()], collection: \tommyknocker\struct\CollectionType::Preserve)]
            public readonly array $emails;
        };

        $this->assertCount(2, $struct->emails);
    }

    public function testValidatorFailsInArray(): void
    {
        $this->expectException(\tommyknocker\struct\exception\ValidationException::class);
        $this->expectExceptionMessage('Invalid email format');

        new class (['emails' => ['john@example.com', 'invalid']]) extends Struct {
            #[Field('string', itemRules: [new EmailRule()], collection: \tommyknocker\struct\CollectionType::Preserve)]
            public readonly array $emails;
        };
    }

}
