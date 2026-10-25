<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use tommyknocker\struct\exception\ValidationException;
use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\validation\rules\CountRule;
use tommyknocker\struct\validation\rules\EmailRule;
use tommyknocker\struct\validation\rules\NullableRule;

final class MailingList extends Struct
{
    #[Field('string', collection: \tommyknocker\struct\CollectionType::Preserve, validationRules: [new CountRule(1, 2)], itemRules: [new EmailRule()])]
    public readonly array $emails;
}

final class CollectionRulesTest extends TestCase
{
    public function testEmptyCollectionRunsWholeFieldRules(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Array size');
        new MailingList(['emails' => []]);
    }

    public function testItemErrorsKeepTheirKey(): void
    {
        try {
            new MailingList(['emails' => ['main' => 'alice@example.com', 'other' => 'bad']]);
            $this->fail('Expected invalid email');
        } catch (ValidationException $e) {
            $this->assertSame('emails[other]', $e->fieldName);
        }
    }

    public function testWholeCollectionRulesRunOnCopies(): void
    {
        $source = new MailingList(['emails' => ['alice@example.com']]);
        $this->expectException(ValidationException::class);
        $source->with(['emails' => []]);
    }

    public function testNullableWrapperOnlySkipsNull(): void
    {
        $rule = new NullableRule(new EmailRule());
        $this->assertTrue($rule->validate(null)->isValid());
        $this->assertTrue($rule->validate('alice@example.com')->isValid());
        $this->assertFalse($rule->validate('')->isValid());
        $this->assertFalse($rule->validate('bad')->isValid());
        $this->assertFalse($rule->validate(42)->isValid());
    }

    public function testNullableDefaultsUseWrappedRules(): void
    {
        $dto = new class ([]) extends Struct {
            #[Field(default: null, validationRules: [new NullableRule(new EmailRule())])]
            public readonly ?string $replyTo;
        };
        $this->assertNull($dto->replyTo);
        $this->expectException(ValidationException::class);
        $dto->with(['replyTo' => 'bad']);
    }

    public function testCountRuleBoundsAndWrongTypes(): void
    {
        $rule = new CountRule(1, 2);
        $this->assertTrue($rule->validate(['a'])->isValid());
        $this->assertTrue($rule->validate(['a', 'b'])->isValid());
        $this->assertFalse($rule->validate([])->isValid());
        $this->assertFalse($rule->validate(['a', 'b', 'c'])->isValid());
        $this->assertFalse($rule->validate('a')->isValid());
        $this->assertTrue((new CountRule())->validate([])->isValid());
        $this->expectException(InvalidArgumentException::class);
        new CountRule(2, 1);
    }

    public function testItemRulesNeedAnExplicitCollection(): void
    {
        $this->expectException(LogicException::class);
        new class (['email' => 'alice@example.com']) extends Struct {
            #[Field(itemRules: [new EmailRule()])]
            public readonly string $email;
        };
    }
}
