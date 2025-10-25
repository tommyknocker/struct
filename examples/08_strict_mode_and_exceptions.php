<?php

declare(strict_types=1);

/**
 * Example 8: Strict Mode and Specialized Exceptions
 * 
 * This example shows how to use strict mode for validating no extra fields
 * and how to handle specialized exceptions for better error reporting.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\exception\ValidationException;
use tommyknocker\struct\exception\FieldNotFoundException;
use tommyknocker\struct\exception\StructException;
use tommyknocker\struct\validation\rules\EmailRule;
use tommyknocker\struct\validation\rules\RangeRule;
use tommyknocker\struct\validation\rules\RequiredRule;

// Example 1: Strict Mode for API Security
final class StrictApiRequest extends Struct
{
    #[Field('string')]
    public readonly string $username;

    #[Field('string', validationRules: [new EmailRule()])]
    public readonly string $email;

    #[Field('int', validationRules: [new RangeRule(18, 120)])]
    public readonly int $age;
}

// Example 2: Non-strict mode for flexible data
final class FlexibleApiRequest extends Struct
{
    #[Field('string')]
    public readonly string $username;

    #[Field('string', validationRules: [new EmailRule()])]
    public readonly string $email;

    #[Field('int', validationRules: [new RangeRule(18, 120)])]
    public readonly int $age;
}

// Example 3: Complex structure with strict validation
final class UserProfile extends Struct
{
    #[Field('string', validationRules: [new RequiredRule()])]
    public readonly string $id;

    #[Field('string', validationRules: [new RequiredRule()])]
    public readonly string $username;

    #[Field('string', validationRules: [new EmailRule()])]
    public readonly string $email;

    #[Field('string', nullable: true)]
    public readonly ?string $firstName;

    #[Field('string', nullable: true)]
    public readonly ?string $lastName;

    #[Field('int', nullable: true, validationRules: [new RangeRule(13, 120)])]
    public readonly ?int $age;

    #[Field('string', nullable: true)]
    public readonly ?string $phone;

    #[Field('string', nullable: true)]
    public readonly ?string $bio;
}

// API Handler with comprehensive error handling
class ApiHandler
{
    public function handleStrictRequest(array $data): array
    {
        try {
            // Enable strict mode for this request
            Struct::$strictMode = true;
            
            $request = new StrictApiRequest($data);
            
            return [
                'success' => true,
                'data' => $request->toArray(),
                'message' => 'Request processed successfully'
            ];
        } catch (FieldNotFoundException $e) {
            return [
                'success' => false,
                'error' => 'Missing required field',
                'field' => $e->fieldName,
                'message' => $e->getMessage(),
                'status_code' => 400
            ];
        } catch (ValidationException $e) {
            return [
                'success' => false,
                'error' => 'Validation failed',
                'field' => $e->fieldName,
                'value' => $e->value,
                'message' => $e->getMessage(),
                'status_code' => 422
            ];
        } catch (StructException $e) {
            return [
                'success' => false,
                'error' => 'Struct error',
                'message' => $e->getMessage(),
                'status_code' => 400
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Unexpected error',
                'message' => $e->getMessage(),
                'status_code' => 500
            ];
        } finally {
            // Always disable strict mode after processing
            Struct::$strictMode = false;
        }
    }

    public function handleFlexibleRequest(array $data): array
    {
        try {
            // Ensure strict mode is disabled
            Struct::$strictMode = false;
            
            $request = new FlexibleApiRequest($data);
            
            return [
                'success' => true,
                'data' => $request->toArray(),
                'message' => 'Request processed successfully'
            ];
        } catch (FieldNotFoundException $e) {
            return [
                'success' => false,
                'error' => 'Missing required field',
                'field' => $e->fieldName,
                'message' => $e->getMessage(),
                'status_code' => 400
            ];
        } catch (ValidationException $e) {
            return [
                'success' => false,
                'error' => 'Validation failed',
                'field' => $e->fieldName,
                'value' => $e->value,
                'message' => $e->getMessage(),
                'status_code' => 422
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Unexpected error',
                'message' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }

    public function handleUserProfile(array $data): array
    {
        try {
            $profile = new UserProfile($data);
            
            return [
                'success' => true,
                'data' => $profile->toArray(),
                'message' => 'Profile processed successfully'
            ];
        } catch (FieldNotFoundException $e) {
            return [
                'success' => false,
                'error' => 'Missing required field',
                'field' => $e->fieldName,
                'message' => $e->getMessage(),
                'status_code' => 400
            ];
        } catch (ValidationException $e) {
            return [
                'success' => false,
                'error' => 'Validation failed',
                'field' => $e->fieldName,
                'value' => $e->value,
                'message' => $e->getMessage(),
                'status_code' => 422
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Unexpected error',
                'message' => $e->getMessage(),
                'status_code' => 500
            ];
        }
    }
}

// Test scenarios
$handler = new ApiHandler();

echo "=== Example 1: Strict Mode - Valid Request ===\n";
$validStrictData = [
    'username' => 'john_doe',
    'email' => 'john@example.com',
    'age' => 25
];

$result = $handler->handleStrictRequest($validStrictData);
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

echo "=== Example 2: Strict Mode - Extra Fields (Should Fail) ===\n";
$invalidStrictData = [
    'username' => 'jane_doe',
    'email' => 'jane@example.com',
    'age' => 30,
    'extra_field' => 'not allowed!', // This should cause an error
    'another_field' => 'also not allowed!'
];

$result = $handler->handleStrictRequest($invalidStrictData);
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

echo "=== Example 3: Flexible Mode - Extra Fields (Should Pass) ===\n";
$flexibleData = [
    'username' => 'bob_smith',
    'email' => 'bob@example.com',
    'age' => 35,
    'extra_field' => 'this is allowed',
    'another_field' => 'this too'
];

$result = $handler->handleFlexibleRequest($flexibleData);
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

echo "=== Example 4: Missing Required Field ===\n";
$missingFieldData = [
    'username' => 'alice',
    'email' => 'alice@example.com',
    // age is missing!
];

$result = $handler->handleStrictRequest($missingFieldData);
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

echo "=== Example 5: Validation Error ===\n";
$validationErrorData = [
    'username' => 'charlie',
    'email' => 'not-an-email', // Invalid email
    'age' => 15 // Too young
];

$result = $handler->handleStrictRequest($validationErrorData);
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

echo "=== Example 6: Complex User Profile ===\n";
$profileData = [
    'id' => 'user_123',
    'username' => 'profile_user',
    'email' => 'profile@example.com',
    'firstName' => 'John',
    'lastName' => 'Doe',
    'age' => 28,
    'phone' => '+1234567890',
    'bio' => 'Software developer with 5 years experience'
];

$result = $handler->handleUserProfile($profileData);
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

echo "=== Example 7: User Profile with Missing Required Field ===\n";
$incompleteProfileData = [
    'username' => 'incomplete_user',
    'email' => 'incomplete@example.com',
    'firstName' => 'Jane',
    'lastName' => 'Smith',
    'age' => 25
    // id is missing!
];

$result = $handler->handleUserProfile($incompleteProfileData);
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

echo "=== Example 8: User Profile with Validation Error ===\n";
$invalidProfileData = [
    'id' => 'user_456',
    'username' => 'invalid_user',
    'email' => 'invalid-email-format',
    'firstName' => 'Invalid',
    'lastName' => 'User',
    'age' => 10 // Too young
];

$result = $handler->handleUserProfile($invalidProfileData);
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

// Test ArrayAccess with strict mode
echo "=== Example 9: ArrayAccess with Strict Mode ===\n";
try {
    Struct::$strictMode = true;
    
    $strictRequest = new StrictApiRequest([
        'username' => 'array_user',
        'email' => 'array@example.com',
        'age' => 30
    ]);
    
    echo "✅ Username via array access: {$strictRequest['username']}\n";
    echo "✅ Email via array access: {$strictRequest['email']}\n";
    echo "✅ Age via array access: {$strictRequest['age']}\n";
    
    // Test offsetExists
    echo "✅ Username exists: " . ($strictRequest->offsetExists('username') ? 'Yes' : 'No') . "\n";
    echo "✅ Extra field exists: " . ($strictRequest->offsetExists('extra_field') ? 'Yes' : 'No') . "\n";
    
} catch (\Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
} finally {
    Struct::$strictMode = false;
}

echo "\n";

// Test JSON serialization with strict mode
echo "=== Example 10: JSON Serialization with Strict Mode ===\n";
try {
    Struct::$strictMode = true;
    
    $strictRequest = new StrictApiRequest([
        'username' => 'json_user',
        'email' => 'json@example.com',
        'age' => 32
    ]);
    
    echo "✅ JSON serialization:\n";
    echo $strictRequest->toJson(pretty: true) . "\n";
    
    // Test fromJson
    $json = '{"username":"json_from","email":"jsonfrom@example.com","age":28}';
    $fromJson = StrictApiRequest::fromJson($json);
    echo "✅ From JSON: {$fromJson->username} ({$fromJson->email})\n";
    
} catch (\Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
} finally {
    Struct::$strictMode = false;
}

echo "\n";

echo "=== Summary ===\n";
echo "✅ Strict mode prevents extra fields for security\n";
echo "✅ Flexible mode allows extra fields for compatibility\n";
echo "✅ FieldNotFoundException for missing required fields\n";
echo "✅ ValidationException for validation failures\n";
echo "✅ StructException for general struct errors\n";
echo "✅ Comprehensive error handling in API handlers\n";
echo "✅ ArrayAccess works with strict mode\n";
echo "✅ JSON serialization works with strict mode\n";
echo "✅ Strict mode can be enabled/disabled per request\n";
echo "✅ All features work together seamlessly\n";
