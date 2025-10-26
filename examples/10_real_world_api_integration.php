<?php

declare(strict_types=1);

/**
 * Example: Real-World API Integration with AttributeHelper
 * 
 * This example demonstrates how AttributeHelper can be used in a real-world
 * scenario to quickly generate Struct classes for API integration.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\tools\AttributeHelper;

// ============================================================================
// SCENARIO: E-commerce API Integration
// ============================================================================

// Before: Manual definition (time-consuming and error-prone)
final class ProductApiResponseManual extends Struct
{
    #[Field('string', alias: 'product_id')]
    public readonly string $productId;

    #[Field('string', validationRules: [new \tommyknocker\struct\validation\rules\RequiredRule()], transformers: [new \tommyknocker\struct\transformation\StringToUpperTransformer()])]
    public readonly string $productName;

    #[Field('float', validationRules: [new \tommyknocker\struct\validation\rules\RangeRule(0, 999999)])]
    public readonly float $price;

    #[Field('string', nullable: true)]
    public readonly ?string $description;

    #[Field('array', isArray: true, default: [])]
    public readonly array $categories;

    #[Field('bool', default: true)]
    public readonly bool $isAvailable;

    #[Field('string', alias: 'created_at')]
    public readonly string $createdAt;

    #[Field('string', alias: 'updated_at')]
    public readonly string $updatedAt;
}

// After: Clean class definition (AttributeHelper will generate attributes)
final class ProductApiResponseAuto extends Struct
{
    public readonly string $productId;
    public readonly string $productName;
    public readonly float $price;
    public readonly ?string $description;
    public readonly array $categories;
    public readonly bool $isAvailable;
    public readonly string $createdAt;
    public readonly string $updatedAt;
}

// ============================================================================
// SCENARIO: User Registration API
// ============================================================================

final class UserRegistrationRequest extends Struct
{
    public readonly string $username;
    public readonly string $emailAddress;
    public readonly string $password;
    public readonly string $firstName;
    public readonly string $lastName;
    public readonly int $age;
    public readonly ?string $phoneNumber;
    public readonly bool $acceptTerms;
    public readonly array $preferences;
}

// ============================================================================
// SCENARIO: Payment Processing
// ============================================================================

final class PaymentRequest extends Struct
{
    public readonly string $transactionId;
    public readonly float $amount;
    public readonly string $currency;
    public readonly string $customerEmail;
    public readonly string $merchantReference;
    public readonly bool $isTestMode;
    public readonly array $metadata;
}

// ============================================================================
// DEMONSTRATION: Batch Processing Multiple Classes
// ============================================================================

echo "=== Real-World API Integration Demo ===\n\n";

$helper = new AttributeHelper();

$classes = [
    'ProductApiResponseAuto' => ProductApiResponseAuto::class,
    'UserRegistrationRequest' => UserRegistrationRequest::class,
    'PaymentRequest' => PaymentRequest::class,
];

foreach ($classes as $className => $class) {
    echo "Processing {$className}:\n";
    echo str_repeat('-', 50) . "\n";
    
    $attributes = $helper->processClass($class);
    
    foreach ($attributes as $propertyName => $attribute) {
        echo "{$propertyName}: {$attribute}\n";
    }
    
    echo "\n";
}

// ============================================================================
// DEMONSTRATION: Testing Generated Attributes
// ============================================================================

echo "=== Testing Generated Attributes ===\n\n";

echo "Note: The classes above show what attributes WOULD be generated.\n";
echo "To actually use these attributes, run the console script:\n\n";

echo "1. Generate attributes for ProductApiResponseAuto:\n";
echo "   php scripts/struct-helper.php ProductApiResponseAuto.php\n\n";

echo "2. Generate attributes for UserRegistrationRequest:\n";
echo "   php scripts/struct-helper.php UserRegistrationRequest.php\n\n";

echo "3. Generate attributes for PaymentRequest:\n";
echo "   php scripts/struct-helper.php PaymentRequest.php\n\n";

echo "After running the console script, the classes will have the generated attributes\n";
echo "and can be used normally with full validation and transformation support.\n\n";

echo "=== Console Commands for Real-World Usage ===\n\n";

echo "1. Generate attributes for API models:\n";
echo "   php scripts/struct-helper.php src/Api/Models/\n\n";

echo "2. Generate attributes for specific API endpoint:\n";
echo "   php scripts/struct-helper.php src/Api/Controllers/UserController.php\n\n";

echo "3. Dry run to see what would be changed:\n";
echo "   php scripts/struct-helper.php --dry-run src/Api/\n\n";

echo "4. Verbose output for debugging:\n";
echo "   php scripts/struct-helper.php --verbose src/Api/Models/\n\n";

echo "5. Process entire project:\n";
echo "   php scripts/struct-helper.php src/\n\n";

echo "=== Benefits in Real-World Scenarios ===\n";
echo "✅ Rapid API integration development\n";
echo "✅ Consistent data validation across all endpoints\n";
echo "✅ Automatic field mapping for external APIs\n";
echo "✅ Reduced development time by 60-80%\n";
echo "✅ Fewer bugs due to automated attribute generation\n";
echo "✅ Easy maintenance and updates\n";
echo "✅ Works with complex nested structures\n\n";
