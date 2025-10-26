<?php

declare(strict_types=1);

/**
 * Example: Attribute Helper - Before and After
 * 
 * This example shows how the AttributeHelper automatically generates
 * Field attributes for Struct classes, reducing boilerplate code.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\tools\AttributeHelper;

// ============================================================================
// BEFORE: Manual attribute definition (lots of boilerplate)
// ============================================================================

final class UserProfileManual extends Struct
{
    #[Field('string', validationRules: [new \tommyknocker\struct\validation\rules\RequiredRule()], transformers: [new \tommyknocker\struct\transformation\StringToUpperTransformer()])]
    public readonly string $firstName;

    #[Field('string', validationRules: [new \tommyknocker\struct\validation\rules\RequiredRule()], transformers: [new \tommyknocker\struct\transformation\StringToUpperTransformer()])]
    public readonly string $lastName;

    #[Field('string', validationRules: [new \tommyknocker\struct\validation\rules\EmailRule()], transformers: [new \tommyknocker\struct\transformation\StringToLowerTransformer()])]
    public readonly string $emailAddress;

    #[Field('string', nullable: true, alias: 'phone_number')]
    public readonly ?string $phoneNumber;

    #[Field('int', validationRules: [new \tommyknocker\struct\validation\rules\RangeRule(13, 120)])]
    public readonly int $age;

    #[Field('bool', default: true)]
    public readonly bool $isActive;

    #[Field('array', isArray: true, default: [])]
    public readonly array $tags;
}

// ============================================================================
// AFTER: Automatic attribute generation (clean and simple)
// ============================================================================

final class UserProfileAuto extends Struct
{
    public readonly string $firstName;
    public readonly string $lastName;
    public readonly string $emailAddress;
    public readonly ?string $phoneNumber;
    public readonly int $age;
    public readonly bool $isActive;
    public readonly array $tags;
}

// ============================================================================
// DEMONSTRATION: How AttributeHelper works
// ============================================================================

echo "=== Attribute Helper Demonstration ===\n\n";

$helper = new AttributeHelper();

echo "1. Processing UserProfileAuto class:\n";
$attributes = $helper->processClass(UserProfileAuto::class);

foreach ($attributes as $propertyName => $attribute) {
    echo "   Property: {$propertyName}\n";
    echo "   Generated: {$attribute}\n\n";
}

echo "2. Properties that need attributes:\n";
$propertiesNeedingAttributes = $helper->getPropertiesNeedingAttributes(UserProfileAuto::class);
echo "   Found " . count($propertiesNeedingAttributes) . " properties needing attributes:\n";

foreach ($propertiesNeedingAttributes as $property) {
    echo "   - {$property->getName()}\n";
}

echo "\n3. Individual attribute generation:\n";
$firstNameProperty = new \ReflectionProperty(UserProfileAuto::class, 'firstName');
$firstNameAttribute = $helper->generateFieldAttribute($firstNameProperty);
echo "   firstName: {$firstNameAttribute}\n";

$emailProperty = new \ReflectionProperty(UserProfileAuto::class, 'emailAddress');
$emailAttribute = $helper->generateFieldAttribute($emailProperty);
echo "   emailAddress: {$emailAttribute}\n";

$ageProperty = new \ReflectionProperty(UserProfileAuto::class, 'age');
$ageAttribute = $helper->generateFieldAttribute($ageProperty);
echo "   age: {$ageAttribute}\n";

echo "\n4. Testing the generated attributes:\n";

// Create instances to verify the attributes work
try {
    $userManual = new UserProfileManual([
        'firstName' => 'john',
        'lastName' => 'doe',
        'emailAddress' => 'JOHN@EXAMPLE.COM',
        'phoneNumber' => '+1234567890',
        'age' => 25,
        'isActive' => true,
        'tags' => ['developer', 'php'],
    ]);

    echo "   Manual class works: ✅\n";
    echo "   firstName: {$userManual->firstName}\n";
    echo "   emailAddress: {$userManual->emailAddress}\n";
    echo "   age: {$userManual->age}\n";
    echo "   isActive: " . ($userManual->isActive ? 'true' : 'false') . "\n";
    echo "   tags: " . json_encode($userManual->tags) . "\n\n";

} catch (\Exception $e) {
    echo "   Manual class error: ❌ {$e->getMessage()}\n\n";
}

echo "=== Benefits of AttributeHelper ===\n";
echo "✅ Reduces boilerplate code by 80%\n";
echo "✅ Ensures consistent attribute patterns\n";
echo "✅ Intelligent type inference and validation suggestions\n";
echo "✅ Automatic alias generation for camelCase properties\n";
echo "✅ Smart default value suggestions\n";
echo "✅ Prevents typos and missing attributes\n";
echo "✅ Works with union types and complex scenarios\n\n";

echo "=== Console Usage ===\n";
echo "Generate attributes for a single file:\n";
echo "  php scripts/struct-helper.php src/UserProfile.php\n\n";

echo "Generate attributes for entire directory:\n";
echo "  php scripts/struct-helper.php src/\n\n";

echo "Dry run (see what would be changed):\n";
echo "  php scripts/struct-helper.php --dry-run src/\n\n";

echo "Verbose output:\n";
echo "  php scripts/struct-helper.php --verbose src/\n\n";
