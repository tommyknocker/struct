# Struct

[![CI](https://github.com/tommyknocker/struct/workflows/CI/badge.svg)](https://github.com/tommyknocker/struct/actions)
[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg)](https://phpstan.org/)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://www.php.net/)

**External data → validated PHP objects → explicit outbound payloads.**

Struct maps arrays and JSON into DTOs and prepares those DTOs for another API,
webhook, or queue. Define exchanged fields, validate incoming values, and control
outgoing names. Framework-independent, with no external runtime dependencies.

## Installation

```bash
composer require tommyknocker/struct
```

Requires PHP 8.1+.

## Receive, work with, send

```php
use tommyknocker\struct\CollectionType;
use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\validation\rules\CountRule;
use tommyknocker\struct\validation\rules\RangeRule;

enum OrderStatus: string
{
    case Paid = 'paid';
}

final class OrderItem extends Struct
{
    #[Field]
    public readonly string $sku;

    #[Field(validationRules: [new RangeRule(1, 100)])]
    public readonly int $quantity;
}

final class Order extends Struct
{
    #[Field(alias: 'order_id', outputAlias: 'id')]
    public readonly string $orderId;

    #[Field]
    public readonly OrderStatus $status;

    #[Field]
    public readonly DateTimeImmutable $createdAt;

    #[Field(OrderItem::class, collection: CollectionType::List, validationRules: [new CountRule(min: 1)])]
    public readonly array $items;
}

$order = Order::fromJson('{
    "order_id": "ord-42",
    "status": "paid",
    "createdAt": "2026-10-25T12:00:00+03:00",
    "items": [{"sku": "A", "quantity": 2}]
}');

$order->items[0]->quantity; // int: 2
$order->status;            // OrderStatus::Paid

$payload = $order->toArray();
// [
//     'id' => 'ord-42',
//     'status' => 'paid',
//     'createdAt' => '2026-10-25T12:00:00.000000+03:00',
//     'items' => [['sku' => 'A', 'quantity' => 2]],
// ]

$json = $order->toJson(pretty: true);
```

Use the same DTO in both directions for the same contract. Use separate DTOs when
fields or validation differ: an input DTO containing a password should not be
reused as a public response. See [executable scenarios](examples/README.md),
including a [partner order → fulfillment integration](examples/10_partner_to_fulfillment.php).

## Documentation

Read the [documentation index](docs/README.md), or go directly to:

- [Fields, types, required/null/default semantics](docs/fields.md)
- [Lists, maps, and shape preservation](docs/collections.md)
- [Validation and all built-in rules](docs/validation.md)
- [Transformations and copies](docs/transformations.md)
- [Arrays, JSON, and outbound contracts](docs/serialization.md)
- [Error handling](docs/errors.md)
- [API reference](docs/reference.md)
- [PHPStan, scaffolding, and development checks](docs/tooling.md)

The [examples](examples/README.md) are executable integration scenarios. Start
with [receiving a request](examples/01_receive_api_request.php),
[partner-to-fulfillment mapping](examples/10_partner_to_fulfillment.php), or
[webhook subscription validation](examples/11_webhook_subscription.php).

## Development

```bash
composer install
composer check
```

Tests target PHP 8.1–8.5; PHPStan runs at level 9. See
[development checks](docs/tooling.md#checks) and [CHANGELOG.md](CHANGELOG.md).

MIT license. Author: Vasiliy Krivoplyas.
