<?php

declare(strict_types=1);

/**
 * Example 6: Union Types and Data Transformations
 * 
 * This example shows how to use union types for flexible field types
 * and data transformations for automatic data processing.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\transformation\StringToUpperTransformer;
use tommyknocker\struct\transformation\StringToLowerTransformer;
use tommyknocker\struct\validation\rules\EmailRule;
use tommyknocker\struct\validation\rules\RangeRule;
use tommyknocker\struct\exception\ValidationException;

// Custom transformer for phone number formatting
class PhoneFormatterTransformer implements \tommyknocker\struct\transformation\TransformerInterface
{
    public function transform(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        // Remove all non-digit characters
        $digits = preg_replace('/[^0-9]/', '', $value);
        
        // Format as (XXX) XXX-XXXX for US numbers
        if (strlen($digits) === 10) {
            return sprintf('(%s) %s-%s', 
                substr($digits, 0, 3),
                substr($digits, 3, 3),
                substr($digits, 6, 4)
            );
        }
        
        return $value;
    }
}

// Custom transformer for name formatting
class NameFormatterTransformer implements \tommyknocker\struct\transformation\TransformerInterface
{
    public function transform(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        // Convert to title case
        return ucwords(strtolower($value));
    }
}

// Example 1: Union Types for Flexible Data
final class FlexibleDataStruct extends Struct
{
    // Can accept either string or int
    #[Field(['string', 'int'])]
    public readonly string|int $id;

    // Can accept string, int, or float
    #[Field(['string', 'int', 'float'])]
    public readonly string|int|float $value;

    // Can accept string or null
    #[Field(['string', 'null'], nullable: true)]
    public readonly string|null $description;
}

// Example 2: Data Transformations
final class ProcessedUserData extends Struct
{
    #[Field('string', transformers: [new NameFormatterTransformer()])]
    public readonly string $firstName;

    #[Field('string', transformers: [new NameFormatterTransformer()])]
    public readonly string $lastName;

    #[Field('string', transformers: [new StringToLowerTransformer()])]
    public readonly string $email;

    #[Field('string', transformers: [new PhoneFormatterTransformer()])]
    public readonly string $phone;

    #[Field('string', transformers: [
        new StringToLowerTransformer(),
        new StringToUpperTransformer()
    ])]
    public readonly string $status;
}

// Example 3: Combined Union Types and Transformations
final class FlexibleProductData extends Struct
{
    // Union type with transformation
    #[Field(['string', 'int'], transformers: [new StringToUpperTransformer()])]
    public readonly string|int $productCode;

    #[Field('string', transformers: [new NameFormatterTransformer()])]
    public readonly string $name;

    // Union type for price (can be string or float)
    #[Field(['string', 'float'])]
    public readonly string|float $price;

    // Union type for availability
    #[Field(['string', 'bool'])]
    public readonly string|bool $available;
}

// Test Union Types
echo "=== Example 1: Union Types ===\n";

// Test with string ID
$data1 = new FlexibleDataStruct([
    'id' => 'user_123',
    'value' => '42.5',
    'description' => 'Test description'
]);
echo "String ID: {$data1->id} (type: " . gettype($data1->id) . ")\n";
echo "String Value: {$data1->value} (type: " . gettype($data1->value) . ")\n\n";

// Test with int ID
$data2 = new FlexibleDataStruct([
    'id' => 456,
    'value' => 99,
    'description' => null
]);
echo "Int ID: {$data2->id} (type: " . gettype($data2->id) . ")\n";
echo "Int Value: {$data2->value} (type: " . gettype($data2->value) . ")\n";
echo "Null Description: " . ($data2->description ?? 'null') . "\n\n";

// Test validation failure
echo "=== Union Type Validation Failure ===\n";
try {
    new FlexibleDataStruct([
        'id' => 3.14, // Float not allowed!
        'value' => 'test',
        'description' => 'test'
    ]);
} catch (ValidationException $e) {
    echo "❌ Validation failed: {$e->getMessage()}\n";
    echo "Field: {$e->fieldName}\n\n";
}

// Test Data Transformations
echo "=== Example 2: Data Transformations ===\n";

$userData = new ProcessedUserData([
    'firstName' => 'john',
    'lastName' => 'DOE',
    'email' => 'JOHN.DOE@EXAMPLE.COM',
    'phone' => '1234567890',
    'status' => 'active'
]);

echo "Original: john -> Processed: {$userData->firstName}\n";
echo "Original: DOE -> Processed: {$userData->lastName}\n";
echo "Original: JOHN.DOE@EXAMPLE.COM -> Processed: {$userData->email}\n";
echo "Original: 1234567890 -> Processed: {$userData->phone}\n";
echo "Original: active -> Processed: {$userData->status}\n\n";

// Test Combined Features
echo "=== Example 3: Combined Union Types and Transformations ===\n";

$product1 = new FlexibleProductData([
    'productCode' => 'abc123',
    'name' => 'wireless mouse',
    'price' => '29.99',
    'available' => 'yes'
]);

echo "Product Code: {$product1->productCode} (type: " . gettype($product1->productCode) . ")\n";
echo "Name: {$product1->name}\n";
echo "Price: {$product1->price} (type: " . gettype($product1->price) . ")\n";
echo "Available: " . ($product1->available ? 'true' : 'false') . " (type: " . gettype($product1->available) . ")\n\n";

$product2 = new FlexibleProductData([
    'productCode' => 999,
    'name' => 'GAMING KEYBOARD',
    'price' => 79.99,
    'available' => true
]);

echo "Product Code: {$product2->productCode} (type: " . gettype($product2->productCode) . ")\n";
echo "Name: {$product2->name}\n";
echo "Price: {$product2->price} (type: " . gettype($product2->price) . ")\n";
echo "Available: " . ($product2->available ? 'true' : 'false') . " (type: " . gettype($product2->available) . ")\n\n";

// Test JSON serialization with transformations
echo "=== JSON Serialization with Transformations ===\n";
echo "User Data JSON:\n";
echo $userData->toJson(pretty: true) . "\n\n";

echo "Product Data JSON:\n";
echo $product1->toJson(pretty: true) . "\n\n";

// Test ArrayAccess with union types
echo "=== ArrayAccess with Union Types ===\n";
echo "User email via array access: {$userData['email']}\n";
echo "Product code via array access: {$product1['productCode']}\n";
echo "Flexible ID via array access: {$data1['id']}\n\n";

// Test cloning with modifications
echo "=== Cloning with Modifications ===\n";
$updatedUser = $userData->with(['firstName' => 'jane']);
echo "Original first name: {$userData->firstName}\n";
echo "Updated first name: {$updatedUser->firstName}\n";
echo "Updated last name: {$updatedUser->lastName}\n\n";

echo "=== Summary ===\n";
echo "✅ Union types allow flexible field types\n";
echo "✅ Transformations automatically process data\n";
echo "✅ Multiple transformations can be chained\n";
echo "✅ All features work together seamlessly\n";
echo "✅ JSON serialization preserves processed data\n";
echo "✅ ArrayAccess works with union types\n";
echo "✅ Cloning preserves transformations\n";
