<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use tommyknocker\struct\CollectionType;
use tommyknocker\struct\exception\ValidationException;
use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\validation\rules\CountRule;
use tommyknocker\struct\validation\rules\OneOfRule;
use tommyknocker\struct\validation\rules\PatternRule;
use tommyknocker\struct\validation\rules\UrlRule;
use tommyknocker\struct\validation\rules\UuidRule;

// Register a subscription with an external ID, constrained integration name,
// HTTPS callback, and a nonempty list of supported events.
final class WebhookSubscription extends Struct
{
    protected const STRICT = true;

    #[Field(validationRules: [new UuidRule(version: 4)])]
    public readonly string $id;

    #[Field(validationRules: [new PatternRule('/\A[a-z][a-z0-9_-]{2,31}\z/')])]
    public readonly string $name;

    #[Field(validationRules: [new UrlRule(schemes: ['https'])])]
    public readonly string $callbackUrl;

    /** @var list<string> */
    #[Field(
        'string',
        collection: CollectionType::List,
        validationRules: [new CountRule(min: 1, max: 4)],
        itemRules: [new OneOfRule(['order.created', 'order.paid', 'order.cancelled'])]
    )]
    public readonly array $events;
}

$subscription = WebhookSubscription::fromJson('{
    "id":"7185bbe3-cdd7-4154-88c3-63416a76327a",
    "name":"orders_eu", "callbackUrl":"https://partner.example.test/hooks/orders",
    "events":["order.created","order.paid"]
}');
$expected = [
    'id' => '7185bbe3-cdd7-4154-88c3-63416a76327a',
    'name' => 'orders_eu',
    'callbackUrl' => 'https://partner.example.test/hooks/orders',
    'events' => ['order.created', 'order.paid'],
];
if ($subscription->toArray() !== $expected) {
    throw new RuntimeException('Subscription does not match its expected contract');
}
echo $subscription->toJson(pretty: true) . "\n";

// The same constraints apply when preparing replacement data with with().
foreach ([
    ['changes' => ['id' => 'not-a-uuid'], 'field' => 'id'],
    ['changes' => ['name' => ' bad name '], 'field' => 'name'],
    ['changes' => ['callbackUrl' => 'http://partner.example.test/hooks/orders'], 'field' => 'callbackUrl'],
    ['changes' => ['events' => ['order.paid', 'unknown.event']], 'field' => 'events[1]'],
] as $case) {
    try {
        $subscription->with($case['changes']);

        throw new RuntimeException('Invalid replacement should have been rejected');
    } catch (ValidationException $error) {
        if ($error->fieldName !== $case['field']) {
            throw $error;
        }
        echo $error->fieldName . ': ' . $error->getMessage() . "\n";
    }
}
echo 'Original endpoint: ' . $subscription->callbackUrl . "\n";
