<?php

declare(strict_types=1);

/**
 * Example 2: Advanced Validation with Custom Validators
 * 
 * This example shows how to use custom validators for email, phone,
 * and other complex validation rules.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

// Custom validators
class EmailValidator
{
    public static function validate(mixed $value): bool|string
    {
        if (!is_string($value)) {
            return "Email must be a string";
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "Invalid email format";
        }

        // Additional checks
        $parts = explode('@', $value);
        if (count($parts) !== 2) {
            return "Invalid email format";
        }

        [$local, $domain] = $parts;
        if (strlen($local) < 1 || strlen($domain) < 3) {
            return "Email format is too short";
        }

        return true;
    }
}

class PhoneValidator
{
    public static function validate(mixed $value): bool|string
    {
        if (!is_string($value)) {
            return "Phone must be a string";
        }

        // Remove all non-digit characters
        $digits = preg_replace('/[^0-9]/', '', $value);

        if (strlen($digits) < 10 || strlen($digits) > 15) {
            return "Phone number must be between 10 and 15 digits";
        }

        return true;
    }
}

class PasswordValidator
{
    public static function validate(mixed $value): bool|string
    {
        if (!is_string($value)) {
            return "Password must be a string";
        }

        if (strlen($value) < 8) {
            return "Password must be at least 8 characters";
        }

        if (!preg_match('/[A-Z]/', $value)) {
            return "Password must contain at least one uppercase letter";
        }

        if (!preg_match('/[a-z]/', $value)) {
            return "Password must contain at least one lowercase letter";
        }

        if (!preg_match('/[0-9]/', $value)) {
            return "Password must contain at least one digit";
        }

        return true;
    }
}

class AgeValidator
{
    public static function validate(mixed $value): bool|string
    {
        if (!is_int($value)) {
            return "Age must be an integer";
        }

        if ($value < 13) {
            return "You must be at least 13 years old";
        }

        if ($value > 120) {
            return "Invalid age";
        }

        return true;
    }
}

// Define user registration with validation
final class ValidatedRegistrationRequest extends Struct
{
    #[Field('string')]
    public readonly string $username;

    #[Field('string', validator: EmailValidator::class)]
    public readonly string $email;

    #[Field('string', validator: PasswordValidator::class)]
    public readonly string $password;

    #[Field('string', validator: PhoneValidator::class)]
    public readonly string $phone;

    #[Field('int', validator: AgeValidator::class)]
    public readonly int $age;
}

// Test validation
echo "=== Example 1: All Valid ===\n";
try {
    $request = new ValidatedRegistrationRequest([
        'username' => 'john_doe',
        'email' => 'john@example.com',
        'password' => 'SecurePass123',
        'phone' => '+1-234-567-8900',
        'age' => 25,
    ]);
    echo "✅ Registration successful!\n";
    echo "Username: {$request->username}\n";
    echo "Email: {$request->email}\n\n";
} catch (RuntimeException $e) {
    echo "❌ Validation failed: {$e->getMessage()}\n\n";
}

echo "=== Example 2: Invalid Email ===\n";
try {
    $request = new ValidatedRegistrationRequest([
        'username' => 'jane_doe',
        'email' => 'not-an-email',
        'password' => 'SecurePass123',
        'phone' => '+1234567890',
        'age' => 30,
    ]);
    echo "✅ Registration successful!\n\n";
} catch (RuntimeException $e) {
    echo "❌ Validation failed: {$e->getMessage()}\n\n";
}

echo "=== Example 3: Weak Password ===\n";
try {
    $request = new ValidatedRegistrationRequest([
        'username' => 'bob',
        'email' => 'bob@example.com',
        'password' => 'weak',
        'phone' => '+1234567890',
        'age' => 22,
    ]);
    echo "✅ Registration successful!\n\n";
} catch (RuntimeException $e) {
    echo "❌ Validation failed: {$e->getMessage()}\n\n";
}

echo "=== Example 4: Invalid Phone ===\n";
try {
    $request = new ValidatedRegistrationRequest([
        'username' => 'alice',
        'email' => 'alice@example.com',
        'password' => 'SecurePass123',
        'phone' => '123',
        'age' => 28,
    ]);
    echo "✅ Registration successful!\n\n";
} catch (RuntimeException $e) {
    echo "❌ Validation failed: {$e->getMessage()}\n\n";
}

echo "=== Example 5: Too Young ===\n";
try {
    $request = new ValidatedRegistrationRequest([
        'username' => 'kid',
        'email' => 'kid@example.com',
        'password' => 'SecurePass123',
        'phone' => '+1234567890',
        'age' => 10,
    ]);
    echo "✅ Registration successful!\n\n";
} catch (RuntimeException $e) {
    echo "❌ Validation failed: {$e->getMessage()}\n\n";
}

