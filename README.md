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
* ✅ **Type validation** – Scalars, objects, arrays, enums, DateTime
* 🔒 **Immutability** – readonly properties by design
* 🌐 **JSON support** – `toJson()`, `fromJson()`, `JsonSerializable`
* 🔄 **Array conversion** – `toArray()` with recursive support
* 📝 **Default values** – Optional fields with defaults
* 🔑 **Field aliases** – Map different key names
* ✔️ **Custom validators** – Add your own validation logic
* 🎭 **Mixed type support** – Handle dynamic data
* ⏰ **DateTime parsing** – Automatic string to DateTime conversion
* 🔁 **Cloning with modifications** – `with()` method
* 📊 **ArrayAccess** – Array-like read access
* 🧰 **PSR-11 container integration** – DI support
* 🔍 **PHPStan Level 9** – Maximum static analysis
* 🧪 **100% tested** – PHPUnit coverage
* ⚡ **Performance optimized** – Reflection caching

## 🎯 Use Cases

Perfect for:
- 📱 **REST API validation** for mobile apps
- 🔄 **Data Transfer Objects (DTOs)** in clean architecture
- 🌐 **Third-party API integration** with field mapping
- ✅ **Form validation** with complex rules
- 📊 **Data serialization/deserialization**
- 🛡️ **Type-safe data handling** in microservices

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

### Default Values (NEW!)

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

### Field Aliases (NEW!)

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

### Custom Validation (NEW!)

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
// new Contact(['email' => 'invalid']); // ❌ Throws RuntimeException
```

### DateTime Support (NEW!)

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

### Mixed Type Support (NEW!)

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

### JSON Serialization (ENHANCED!)

```php
$user = new User(['name' => 'Alice', 'address' => ['city' => 'Berlin', 'street' => 'Main St']]);

// To JSON
$json = $user->toJson(pretty: true);

// From JSON (NEW!)
$restored = User::fromJson($json);

// To Array (NEW!)
$array = $user->toArray(); // Recursive for nested structs
```

### Cloning with Modifications (NEW!)

```php
$user = new User(['name' => 'Alice', 'age' => 30]);

// Create modified copy
$updated = $user->with(['age' => 31]);

echo $user->age;    // 30 (original unchanged)
echo $updated->age; // 31 (new instance)
```

### PSR-11 Container Integration

```php
use Psr\Container\ContainerInterface;
use tommyknocker\struct\Struct;

// Setup container
$container = new SimpleContainer();
Struct::$container = $container;

// Register Address
$container->set(Address::class, new Address(['city' => 'Amsterdam', 'street' => 'Damrak']));

// Create User - Address will be resolved from container
$user = new User([
    'name' => 'Alice',
    'address' => ['city' => 'Amsterdam', 'street' => 'Damrak'],
]);
```

---

## 🧪 Testing

Run the test suite:

```bash
composer test
```

Run PHPStan static analysis:

```bash
composer phpstan
```

Check code style:

```bash
composer cs-check
```

Run all checks:

```bash
composer check
```

---

## 🛠️ Development

### Code Style

This project uses PHP-CS-Fixer with PSR-12 standard:

```bash
composer cs-fix
```

### Static Analysis

PHPStan is configured at level 9 for maximum type safety:

```bash
composer phpstan
```

---

## 📝 API Reference

### Field Attribute

```php
#[Field(
    type: string,              // Type: 'string', 'int', 'float', 'bool', 'mixed', or class-string
    nullable: bool = false,    // Allow null values
    isArray: bool = false,     // Field is array of type
    default: mixed = null,     // Default value if not provided
    alias: ?string = null,     // Alternative key name in input data
    validator: ?string = null  // Validator class with static validate() method
)]
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
