<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use tommyknocker\struct\CollectionType;
use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\validation\rules\CountRule;
use tommyknocker\struct\validation\rules\RangeRule;

final class PartnerOrderLine extends Struct
{
    #[Field]
    public readonly string $sku;

    #[Field(validationRules: [new RangeRule(1, 100)])]
    public readonly int $quantity;

    #[Field(alias: 'unit_price_cents', validationRules: [new RangeRule(0, 1000000)])]
    public readonly int $unitPriceCents;
}

final class PartnerOrder extends Struct
{
    #[Field(alias: 'order_id')]
    public readonly string $id;

    #[Field(alias: 'customer_name')]
    public readonly string $customerName;

    /** @var list<PartnerOrderLine> */
    #[Field(PartnerOrderLine::class, collection: CollectionType::List, validationRules: [new CountRule(min: 1)])]
    public readonly array $lines;
}

final class FulfillmentLine extends Struct
{
    #[Field]
    public readonly string $sku;

    #[Field]
    public readonly int $quantity;
}

final class FulfillmentJob extends Struct
{
    #[Field(outputAlias: 'reference')]
    public readonly string $orderId;

    #[Field]
    public readonly string $recipient;

    /** @var list<FulfillmentLine> */
    #[Field(FulfillmentLine::class, collection: CollectionType::List)]
    public readonly array $items;

    #[Field(outputAlias: 'total_cents')]
    public readonly int $totalCents;
}

// 1. Accept a partner's order. Unrelated upstream fields may evolve independently.
$order = PartnerOrder::fromJson('{
    "order_id":"p-42", "customer_name":"Alice", "partner_note":"internal",
    "lines":[
        {"sku":"BOOK","quantity":2,"unit_price_cents":1500},
        {"sku":"PEN","quantity":1,"unit_price_cents":200}
    ]
}');

// 2. Application logic prepares a different contract. Money stays in integer cents.
$items = [];
$total = 0;
foreach ($order->lines as $line) {
    $total += $line->quantity * $line->unitPriceCents;
    $items[] = ['sku' => $line->sku, 'quantity' => $line->quantity];
}
$job = new FulfillmentJob([
    'orderId' => $order->id, 'recipient' => $order->customerName,
    'items' => $items, 'totalCents' => $total,
]);

// 3. Produce exactly what the next system expects; no partner-only fields leak.
$expected = [
    'reference' => 'p-42', 'recipient' => 'Alice',
    'items' => [['sku' => 'BOOK', 'quantity' => 2], ['sku' => 'PEN', 'quantity' => 1]],
    'total_cents' => 3200,
];
if ($job->toArray() !== $expected) {
    throw new RuntimeException('Fulfillment payload does not match its contract');
}
echo $job->toJson(pretty: true) . "\n";
