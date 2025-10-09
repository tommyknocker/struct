# Struct

A lightweight structure helper for PHP 8.1+.  
Define your data models with attributes, get automatic validation, array access, and JSON serialization.

---

## Why Struct?

Instead of manually validating arrays, you can define a strict data model with attributes. This makes your code safer, 
more expressive, and ready for modern PHP.

---

## Features

* Attribute‑based field definitions
* Type validation at runtime (scalars, objects, arrays, enums)
* Nullable fields support
* readonly properties for immutability
* Implements `ArrayAccess` and `JsonSerializable`
* PSR‑11 container integration (single object + arrays of objects)
* PHPUnit test coverage

## Examples

### Example: scalars

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
```

### Example: nullable fields

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

### Example: Nested objects

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
    'address' => new Address(['city' => 'Berlin', 'street' => 'Unter den Linden']),
]);
```

### Example: Arrays of objects

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
        new Address(['city' => 'Paris', 'street' => 'Champs-Élysées']),
        new Address(['city' => 'Rome', 'street' => 'Via del Corso']),
    ],
]);

```

### Example: Enums

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

### Example: Using a PSR-11 Container

`Struct` can optionally use a PSR-11 container to resolve nested objects.  
If a class is registered in the container, it will be retrieved from there; otherwise, it will be created via `new`.

```php
use Psr\Container\ContainerInterface;
use Tommyknocker\Struct\Struct;
use Tommyknocker\Struct\Field;

final class SimpleContainer implements ContainerInterface
{
    private array $services = [];

    public function set(string $id, mixed $service): void
    {
        $this->services[$id] = $service;
    }

    public function get(string $id): mixed
    {
        return $this->services[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }
}

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

// Setup container
$container = new SimpleContainer();
Struct::$container = $container;

// Register Address
$container->set(Address::class, new Address(['city' => 'Amsterdam', 'street' => 'Damrak']));

// Create User
$user = new User([
    'name' => 'Alice',
    'address' => ['city' => 'Amsterdam', 'street' => 'Damrak'],
]);

echo $user->address->city; // Amsterdam
```

### Example: Arrays of Objects with a PSR-11 Container

```php
## Example: Arrays of Objects with a PSR-11 Container

`Struct` can also hydrate arrays of nested objects.  
If the class is registered in the container, it will be resolved from there; otherwise, it will be created via `new`.

```php
use Psr\Container\ContainerInterface;
use Tommyknocker\Struct\Struct;
use Tommyknocker\Struct\Field;

final class SimpleContainer implements ContainerInterface
{
    private array $services = [];

    public function set(string $id, mixed $service): void
    {
        $this->services[$id] = $service;
    }

    public function get(string $id): mixed
    {
        return $this->services[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }
}

final class Address extends Struct
{
    #[Field('string')]
    public readonly string $city;

    #[Field('string')]
    public readonly string $street;
}

final class UserWithHistory extends Struct
{
    #[Field('string')]
    public readonly string $name;

    #[Field(Address::class, isArray: true)]
    public readonly array $previousAddresses;
}

// Setup container
$container = new SimpleContainer();
Struct::$container = $container;

// Register Address in container
$container->set(Address::class, new Address(['city' => 'Berlin', 'street' => 'Unter den Linden']));

// Create User with array of addresses
$user = new UserWithHistory([
    'name' => 'Alice',
    'previousAddresses' => [
        ['city' => 'Berlin', 'street' => 'Unter den Linden'],
        ['city' => 'Paris', 'street' => 'Champs-Élysées'],
    ],
]);

echo $user->previousAddresses[0]->city; // Berlin
echo $user->previousAddresses[1]->street; // Champs-Élysées
```

## Testing

```bash
./vendor/bin/phpunit --bootstrap vendor/autoload.php tests
```
or
```bash
composer run-script test
```