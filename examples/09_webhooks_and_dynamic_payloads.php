<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

enum WebhookEvent: string
{
    case Created = 'created';
    case Updated = 'updated';
}

// A typed envelope with a dynamic body: useful for forwarding partner webhooks.
final class WebhookEnvelope extends Struct
{
    // Preserve integer IDs as integers and string IDs (including leading zeros) as strings.
    #[Field]
    public readonly string|int $id;

    #[Field]
    public readonly WebhookEvent $event;

    #[Field(alias: 'occurred_at', outputAlias: 'occurred_at')]
    public readonly DateTimeImmutable $occurredAt;

    #[Field]
    public readonly mixed $payload;
}

$webhook = WebhookEnvelope::fromJson('{
    "id":"0042", "event":"updated", "occurred_at":"2026-10-25T12:00:00.123456+03:00",
    "payload":{"changes":[],"metadata":{},"externalId":9223372036854775808}
}');
if ($webhook->id !== '0042' || $webhook->event->value !== 'updated'
    || !$webhook->payload instanceof stdClass
    || !$webhook->payload->metadata instanceof stdClass || $webhook->payload->changes !== []
    || $webhook->payload->externalId !== '9223372036854775808') {
    throw new RuntimeException('Typed envelope or dynamic JSON structure was changed');
}

// Typed dates/enums become wire values; {} and [] inside mixed stay distinct in JSON.
$forwarded = $webhook->toJson();
$restored = WebhookEnvelope::fromJson($forwarded);
if ($restored->toJson() !== $forwarded || $restored->id !== '0042') {
    throw new RuntimeException('Webhook could not be forwarded and restored');
}
echo $forwarded . "\n";
