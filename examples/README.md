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

**Use Case:** Registration with new validation rules system

Demonstrates:
- ✅ New validation rules (EmailRule, RangeRule, RequiredRule)
- ✅ Custom validation rules extending ValidationRule
- ✅ Specialized exception handling (ValidationException, FieldNotFoundException)
- ✅ Complex validation logic with detailed error messages
- ✅ Real-world password and phone validation

**Run:**
```bash
php examples/02_advanced_validation.php
```

**Key Features:**
- Email validation with built-in EmailRule
- Phone number validation (10-15 digits)
- Password strength requirements (uppercase, lowercase, digits)
- Age validation (13-120 years)
- Proper exception handling with field context

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

### 6. Union Types and Transformations (`06_union_types_and_transformations.php`)

**Use Case:** Flexible data types and automatic data processing

Demonstrates:
- ✅ Union types for flexible field types (`string|int`, `string|int|float`)
- ✅ Data transformations (StringToUpperTransformer, StringToLowerTransformer)
- ✅ Custom transformers (PhoneFormatterTransformer, NameFormatterTransformer)
- ✅ Multiple transformations chaining
- ✅ Combined union types and transformations
- ✅ JSON serialization with processed data

**Run:**
```bash
php examples/06_union_types_and_transformations.php
```

**Key Features:**
- Flexible field types accepting multiple types
- Automatic data formatting and processing
- Custom transformation logic
- Chain multiple transformations
- Preserve transformations in JSON output

---

### 7. Factory Pattern (`07_factory_pattern.php`)

**Use Case:** Centralized struct creation with dependency injection

Demonstrates:
- ✅ Factory pattern for struct creation
- ✅ PSR-11 container integration
- ✅ Service layer architecture
- ✅ Dependency injection
- ✅ API controller pattern
- ✅ Complex nested structures
- ✅ Custom JSON serialization

**Run:**
```bash
php examples/07_factory_pattern.php
```

**Perfect for:**
- Enterprise applications
- Microservices architecture
- Clean architecture patterns
- Testable and maintainable code
- Dependency injection frameworks

---

### 8. Strict Mode and Exceptions (`08_strict_mode_and_exceptions.php`)

**Use Case:** API security and comprehensive error handling

Demonstrates:
- ✅ Strict mode for preventing extra fields
- ✅ Flexible mode for backward compatibility
- ✅ Specialized exceptions (ValidationException, FieldNotFoundException)
- ✅ Comprehensive error handling
- ✅ API security patterns
- ✅ ArrayAccess with strict mode
- ✅ JSON serialization with strict mode

**Run:**
```bash
php examples/08_strict_mode_and_exceptions.php
```

**Key Features:**
- Prevent extra fields for security
- Detailed error reporting with field context
- Flexible vs strict validation modes
- Production-ready error handling
- API security best practices

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

### Data Processing
See: `06_union_types_and_transformations.php`

### Enterprise Applications
See: `07_factory_pattern.php`

### API Security
See: `08_strict_mode_and_exceptions.php`

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
php examples/06_union_types_and_transformations.php
php examples/07_factory_pattern.php
php examples/08_strict_mode_and_exceptions.php
```

---

## 💡 Tips

1. **Always validate input** - Use Struct for all incoming API data
2. **Use new validation rules** - Leverage EmailRule, RangeRule, RequiredRule
3. **Handle exceptions properly** - Use ValidationException and FieldNotFoundException
4. **Apply transformations** - Use transformers for data processing
5. **Use union types** - For flexible field types
6. **Enable strict mode** - For API security when needed
7. **Use factory pattern** - For enterprise applications
8. **Keep structs readonly** - Ensures immutability and thread-safety
9. **Use aliases** - Map external APIs to your naming conventions
10. **Leverage enums** - For fixed sets of values (status, types, etc.)

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
    } catch (ValidationException $e) {
        return response()->json([
            'success' => false, 
            'error' => $e->getMessage(),
            'field' => $e->fieldName
        ], 422);
    } catch (FieldNotFoundException $e) {
        return response()->json([
            'success' => false, 
            'error' => 'Missing required field: ' . $e->fieldName
        ], 400);
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
    } catch (ValidationException $e) {
        return $this->json([
            'success' => false, 
            'error' => $e->getMessage(),
            'field' => $e->fieldName
        ], 422);
    } catch (FieldNotFoundException $e) {
        return $this->json([
            'success' => false, 
            'error' => 'Missing required field: ' . $e->fieldName
        ], 400);
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
    } catch (ValidationException $e) {
        $response->getBody()->write(json_encode([
            'success' => false, 
            'error' => $e->getMessage(),
            'field' => $e->fieldName
        ]));
        return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
    } catch (FieldNotFoundException $e) {
        $response->getBody()->write(json_encode([
            'success' => false, 
            'error' => 'Missing required field: ' . $e->fieldName
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
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

