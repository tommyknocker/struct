<?php

declare(strict_types=1);

/**
 * Example 7: Factory Pattern and PSR-11 Container Integration
 * 
 * This example shows how to use the Factory pattern for centralized
 * struct creation and PSR-11 container integration for dependency injection.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\factory\StructFactory;
use tommyknocker\struct\serialization\JsonSerializer;
use tommyknocker\struct\validation\FieldValidator;
use tommyknocker\struct\validation\rules\EmailRule;
use tommyknocker\struct\validation\rules\RangeRule;
use tommyknocker\struct\transformation\StringToUpperTransformer;
use Psr\Container\ContainerInterface;

// Simple PSR-11 Container implementation
class SimpleContainer implements ContainerInterface
{
    private array $services = [];

    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new \Exception("Service {$id} not found");
        }
        return $this->services[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }

    public function set(string $id, mixed $service): void
    {
        $this->services[$id] = $service;
    }
}

// Example structs for demonstration
final class User extends Struct
{
    #[Field('string')]
    public readonly string $id;

    #[Field('string')]
    public readonly string $username;

    #[Field('string', validationRules: [new EmailRule()])]
    public readonly string $email;

    #[Field('int', validationRules: [new RangeRule(18, 120)])]
    public readonly int $age;
}

final class Product extends Struct
{
    #[Field('string')]
    public readonly string $id;

    #[Field('string', transformers: [new StringToUpperTransformer()])]
    public readonly string $name;

    #[Field('float')]
    public readonly float $price;

    #[Field('int')]
    public readonly int $stock;
}

final class Order extends Struct
{
    #[Field('string')]
    public readonly string $id;

    #[Field(User::class)]
    public readonly User $user;

    #[Field(Product::class, isArray: true)]
    public readonly array $products;

    #[Field('float')]
    public readonly float $total;
}

// Service classes that use StructFactory
class UserService
{
    public function __construct(
        private readonly StructFactory $factory
    ) {}

    public function createUser(array $data): User
    {
        return $this->factory->create(User::class, $data);
    }

    public function createUserFromJson(string $json): User
    {
        return $this->factory->createFromJson(User::class, $json);
    }
}

class ProductService
{
    public function __construct(
        private readonly StructFactory $factory
    ) {}

    public function createProduct(array $data): Product
    {
        return $this->factory->create(Product::class, $data);
    }

    public function createProductFromJson(string $json): Product
    {
        return $this->factory->createFromJson(Product::class, $json);
    }
}

class OrderService
{
    public function __construct(
        private readonly StructFactory $factory
    ) {}

    public function createOrder(array $data): Order
    {
        return $this->factory->create(Order::class, $data);
    }

    public function createOrderFromJson(string $json): Order
    {
        return $this->factory->createFromJson(Order::class, $json);
    }
}

// API Controller simulation
class ApiController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly ProductService $productService,
        private readonly OrderService $orderService
    ) {}

    public function createUser(string $jsonData): string
    {
        try {
            $user = $this->userService->createUserFromJson($jsonData);
            
            return json_encode([
                'success' => true,
                'data' => $user->toArray()
            ], JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_PRETTY_PRINT);
        }
    }

    public function createProduct(string $jsonData): string
    {
        try {
            $product = $this->productService->createProductFromJson($jsonData);
            
            return json_encode([
                'success' => true,
                'data' => $product->toArray()
            ], JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_PRETTY_PRINT);
        }
    }

    public function createOrder(string $jsonData): string
    {
        try {
            $order = $this->orderService->createOrderFromJson($jsonData);
            
            return json_encode([
                'success' => true,
                'data' => $order->toArray()
            ], JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_PRETTY_PRINT);
        }
    }
}

// Setup dependency injection
echo "=== Setting up Dependency Injection ===\n";

// Create container
$container = new SimpleContainer();

// Create factory
$factory = new StructFactory();

// Register services in container
$container->set(StructFactory::class, $factory);

// Create service instances
$userService = new UserService($factory);
$productService = new ProductService($factory);
$orderService = new OrderService($factory);

$container->set(UserService::class, $userService);
$container->set(ProductService::class, $productService);
$container->set(OrderService::class, $orderService);

// Create controller
$controller = new ApiController($userService, $productService, $orderService);
$container->set(ApiController::class, $controller);

echo "✅ Container setup complete\n\n";

// Test Factory Pattern
echo "=== Testing Factory Pattern ===\n";

// Test direct factory usage
$userData = [
    'id' => 'user_123',
    'username' => 'john_doe',
    'email' => 'john@example.com',
    'age' => 25
];

$user = $factory->create(User::class, $userData);
echo "✅ User created via factory: {$user->username} ({$user->email})\n";

// Test JSON creation
$productJson = json_encode([
    'id' => 'prod_456',
    'name' => 'wireless mouse',
    'price' => 29.99,
    'stock' => 100
]);

$product = $factory->createFromJson(Product::class, $productJson);
echo "✅ Product created from JSON: {$product->name} (\${$product->price})\n\n";

// Test Service Layer
echo "=== Testing Service Layer ===\n";

$userJson = json_encode([
    'id' => 'user_789',
    'username' => 'jane_smith',
    'email' => 'jane@example.com',
    'age' => 30
]);

$createdUser = $userService->createUserFromJson($userJson);
echo "✅ User created via service: {$createdUser->username}\n";

$productData = [
    'id' => 'prod_789',
    'name' => 'gaming keyboard',
    'price' => 79.99,
    'stock' => 50
];

$createdProduct = $productService->createProduct($productData);
echo "✅ Product created via service: {$createdProduct->name}\n\n";

// Test API Controller
echo "=== Testing API Controller ===\n";

$newUserJson = json_encode([
    'id' => 'user_api_001',
    'username' => 'api_user',
    'email' => 'api@example.com',
    'age' => 28
]);

echo "Creating user via API:\n";
echo $controller->createUser($newUserJson) . "\n\n";

$newProductJson = json_encode([
    'id' => 'prod_api_001',
    'name' => 'bluetooth headphones',
    'price' => 99.99,
    'stock' => 25
]);

echo "Creating product via API:\n";
echo $controller->createProduct($newProductJson) . "\n\n";

// Test complex nested structure
echo "=== Testing Complex Nested Structure ===\n";

$orderData = [
    'id' => 'order_001',
    'user' => $userData,
    'products' => [
        $productData,
        [
            'id' => 'prod_999',
            'name' => 'usb cable',
            'price' => 9.99,
            'stock' => 200
        ]
    ],
    'total' => 119.97
];

$order = $orderService->createOrder($orderData);
echo "✅ Order created: {$order->id}\n";
echo "   User: {$order->user->username}\n";
echo "   Products: " . count($order->products) . " items\n";
echo "   Total: \${$order->total}\n\n";

// Test error handling
echo "=== Testing Error Handling ===\n";

$invalidUserJson = json_encode([
    'id' => 'user_invalid',
    'username' => 'invalid_user',
    'email' => 'not-an-email', // Invalid email!
    'age' => 15 // Too young!
]);

echo "Creating user with invalid data:\n";
echo $controller->createUser($invalidUserJson) . "\n\n";

// Test container resolution
echo "=== Testing Container Resolution ===\n";

$resolvedFactory = $container->get(StructFactory::class);
$resolvedUserService = $container->get(UserService::class);
$resolvedController = $container->get(ApiController::class);

echo "✅ Factory resolved from container: " . get_class($resolvedFactory) . "\n";
echo "✅ UserService resolved from container: " . get_class($resolvedUserService) . "\n";
echo "✅ Controller resolved from container: " . get_class($resolvedController) . "\n\n";

// Test JSON serialization with custom serializer
echo "=== Testing Custom JSON Serialization ===\n";

$customSerializer = new JsonSerializer();
$json = $customSerializer->toJson($user, pretty: true);
echo "Custom JSON serialization:\n";
echo $json . "\n\n";

$deserializedUser = $customSerializer->fromJson($json, User::class);
echo "✅ User deserialized: {$deserializedUser->username}\n\n";

echo "=== Summary ===\n";
echo "✅ Factory pattern centralizes struct creation\n";
echo "✅ PSR-11 container enables dependency injection\n";
echo "✅ Service layer encapsulates business logic\n";
echo "✅ API controllers use injected services\n";
echo "✅ Complex nested structures work seamlessly\n";
echo "✅ Error handling is consistent across layers\n";
echo "✅ JSON serialization is customizable\n";
echo "✅ All components are testable and maintainable\n";
