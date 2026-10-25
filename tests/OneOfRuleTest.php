<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use tommyknocker\struct\tests\fixtures\AllowedWorkflowState;
use tommyknocker\struct\validation\rules\OneOfRule;

final class OneOfRuleTest extends TestCase
{
    public function testMembershipIsStrictAndCaseSensitive(): void
    {
        $rule = new OneOfRule([1, 'ready', null]);
        foreach ([1, 'ready', null] as $value) {
            $this->assertTrue($rule->validate($value)->isValid());
        }
        foreach (['1', 1.0, true, false, 'READY', '', []] as $value) {
            $this->assertFalse($rule->validate($value)->isValid());
        }
    }

    public function testEnumCasesAreComparedAfterMappingWithoutUnwrapping(): void
    {
        $rule = new OneOfRule([AllowedWorkflowState::Ready]);
        $this->assertTrue($rule->validate(AllowedWorkflowState::Ready)->isValid());
        $this->assertFalse($rule->validate(AllowedWorkflowState::Failed)->isValid());
        $this->assertFalse($rule->validate('ready')->isValid());
    }

    public function testConfigurationKeysDoNotBecomeAllowedValues(): void
    {
        $rule = new OneOfRule(['label' => 'ready']);
        $this->assertTrue($rule->validate('ready')->isValid());
        $this->assertFalse($rule->validate('label')->isValid());
    }

    public function testEmptyAllowedSetIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new OneOfRule([]);
    }

    public function testNonScalarNonEnumConfigurationIsRejected(): void
    {
        foreach ([[[]], [new \stdClass()], [NAN], [INF]] as $values) {
            try {
                new OneOfRule($values);
                $this->fail('Expected invalid allowed-value configuration');
            } catch (InvalidArgumentException $error) {
                $this->assertStringContainsString('finite scalars', $error->getMessage());
            }
        }
    }
}
