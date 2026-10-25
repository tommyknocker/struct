<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
use tommyknocker\struct\CollectionType;
use tommyknocker\struct\exception\ValidationException;
use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\tests\fixtures\AllowedWorkflowState;
use tommyknocker\struct\validation\rules\OneOfRule;
use tommyknocker\struct\validation\rules\PatternRule;
use tommyknocker\struct\validation\rules\UrlRule;
use tommyknocker\struct\validation\rules\UuidRule;

final class RuleCheckedEndpoint extends Struct
{
    #[Field(validationRules: [new UuidRule(4)])]
    public readonly string $id;

    #[Field(validationRules: [new PatternRule('/\A[a-z][a-z0-9_-]+\z/')])]
    public readonly string $name;

    #[Field(validationRules: [new UrlRule(['https'])])]
    public readonly string $url;

    #[Field('string', collection: CollectionType::List, itemRules: [new OneOfRule(['created', 'paid'])])]
    public readonly array $events;
}

final class EndpointBatch extends Struct
{
    #[Field(RuleCheckedEndpoint::class, collection: CollectionType::List)]
    public readonly array $endpoints;
}

final class NewRuleIntegrationTest extends TestCase
{
    /** @return array<string, mixed> */
    private function validData(): array
    {
        return ['id' => '7185bbe3-cdd7-4154-88c3-63416a76327a', 'name' => 'orders_eu',
            'url' => 'https://partner.example.test/hooks', 'events' => ['created', 'paid']];
    }

    public function testNewRulesParticipateInJsonMappingAndNestedItemErrors(): void
    {
        $data = $this->validData();
        $dto = RuleCheckedEndpoint::fromJson(json_encode($data, JSON_THROW_ON_ERROR));
        $this->assertSame($data, $dto->toArray());
        $data['events'] = ['paid', 'unknown'];

        try {
            new EndpointBatch(['endpoints' => [$data]]);
            $this->fail('Expected indexed validation error');
        } catch (ValidationException $error) {
            $this->assertSame('endpoints[0].events[1]', $error->fieldName);
            $this->assertSame('unknown', $error->value);
        }
    }

    public function testAllFourRulesRunOnCopiesWithoutMutatingTheSource(): void
    {
        $source = new RuleCheckedEndpoint($this->validData());
        foreach ([['id' => 'bad'], ['name' => ' bad '], ['url' => 'http://example.test'], ['events' => ['bad']]] as $changes) {
            try {
                $source->with($changes);
                $this->fail('Expected invalid replacement');
            } catch (ValidationException $error) {
                $this->assertNotSame('', $error->fieldName);
            }
        }
        $this->assertSame($this->validData(), $source->toArray());
    }

    public function testOneOfSeesMappedEnumCasesRatherThanIncomingStrings(): void
    {
        $dto = new class (['state' => 'ready']) extends Struct {
            #[Field(validationRules: [new OneOfRule([AllowedWorkflowState::Ready])])]
            public readonly AllowedWorkflowState $state;
        };
        $this->assertSame(AllowedWorkflowState::Ready, $dto->state);
        $this->expectException(ValidationException::class);
        $dto->with(['state' => 'failed']);
    }

    public function testDefaultValuesUseTheSameNewRules(): void
    {
        $this->expectException(ValidationException::class);
        new class ([]) extends Struct {
            #[Field(default: 'http://partner.example.test', validationRules: [new UrlRule(['https'])])]
            public readonly string $url;
        };
    }
}
