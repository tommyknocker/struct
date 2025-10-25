# Struct

[![CI](https://github.com/tommyknocker/struct/workflows/CI/badge.svg)](https://github.com/tommyknocker/struct/actions)
[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg)](https://phpstan.org/)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

A lightweight, type-safe structure helper for PHP 8.1+.  
Define your data models with attributes, get automatic validation, array access, and JSON serialization.

---

## 🚀 Why Struct?

Instead of manually validating arrays, you can define a strict data model with attributes. This makes your code:
- ✅ **Type-safe** with runtime validation
- 🔒 **Immutable** with readonly properties
- 📦 **Serializable** with built-in JSON support
- 🎯 **Simple** with minimal boilerplate

---

## 📦 Installation

Install via Composer:

```bash
composer require tommyknocker/struct
```

**Requirements:**
- PHP 8.1 or higher
- Composer

---

## ✨ Features

* 🏷️ **Attribute-based field definitions** – Clean and declarative syntax
* ✅ **Advanced type validation** – Scalars, objects, arrays, enums, DateTime, union types
* 🔒 **Immutability** – readonly properties by design
* 🌐 **JSON support** – `toJson()`, `fromJson()`, `JsonSerializable`
* 🔄 **Array conversion** – `toArray()` with recursive support
* 📝 **Default values** – Optional fields with defaults
* 🔑 **Field aliases** – Map different key names
* ✔️ **Flexible validation system** – Custom validators, validation rules, and transformers
* 🎭 **Mixed type support** – Handle dynamic data
* ⏰ **DateTime parsing** – Automatic string to DateTime conversion
* 🔁 **Cloning with modifications** – `with()` method
* 📊 **ArrayAccess** – Array-like read access
* 🧰 **PSR-11 container integration** – DI support
* 🏭 **Factory pattern** – Centralized struct creation with dependency injection
* 🔍 **PHPStan Level 9** – Maximum static analysis
* 🧪 **100% tested** – PHPUnit coverage
* ⚡ **Performance optimized** – Reflection caching and metadata system

## 🎯 Use Cases

Perfect for:
- 📱 **REST API validation** for mobile apps with flexible field types
- 🔄 **Data Transfer Objects (DTOs)** in clean architecture with validation rules
- 🌐 **Third-party API integration** with field mapping and transformations
- ✅ **Form validation** with complex rules and data processing
- 📊 **Data serialization/deserialization** with custom formats
- 🛡️ **Type-safe data handling** in microservices with union types
- 🏭 **Enterprise applications** with centralized struct creation and dependency injection
- 🔍 **Data processing pipelines** with automatic transformations and validation

👉 **[See practical examples](examples/)** for mobile app REST API scenarios

---

## 📚 Examples

### Basic Usage: Scalars

```php
use tommyknocker\struct\Struct;
use tommyknocker\struct\Field;

final class Hit extends Struct
{
    #[Field('string')]
    public readonly string $date;

    #[Field('int')]
    public readonly int $type;

    #[Field('string')]
    public readonly string $ip;

    #[Field('string')]
    public readonly string $uuid;

    #[Field('string')]
    public readonly string $referer;
}

$hit = new Hit([
    'date' => '2025-10-09',
    'type' => 1,
    'ip' => '127.0.0.1',
    'uuid' => '7185bbe3-cdd7-4154-88c3-c63416a76327',
    'referer' => 'https://google.com',
]);

echo $hit->date; // 2025-10-09
echo $hit['ip']; // 127.0.0.1 (ArrayAccess support)
```

### Nullable Fields

```php
final class Person extends Struct
{
    #[Field('string')]
    public readonly string $name;

    #[Field('int', nullable: true)]
    public readonly ?int $age;
}

$person = new Person(['name' => 'Alice', 'age' => null]);
```

### Default Values

```php
final class Config extends Struct
{
    #[Field('string', default: 'localhost')]
    public readonly string $host;

    #[Field('int', default: 3306)]
    public readonly int $port;
}

// Both fields use defaults
$config = new Config([]);
echo $config->host; // localhost
echo $config->port; // 3306
```

### Field Aliases

```php
final class User extends Struct
{
    #[Field('string', alias: 'user_name')]
    public readonly string $name;

    #[Field('string', alias: 'email_address')]
    public readonly string $email;
}

// Use API keys as they come
$user = new User([
    'user_name' => 'John',
    'email_address' => 'john@example.com'
]);
```

### Union Types

```php
final class FlexibleField extends Struct
{
    #[Field(['string', 'int'])]
    public readonly string|int $value;
}

$flexible = new FlexibleField(['value' => 'hello']); // ✅ String
$flexible2 = new FlexibleField(['value' => 42]);     // ✅ Integer
// new FlexibleField(['value' => 3.14]); // ❌ Float not allowed
```

### Advanced Validation Rules

```php
use tommyknocker\struct\validation\rules\EmailRule;
use tommyknocker\struct\validation\rules\RangeRule;

final class UserProfile extends Struct
{
    #[Field('string', validationRules: [new EmailRule()])]
    public readonly string $email;

    #[Field('int', validationRules: [new RangeRule(18, 120)])]
    public readonly int $age;
}

$profile = new UserProfile([
    'email' => 'user@example.com',
    'age' => 25
]); // ✅ Valid
```

### Data Transformations

```php
use tommyknocker\struct\transformation\StringToUpperTransformer;

final class ProcessedData extends Struct
{
    #[Field('string', transformers: [new StringToUpperTransformer()])]
    public readonly string $name;
}

$data = new ProcessedData(['name' => 'john doe']);
echo $data->name; // JOHN DOE
```

### Custom Validation (Legacy Support)

```php
class EmailValidator
{
    public static function validate(mixed $value): bool|string
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "Invalid email format";
        }
        return true;
    }
}

final class Contact extends Struct
{
    #[Field('string', validator: EmailValidator::class)]
    public readonly string $email;
}

$contact = new Contact(['email' => 'test@example.com']); // ✅ OK
// new Contact(['email' => 'invalid']); // ❌ Throws ValidationException
```

### DateTime Support

```php
final class Event extends Struct
{
    #[Field('string')]
    public readonly string $name;

    #[Field(\DateTimeImmutable::class)]
    public readonly \DateTimeImmutable $date;
}

// Accepts string or DateTime
$event = new Event([
    'name' => 'Conference',
    'date' => '2025-12-31 10:00:00'
]);
```

### Mixed Type Support

```php
final class Payload extends Struct
{
    #[Field('string')]
    public readonly string $type;

    #[Field('mixed')]
    public readonly mixed $data; // Can be anything
}
```

### Nested Objects

```php
final class Address extends Struct
{
    #[Field('string')]
    public readonly string $city;

    #[Field('string')]
    public readonly string $street;
}

final class User extends Struct
{
    #[Field('string')]
    public readonly string $name;

    #[Field(Address::class)]
    public readonly Address $address;
}

$user = new User([
    'name' => 'Bob',
    'address' => ['city' => 'Berlin', 'street' => 'Unter den Linden'],
]);
```

### Arrays of Objects

```php
final class UserWithHistory extends Struct
{
    #[Field('string')]
    public readonly string $name;

    #[Field(Address::class, isArray: true)]
    public readonly array $previousAddresses;
}

$user = new UserWithHistory([
    'name' => 'Charlie',
    'previousAddresses' => [
        ['city' => 'Paris', 'street' => 'Champs-Élysées'],
        ['city' => 'Rome', 'street' => 'Via del Corso'],
    ],
]);
```

### Enums

```php
enum UserType: string
{
    case Admin = 'admin';
    case Regular = 'regular';
    case Guest = 'guest';
}

final class Account extends Struct
{
    #[Field(UserType::class)]
    public readonly UserType $type;

    #[Field('string')]
    public readonly string $email;
}

$account = new Account([
    'type' => UserType::Admin,
    'email' => 'admin@example.com',
]);
```

### JSON Serialization

```php
$user = new User(['name' => 'Alice', 'address' => ['city' => 'Berlin', 'street' => 'Main St']]);

// To JSON
$json = $user->toJson(pretty: true);

// From JSON
$restored = User::fromJson($json);

// To Array
$array = $user->toArray(); // Recursive for nested structs
```

### Cloning with Modifications

```php
$user = new User(['name' => 'Alice', 'age' => 30]);

// Create modified copy
$updated = $user->with(['age' => 31]);

echo $user->age;    // 30 (original unchanged)
echo $updated->age; // 31 (new instance)
```

### Strict Mode (Validate No Extra Fields)

```php
use tommyknocker\struct\Struct;

// Enable strict mode globally
Struct::$strictMode = true;

final class ApiRequest extends Struct
{
    #[Field('string')]
    public readonly string $username;

    #[Field('string')]
    public readonly string $email;
}

// ✅ Valid - all fields are known
$request = new ApiRequest([
    'username' => 'john',
    'email' => 'john@example.com',
]);

// ❌ Throws RuntimeException: Unknown field: extra_field
$request = new ApiRequest([
    'username' => 'john',
    'email' => 'john@example.com',
    'extra_field' => 'not allowed!',
]);

// Disable strict mode (default behavior - extra fields ignored)
Struct::$strictMode = false;
```

### Factory Pattern

```php
use tommyknocker\struct\factory\StructFactory;

// Setup factory with dependencies
$factory = new StructFactory();

// Create struct instances
$user = $factory->create(User::class, [
    'name' => 'Alice',
    'email' => 'alice@example.com'
]);

// Create from JSON
$userFromJson = $factory->createFromJson(User::class, '{"name":"Bob","email":"bob@example.com"}');
```

### Error Handling

```php
use tommyknocker\struct\exception\ValidationException;
use tommyknocker\struct\exception\FieldNotFoundException;

try {
    $user = new User(['name' => 'John', 'email' => 'invalid-email']);
} catch (ValidationException $e) {
    echo "Validation error: " . $e->getMessage();
    echo "Field: " . $e->fieldName;
    echo "Value: " . $e->value;
} catch (FieldNotFoundException $e) {
    echo "Missing field: " . $e->getMessage();
}
```

### Real-World API Example

```php
// API endpoint for user registration
final class RegisterRequest extends Struct
{
    #[Field('string', validationRules: [new EmailRule()])]
    public readonly string $email;

    #[Field('string', validationRules: [new RangeRule(8, 50)])]
    public readonly string $password;

    #[Field('string', alias: 'full_name')]
    public readonly string $fullName;

    #[Field('int', nullable: true, validationRules: [new RangeRule(13, 120)])]
    public readonly ?int $age;
}

// In your API controller
public function register(Request $request): JsonResponse
{
    try {
        $data = RegisterRequest::fromJson($request->getContent());
        
        // Create user account
        $user = User::create([
            'email' => $data->email,
            'password' => Hash::make($data->password),
            'full_name' => $data->fullName,
            'age' => $data->age,
        ]);
        
        return response()->json([
            'success' => true,
            'user' => $user->toArray()
        ]);
        
    } catch (ValidationException $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'field' => $e->fieldName
        ], 422);
    }
}
```

## 💡 Best Practices

### 1. Always Validate Input
```php
// ✅ Good - Validate all incoming data
$userData = UserRequest::fromJson($request->getContent());

// ❌ Bad - Trusting raw input
$userData = json_decode($request->getContent(), true);
```

### 2. Use Specific Exception Types
```php
try {
    $data = MyStruct::fromJson($json);
} catch (ValidationException $e) {
    // Handle validation errors specifically
    return response()->json(['error' => $e->getMessage()], 422);
} catch (FieldNotFoundException $e) {
    // Handle missing fields
    return response()->json(['error' => 'Missing required field'], 400);
}
```

### 3. Leverage Field Aliases for API Integration
```php
final class ApiResponse extends Struct
{
    #[Field('string', alias: 'user_name')]
    public readonly string $userName;
    
    #[Field('string', alias: 'email_address')]
    public readonly string $emailAddress;
}

// Works with external API that uses snake_case
$response = new ApiResponse([
    'user_name' => 'John Doe',
    'email_address' => 'john@example.com'
]);
```

### 4. Use Default Values for Optional Fields
```php
final class Config extends Struct
{
    #[Field('string', default: 'localhost')]
    public readonly string $host;
    
    #[Field('int', default: 3306)]
    public readonly int $port;
    
    #[Field('bool', default: false)]
    public readonly bool $debug;
}

// All fields get defaults if not provided
$config = new Config([]);
```

### 5. Combine Validation Rules for Complex Logic
```php
final class PasswordField extends Struct
{
    #[Field('string', validationRules: [
        new RequiredRule(),
        new RangeRule(8, 128),
        new PasswordStrengthRule()
    ])]
    public readonly string $password;
}
```

---

## ❓ FAQ

### Q: How is this different from regular PHP classes?
A: Struct provides automatic validation, type casting, JSON serialization, and immutability out of the box. Regular classes require manual implementation of these features.

### Q: Can I use this with existing frameworks?
A: Yes! Struct works with any PHP framework. See the [examples](examples/) for Laravel, Symfony, and Slim integration.

### Q: What about performance?
A: Struct uses reflection caching and optimized metadata systems. It's designed for production use with minimal overhead.

### Q: Can I extend Struct classes?
A: Yes, but remember that Struct classes are immutable. Use the `with()` method to create modified copies.

### Q: How do I handle optional fields?
A: Use `nullable: true` for fields that can be null, or `default: value` for fields with default values.

### Q: What validation rules are available?
A: Built-in rules include `EmailRule`, `RangeRule`, `RequiredRule`. You can create custom rules by extending `ValidationRule`.

### Q: Can I use this for database models?
A: Struct is designed for data validation and transfer, not ORM functionality. Use it for DTOs, API requests/responses, and data validation.

---

## 🧪 Testing

The library is thoroughly tested with 100% code coverage:

```bash
composer test
```

All examples are verified to work:

```bash
composer test-examples
```

---

## 🛠️ Development

This project follows PSR-12 coding standards and uses PHPStan Level 9 for static analysis.

For contributors:
- Run `composer check` to verify all tests and standards
- Follow the existing code style
- Add tests for new features
- Update documentation as needed

---

## 📝 API Reference

### Field Attribute

```php
#[Field(
    type: string|array<string>,                    // Type: 'string', 'int', 'float', 'bool', 'mixed', class-string, or array of types for union
    nullable: bool = false,                        // Allow null values
    isArray: bool = false,                         // Field is array of type
    default: mixed = null,                         // Default value if not provided
    alias: ?string = null,                         // Alternative key name in input data
    validator: ?string = null,                     // Legacy validator class with static validate() method
    validationRules: array = [],                   // Array of ValidationRule instances
    transformers: array = []                       // Array of TransformerInterface instances
)]
```

### Validation Rules

```php
// Built-in validation rules
use tommyknocker\struct\validation\rules\EmailRule;
use tommyknocker\struct\validation\rules\RangeRule;
use tommyknocker\struct\validation\rules\RequiredRule;

// Custom validation rule
class CustomRule extends \tommyknocker\struct\validation\ValidationRule
{
    public function validate(mixed $value): \tommyknocker\struct\validation\ValidationResult
    {
        // Your validation logic
        return \tommyknocker\struct\validation\ValidationResult::valid();
    }
}
```

### Data Transformers

```php
// Built-in transformers
use tommyknocker\struct\transformation\StringToUpperTransformer;
use tommyknocker\struct\transformation\StringToLowerTransformer;

// Custom transformer
class CustomTransformer implements \tommyknocker\struct\transformation\TransformerInterface
{
    public function transform(mixed $value): mixed
    {
        // Your transformation logic
        return $value;
    }
}
```

### Factory and Serialization

```php
// Factory for struct creation
use tommyknocker\struct\factory\StructFactory;

// JSON serialization
use tommyknocker\struct\serialization\JsonSerializer;

// Metadata system
use tommyknocker\struct\metadata\MetadataFactory;
use tommyknocker\struct\metadata\StructMetadata;
use tommyknocker\struct\metadata\FieldMetadata;
```

### Struct Methods

```php
// Constructor
public function __construct(array $data)

// Array conversion (recursive)
public function toArray(): array

// JSON serialization
public function toJson(bool $pretty = false, int $flags = ...): string

// Create from JSON
public static function fromJson(string $json, int $flags = JSON_THROW_ON_ERROR): static

// Clone with modifications
public function with(array $changes): static

// ArrayAccess (read-only)
public function offsetExists(mixed $offset): bool
public function offsetGet(mixed $offset): mixed

// JsonSerializable
public function jsonSerialize(): mixed
```

---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run tests and checks (`composer check`)
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 🙏 Acknowledgments

- Inspired by modern typed data structures in other languages
- Built with modern PHP 8.1+ features
- Tested with PHPUnit 11
- Analyzed with PHPStan Level 9

---

## 📧 Author

**Vasiliy Krivoplyas**  
Email: vasiliy@krivoplyas.com

---

## 🔗 Links

- [GitHub Repository](https://github.com/tommyknocker/struct)
- [Packagist Package](https://packagist.org/packages/tommyknocker/struct)
- [Issue Tracker](https://github.com/tommyknocker/struct/issues)
