<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use tommyknocker\struct\CollectionType;
use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\validation\rules\CountRule;
use tommyknocker\struct\validation\rules\RangeRule;

final class ShippingAddress extends Struct
{
    #[Field]
    public readonly string $city;

    #[Field]
    public readonly string $street;
}

final class PurchaseLine extends Struct
{
    #[Field]
    public readonly string $sku;

    #[Field(validationRules: [new RangeRule(1, 100)])]
    public readonly int $quantity;
}

final class PurchaseOrder extends Struct
{
    #[Field]
    public readonly ShippingAddress $shipping;

    // Both JSON lists and keyed JSON objects can be typed collections.
    /** @var array<string, PurchaseLine> */
    #[Field(PurchaseLine::class, collection: CollectionType::Map, validationRules: [new CountRule(min: 1)])]
    public readonly array $lines;
}

$order = PurchaseOrder::fromJson('{
    "shipping": {"city":"Berlin","street":"Main Street 1"},
    "lines": {
        "main": {"sku":"BOOK","quantity":2},
        "extra": {"sku":"PEN","quantity":1}
    }
}');

if ($order->lines['main']->quantity !== 2
    || array_keys($order->lines) !== ['main', 'extra']) {
    throw new RuntimeException('Nested mapping or map keys were lost');
}

echo $order->shipping->city . ': ' . $order->lines['main']->sku . "\n";
echo $order->toJson(pretty: true) . "\n";
