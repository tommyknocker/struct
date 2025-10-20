<?php

declare(strict_types=1);

/**
 * Example 5: Field Mapping and Aliases
 * 
 * This example shows how to use field aliases to map snake_case API data
 * to camelCase properties, or handle third-party APIs with different naming.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

// Example 1: Handle snake_case API responses
final class UserProfile extends Struct
{
    #[Field('string', alias: 'user_id')]
    public readonly string $userId;

    #[Field('string', alias: 'first_name')]
    public readonly string $firstName;

    #[Field('string', alias: 'last_name')]
    public readonly string $lastName;

    #[Field('string', alias: 'email_address')]
    public readonly string $emailAddress;

    #[Field('string', alias: 'phone_number', nullable: true)]
    public readonly ?string $phoneNumber;

    #[Field('string', alias: 'created_at')]
    public readonly string $createdAt;

    #[Field('string', alias: 'updated_at')]
    public readonly string $updatedAt;

    public function getFullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }
}

// Example 2: Handle third-party API with weird naming
final class PaymentWebhook extends Struct
{
    #[Field('string', alias: 'TXN_ID')]
    public readonly string $transactionId;

    #[Field('float', alias: 'AMT')]
    public readonly float $amount;

    #[Field('string', alias: 'CCY')]
    public readonly string $currency;

    #[Field('string', alias: 'STATUS')]
    public readonly string $status;

    #[Field('string', alias: 'CUSTOMER_EMAIL')]
    public readonly string $customerEmail;

    #[Field('string', alias: 'MERCHANT_REF')]
    public readonly string $merchantReference;
}

// Example 3: Mix of aliases and defaults
final class ProductResponse extends Struct
{
    #[Field('string', alias: 'product_id')]
    public readonly string $productId;

    #[Field('string', alias: 'product_name')]
    public readonly string $name;

    #[Field('string', alias: 'product_description', nullable: true)]
    public readonly ?string $description;

    #[Field('float', alias: 'unit_price')]
    public readonly float $price;

    #[Field('int', alias: 'stock_quantity')]
    public readonly int $stock;

    #[Field('bool', alias: 'is_available', default: true)]
    public readonly bool $isAvailable;

    #[Field('string', alias: 'image_url', nullable: true)]
    public readonly ?string $imageUrl;

    #[Field('string', alias: 'category_name', default: 'Uncategorized')]
    public readonly string $category;
}

// Test Examples
echo "=== Example 1: Snake Case API Response ===\n";
$apiData = json_encode([
    'user_id' => 'usr_12345',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email_address' => 'john.doe@example.com',
    'phone_number' => '+1234567890',
    'created_at' => '2024-01-15T10:30:00Z',
    'updated_at' => '2024-10-20T14:22:00Z',
]);

$profile = UserProfile::fromJson($apiData);
echo "User ID: {$profile->userId}\n";
echo "Full Name: {$profile->getFullName()}\n";
echo "Email: {$profile->emailAddress}\n";
echo "Created: {$profile->createdAt}\n";
echo "\nConverted to array:\n";
echo json_encode($profile->toArray(), JSON_PRETTY_PRINT);
echo "\n\n";

echo "=== Example 2: Payment Webhook (Uppercase Keys) ===\n";
$webhookData = json_encode([
    'TXN_ID' => 'TXN_987654321',
    'AMT' => 99.99,
    'CCY' => 'USD',
    'STATUS' => 'SUCCESS',
    'CUSTOMER_EMAIL' => 'customer@example.com',
    'MERCHANT_REF' => 'ORDER-123',
]);

$webhook = PaymentWebhook::fromJson($webhookData);
echo "Transaction: {$webhook->transactionId}\n";
echo "Amount: {$webhook->amount} {$webhook->currency}\n";
echo "Status: {$webhook->status}\n";
echo "Customer: {$webhook->customerEmail}\n";
echo "\n";

echo "=== Example 3: Product with Defaults ===\n";
$productData = json_encode([
    'product_id' => 'prod_42',
    'product_name' => 'Wireless Mouse',
    'product_description' => 'Ergonomic wireless mouse with 6 buttons',
    'unit_price' => 29.99,
    'stock_quantity' => 150,
    // is_available not provided - will use default true
    'image_url' => 'https://example.com/images/mouse.jpg',
    // category_name not provided - will use default 'Uncategorized'
]);

$product = ProductResponse::fromJson($productData);
echo "Product: {$product->name}\n";
echo "Price: \${$product->price}\n";
echo "Stock: {$product->stock}\n";
echo "Available: " . ($product->isAvailable ? 'Yes' : 'No') . "\n";
echo "Category: {$product->category}\n";
echo "\n";

echo "=== Example 4: Modifying and Sending Back ===\n";
// Update stock and send to another API
$updatedProduct = $product->with(['stock' => 149]);
echo "Updated product JSON:\n";
echo $updatedProduct->toJson(pretty: true);
echo "\n";

