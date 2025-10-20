# Struct Examples

This directory contains practical examples of using the Struct library in real-world scenarios, particularly for REST API validation in mobile applications.

## 📁 Examples

### 1. Basic API Validation (`01_basic_api_validation.php`)

**Use Case:** Simple user registration endpoint validation

Demonstrates:
- ✅ Parsing JSON from mobile app requests
- ✅ Type validation (string, int, nullable)
- ✅ Error handling and response formatting
- ✅ Missing field detection
- ✅ Type mismatch detection

**Run:**
```bash
php examples/01_basic_api_validation.php
```

---

### 2. Advanced Validation (`02_advanced_validation.php`)

**Use Case:** Registration with custom validation rules

Demonstrates:
- ✅ Custom validators (Email, Phone, Password, Age)
- ✅ Complex validation logic
- ✅ User-friendly error messages
- ✅ Multiple validation rules
- ✅ Real-world password requirements

**Run:**
```bash
php examples/02_advanced_validation.php
```

**Key Features:**
- Email validation with domain checks
- Phone number validation (10-15 digits)
- Password strength requirements (uppercase, lowercase, digits)
- Age validation (13-120 years)

---

### 3. Complex API Structures (`03_complex_api_structures.php`)

**Use Case:** E-commerce order creation with nested objects

Demonstrates:
- ✅ Nested structs (Address, OrderItem, PaymentInfo)
- ✅ Arrays of objects (order items)
- ✅ Enums (OrderStatus, PaymentMethod)
- ✅ Optional nested objects
- ✅ Business logic in structs (calculateTotal)
- ✅ Default values

**Run:**
```bash
php examples/03_complex_api_structures.php
```

**Perfect for:**
- Shopping cart APIs
- Multi-step forms
- Complex data models
- E-commerce platforms

---

### 4. API Response Formatting (`04_api_response_formatting.php`)

**Use Case:** Standardized API responses for mobile apps

Demonstrates:
- ✅ Consistent response structure
- ✅ Pagination metadata
- ✅ Success/error responses
- ✅ Status codes
- ✅ Converting structs to JSON
- ✅ DateTime handling

**Run:**
```bash
php examples/04_api_response_formatting.php
```

**Response Types:**
- Single resource response
- Paginated list response
- Error response
- Success with metadata

---

### 5. Field Mapping and Aliases (`05_field_mapping_aliases.php`)

**Use Case:** Integrating with third-party APIs and legacy systems

Demonstrates:
- ✅ Snake_case to camelCase mapping
- ✅ Custom key mapping (e.g., API has `user_id`, you want `userId`)
- ✅ Handling weird naming conventions (uppercase keys)
- ✅ Combining aliases with defaults
- ✅ Working with external APIs

**Run:**
```bash
php examples/05_field_mapping_aliases.php
```

**Perfect for:**
- Legacy system integration
- Third-party API wrappers
- Database model mapping
- Payment gateway webhooks

---

## 🎯 Common Mobile App Scenarios

### User Registration
See: `01_basic_api_validation.php`, `02_advanced_validation.php`

### User Login
Similar to registration, validate email/username and password

### Profile Updates
Use `with()` method to create updated copies

### Shopping Cart / Orders
See: `03_complex_api_structures.php`

### API Responses
See: `04_api_response_formatting.php`

### Third-Party Integration
See: `05_field_mapping_aliases.php`

---

## 🚀 Quick Start

All examples are standalone and can be run directly:

```bash
# Run all examples
for file in examples/*.php; do
    echo "=== Running $file ==="
    php "$file"
    echo ""
done
```

Or run individually:

```bash
php examples/01_basic_api_validation.php
php examples/02_advanced_validation.php
php examples/03_complex_api_structures.php
php examples/04_api_response_formatting.php
php examples/05_field_mapping_aliases.php
```

---

## 💡 Tips

1. **Always validate input** - Use Struct for all incoming API data
2. **Use custom validators** - Add business-specific validation rules
3. **Keep structs readonly** - Ensures immutability and thread-safety
4. **Use aliases** - Map external APIs to your naming conventions
5. **Leverage enums** - For fixed sets of values (status, types, etc.)
6. **Handle errors gracefully** - Catch RuntimeException for validation errors
7. **Return consistent responses** - Use response structs for all API endpoints

---

## 🔧 Integration with Frameworks

### Laravel

```php
// In your controller
public function register(Request $request)
{
    try {
        $dto = RegisterUserRequest::fromJson($request->getContent());
        
        // Create user from validated data
        $user = User::create([
            'username' => $dto->username,
            'email' => $dto->email,
            'password' => Hash::make($dto->password),
        ]);
        
        return response()->json(['success' => true, 'data' => $user]);
    } catch (RuntimeException $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
    }
}
```

### Symfony

```php
// In your controller
#[Route('/api/register', methods: ['POST'])]
public function register(Request $request): JsonResponse
{
    try {
        $dto = RegisterUserRequest::fromJson($request->getContent());
        
        // Process registration
        // ...
        
        return $this->json(['success' => true]);
    } catch (RuntimeException $e) {
        return $this->json(['success' => false, 'error' => $e->getMessage()], 422);
    }
}
```

### Slim

```php
$app->post('/api/register', function (Request $request, Response $response) {
    try {
        $dto = RegisterUserRequest::fromJson($request->getBody()->getContents());
        
        // Process registration
        // ...
        
        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (RuntimeException $e) {
        $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
        return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
    }
});
```

---

## 📚 Additional Resources

- [Main Documentation](../README.md)
- [PHPStan Configuration](../phpstan.neon)
- [Test Cases](../tests/)

---

## 🐛 Found an Issue?

If you find any issues with the examples or have suggestions for new examples, please open an issue on GitHub.

