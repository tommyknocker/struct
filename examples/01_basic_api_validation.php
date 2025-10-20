<?php

declare(strict_types=1);

/**
 * Example 1: Basic REST API Validation
 * 
 * This example shows how to validate incoming JSON data from a mobile app
 * using Struct for type-safe data validation.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

// Define user registration request structure
final class RegisterUserRequest extends Struct
{
    #[Field('string')]
    public readonly string $username;

    #[Field('string')]
    public readonly string $email;

    #[Field('string')]
    public readonly string $password;

    #[Field('string', nullable: true)]
    public readonly ?string $phoneNumber;
}

// Simulate API endpoint handler
function handleRegistration(string $jsonInput): array
{
    try {
        // Parse and validate incoming JSON
        $request = RegisterUserRequest::fromJson($jsonInput);

        // At this point, all data is validated and type-safe
        // You can safely use the data
        $user = [
            'id' => uniqid(),
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phoneNumber,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        // Return success response
        return [
            'success' => true,
            'data' => $user,
        ];
    } catch (RuntimeException $e) {
        // Validation failed - return error
        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    } catch (JsonException $e) {
        return [
            'success' => false,
            'error' => 'Invalid JSON format',
        ];
    }
}

// Example 1: Valid request from mobile app
echo "=== Example 1: Valid Request ===\n";
$validJson = json_encode([
    'username' => 'john_doe',
    'email' => 'john@example.com',
    'password' => 'SecurePass123!',
    'phoneNumber' => '+1234567890',
]);

$response = handleRegistration($validJson);
echo json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Example 2: Missing required field
echo "=== Example 2: Missing Required Field ===\n";
$invalidJson = json_encode([
    'username' => 'jane_doe',
    'email' => 'jane@example.com',
    // password is missing!
]);

$response = handleRegistration($invalidJson);
echo json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Example 3: Wrong type
echo "=== Example 3: Wrong Type ===\n";
$wrongTypeJson = json_encode([
    'username' => 'bob',
    'email' => 'bob@example.com',
    'password' => 12345, // should be string!
    'phoneNumber' => null,
]);

$response = handleRegistration($wrongTypeJson);
echo json_encode($response, JSON_PRETTY_PRINT) . "\n";

