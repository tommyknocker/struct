<?php

declare(strict_types=1);

/**
 * Example 3: Complex API Structures with Nested Objects
 * 
 * This example shows how to handle complex data structures
 * typical in mobile app APIs (e.g., creating an order with items).
 */

require_once __DIR__ . '/../vendor/autoload.php';

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

// Enums for order status and payment method
enum OrderStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}

enum PaymentMethod: string
{
    case CreditCard = 'credit_card';
    case DebitCard = 'debit_card';
    case PayPal = 'paypal';
    case ApplePay = 'apple_pay';
    case GooglePay = 'google_pay';
}

// Nested structures
final class Address extends Struct
{
    #[Field('string')]
    public readonly string $street;

    #[Field('string')]
    public readonly string $city;

    #[Field('string')]
    public readonly string $state;

    #[Field('string')]
    public readonly string $zipCode;

    #[Field('string')]
    public readonly string $country;
}

final class OrderItem extends Struct
{
    #[Field('string')]
    public readonly string $productId;

    #[Field('string')]
    public readonly string $name;

    #[Field('int')]
    public readonly int $quantity;

    #[Field('float')]
    public readonly float $price;

    public function getTotal(): float
    {
        return $this->quantity * $this->price;
    }
}

final class PaymentInfo extends Struct
{
    #[Field(PaymentMethod::class)]
    public readonly PaymentMethod $method;

    #[Field('string', nullable: true)]
    public readonly ?string $cardLastFour;

    #[Field('string', nullable: true)]
    public readonly ?string $transactionId;
}

final class CreateOrderRequest extends Struct
{
    #[Field('string')]
    public readonly string $userId;

    #[Field(OrderItem::class, isArray: true)]
    public readonly array $items;

    #[Field(Address::class)]
    public readonly Address $shippingAddress;

    #[Field(Address::class, nullable: true)]
    public readonly ?Address $billingAddress;

    #[Field(PaymentInfo::class)]
    public readonly PaymentInfo $payment;

    #[Field('string', nullable: true)]
    public readonly ?string $notes;

    #[Field(OrderStatus::class, default: OrderStatus::Pending)]
    public readonly OrderStatus $status;

    public function calculateTotal(): float
    {
        return array_reduce(
            $this->items,
            fn($sum, OrderItem $item) => $sum + $item->getTotal(),
            0.0
        );
    }
}

// Simulate API endpoint
function createOrder(string $jsonInput): array
{
    try {
        $request = CreateOrderRequest::fromJson($jsonInput);

        // Calculate order total
        $total = $request->calculateTotal();

        // Create order response
        $order = [
            'orderId' => 'ORD-' . strtoupper(uniqid()),
            'userId' => $request->userId,
            'status' => $request->status->value,
            'items' => array_map(
                fn(OrderItem $item) => [
                    'productId' => $item->productId,
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->getTotal(),
                ],
                $request->items
            ),
            'shippingAddress' => $request->shippingAddress->toArray(),
            'billingAddress' => $request->billingAddress?->toArray(),
            'payment' => $request->payment->toArray(),
            'notes' => $request->notes,
            'total' => $total,
            'createdAt' => date('Y-m-d H:i:s'),
        ];

        return [
            'success' => true,
            'data' => $order,
        ];
    } catch (RuntimeException | JsonException $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}

// Example: Create order from mobile app
echo "=== Create Order Example ===\n\n";

$orderJson = json_encode([
    'userId' => 'user123',
    'items' => [
        [
            'productId' => 'prod-001',
            'name' => 'iPhone 15 Pro',
            'quantity' => 1,
            'price' => 999.99,
        ],
        [
            'productId' => 'prod-002',
            'name' => 'iPhone Case',
            'quantity' => 2,
            'price' => 29.99,
        ],
        [
            'productId' => 'prod-003',
            'name' => 'Screen Protector',
            'quantity' => 2,
            'price' => 9.99,
        ],
    ],
    'shippingAddress' => [
        'street' => '123 Main St',
        'city' => 'San Francisco',
        'state' => 'CA',
        'zipCode' => '94102',
        'country' => 'USA',
    ],
    'billingAddress' => null, // Same as shipping
    'payment' => [
        'method' => 'apple_pay',
        'cardLastFour' => '4242',
        'transactionId' => 'txn_123456789',
    ],
    'notes' => 'Please leave at the front door',
]);

$response = createOrder($orderJson);
echo json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Example 2: Invalid order (missing required fields)
echo "=== Invalid Order Example ===\n\n";

$invalidJson = json_encode([
    'userId' => 'user456',
    'items' => [], // Empty items!
    'shippingAddress' => [
        'street' => '456 Oak Ave',
        'city' => 'Los Angeles',
        'state' => 'CA',
        'zipCode' => '90001',
        'country' => 'USA',
    ],
    'payment' => [
        'method' => 'credit_card',
        'cardLastFour' => '1234',
        'transactionId' => null,
    ],
]);

$response = createOrder($invalidJson);
echo json_encode($response, JSON_PRETTY_PRINT) . "\n";

