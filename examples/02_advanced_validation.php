<?php

declare(strict_types=1);

/**
 * Example 2: Advanced Validation with New Validation Rules
 * 
 * This example shows how to use the new validation system with
 * EmailRule, RangeRule, and RequiredRule for complex validation.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\validation\rules\EmailRule;
use tommyknocker\struct\validation\rules\RangeRule;
use tommyknocker\struct\validation\rules\RequiredRule;
use tommyknocker\struct\exception\ValidationException;
use tommyknocker\struct\exception\FieldNotFoundException;

// Custom validation rule for password strength
class PasswordStrengthRule implements \tommyknocker\struct\validation\ValidationRule
{
    public function validate(mixed $value): \tommyknocker\struct\validation\ValidationResult
    {
        if (!is_string($value)) {
            return \tommyknocker\struct\validation\ValidationResult::invalid("Password must be a string");
        }

        if (strlen($value) < 8) {
            return \tommyknocker\struct\validation\ValidationResult::invalid("Password must be at least 8 characters");
        }

        if (!preg_match('/[A-Z]/', $value)) {
            return \tommyknocker\struct\validation\ValidationResult::invalid("Password must contain at least one uppercase letter");
        }

        if (!preg_match('/[a-z]/', $value)) {
            return \tommyknocker\struct\validation\ValidationResult::invalid("Password must contain at least one lowercase letter");
        }

        if (!preg_match('/[0-9]/', $value)) {
            return \tommyknocker\struct\validation\ValidationResult::invalid("Password must contain at least one digit");
        }

        return \tommyknocker\struct\validation\ValidationResult::valid();
    }
}

// Custom validation rule for phone numbers
class PhoneRule implements \tommyknocker\struct\validation\ValidationRule
{
    public function validate(mixed $value): \tommyknocker\struct\validation\ValidationResult
    {
        if (!is_string($value)) {
            return \tommyknocker\struct\validation\ValidationResult::invalid("Phone must be a string");
        }

        // Remove all non-digit characters
        $digits = preg_replace('/[^0-9]/', '', $value);

        if (strlen($digits) < 10 || strlen($digits) > 15) {
            return \tommyknocker\struct\validation\ValidationResult::invalid("Phone number must be between 10 and 15 digits");
        }

        return \tommyknocker\struct\validation\ValidationResult::valid();
    }
}

// Define user registration with new validation rules
final class ValidatedRegistrationRequest extends Struct
{
    #[Field('string', validationRules: [new RequiredRule()])]
    public readonly string $username;

    #[Field('string', validationRules: [new EmailRule()])]
    public readonly string $email;

    #[Field('string', validationRules: [new PasswordStrengthRule()])]
    public readonly string $password;

    #[Field('string', validationRules: [new PhoneRule()])]
    public readonly string $phone;

    #[Field('int', validationRules: [new RangeRule(13, 120)])]
    public readonly int $age;
}

// Test validation with proper exception handling
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
} catch (ValidationException $e) {
    echo "❌ Validation failed: {$e->getMessage()}\n";
    echo "Field: {$e->fieldName}\n";
    echo "Value: " . json_encode($e->value) . "\n\n";
} catch (FieldNotFoundException $e) {
    echo "❌ Missing field: {$e->getMessage()}\n\n";
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
} catch (ValidationException $e) {
    echo "❌ Validation failed: {$e->getMessage()}\n";
    echo "Field: {$e->fieldName}\n\n";
} catch (FieldNotFoundException $e) {
    echo "❌ Missing field: {$e->getMessage()}\n\n";
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
} catch (ValidationException $e) {
    echo "❌ Validation failed: {$e->getMessage()}\n";
    echo "Field: {$e->fieldName}\n\n";
} catch (FieldNotFoundException $e) {
    echo "❌ Missing field: {$e->getMessage()}\n\n";
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
} catch (ValidationException $e) {
    echo "❌ Validation failed: {$e->getMessage()}\n";
    echo "Field: {$e->fieldName}\n\n";
} catch (FieldNotFoundException $e) {
    echo "❌ Missing field: {$e->getMessage()}\n\n";
}

echo "=== Example 5: Age Out of Range ===\n";
try {
    $request = new ValidatedRegistrationRequest([
        'username' => 'kid',
        'email' => 'kid@example.com',
        'password' => 'SecurePass123',
        'phone' => '+1234567890',
        'age' => 10,
    ]);
    echo "✅ Registration successful!\n\n";
} catch (ValidationException $e) {
    echo "❌ Validation failed: {$e->getMessage()}\n";
    echo "Field: {$e->fieldName}\n\n";
} catch (FieldNotFoundException $e) {
    echo "❌ Missing field: {$e->getMessage()}\n\n";
}

echo "=== Example 6: Missing Required Field ===\n";
try {
    $request = new ValidatedRegistrationRequest([
        // username is missing!
        'email' => 'test@example.com',
        'password' => 'SecurePass123',
        'phone' => '+1234567890',
        'age' => 25,
    ]);
    echo "✅ Registration successful!\n\n";
} catch (ValidationException $e) {
    echo "❌ Validation failed: {$e->getMessage()}\n";
    echo "Field: {$e->fieldName}\n\n";
} catch (FieldNotFoundException $e) {
    echo "❌ Missing field: {$e->getMessage()}\n\n";
}

