# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2025-10-25

### Added
- Union type support for flexible field definitions
- Advanced validation rules (`EmailRule`, `RangeRule`, `RequiredRule`)
- Data transformers (`StringToUpperTransformer`, `StringToLowerTransformer`)
- Legacy validator support for backward compatibility
- Metadata factory with caching
- Reflection cache for performance optimization
- Factory pattern implementation
- JSON serialization system
- Comprehensive test fixtures following "1 file - 1 class" principle
- Advanced examples demonstrating all library capabilities

### Changed
- Improved validation system architecture
- Enhanced error messages and exception handling
- Better type safety with improved PHPDoc annotations
- Optimized reflection usage with caching

### Fixed
- Fixed validation order issues
- Resolved inherited properties handling in tests
- Fixed exception type mismatches
- Corrected array type annotations for PHPStan

## [1.0.0] - 2025-10-20

### Added
- Initial release of Struct library
- Basic attribute-based field definitions
- Type validation for scalars, objects, arrays, enums, DateTime
- Immutable readonly properties
- JSON serialization support (`toJson()`, `fromJson()`)
- Array conversion with recursive support
- Default values for optional fields
- Field aliases for key mapping
- Mixed type support
- DateTime parsing from strings
- Cloning with modifications (`with()` method)
- ArrayAccess implementation
- PSR-11 container integration
- PHPStan Level 9 static analysis
- PHPUnit test coverage
- Basic examples and documentation

### Features
- Attribute-based field definitions with clean syntax
- Runtime type validation and casting
- Immutable data structures with readonly properties
- Built-in JSON serialization/deserialization
- Array-like access with `ArrayAccess`
- Support for nullable fields and default values
- Field aliases for API integration
- Mixed type handling for dynamic data
- DateTime automatic parsing
- Cloning with modifications
- PSR-12 coding standards compliance

## [0.1.0] - 2025-10-15

### Added
- Basic struct functionality
- Simple field validation
- JSON serialization
- Array access support

## [0.0.1] - 2025-10-10

### Added
- Initial project setup
- Basic struct class implementation
- Simple field attribute system
- Basic validation framework

---

## Version History

- **v1.1.0**: Major architectural improvements and new features
- **v1.0.0**: Stable release with core functionality
- **v0.1.0**: Beta release with basic features
- **v0.0.1**: Initial alpha release

## Breaking Changes

### v1.1.0
- `Field` attribute `validator` parameter is deprecated, use `validationRules` instead
- Constructor patterns changed for better consistency
- Exception types changed from generic `RuntimeException` to specific exceptions

### v1.0.0
- Initial stable API established
- All previous versions considered unstable

## Migration Guide

### From v1.0.0 to v1.1.0

#### Field Attribute Changes
```php
// Old (deprecated)
#[Field('string', validator: EmailValidator::class)]

// New (recommended)
#[Field('string', validationRules: [new EmailRule()])]
```

#### Exception Handling
```php
// Old
try {
    $struct = new MyStruct($data);
} catch (RuntimeException $e) {
    // Handle error
}

// New
try {
    $struct = new MyStruct($data);
} catch (ValidationException $e) {
    // Handle validation error
    echo "Field: " . $e->fieldName;
    echo "Value: " . $e->value;
} catch (FieldNotFoundException $e) {
    // Handle missing field
}
```

## Links

- [Unreleased]: https://github.com/tommyknocker/struct/compare/v1.1.0...HEAD
- [1.1.0]: https://github.com/tommyknocker/struct/compare/v1.0.0...v1.1.0
- [1.0.0]: https://github.com/tommyknocker/struct/compare/v0.1.0...v1.0.0
- [0.1.0]: https://github.com/tommyknocker/struct/compare/v0.0.1...v0.1.0
- [0.0.1]: https://github.com/tommyknocker/struct/releases/tag/v0.0.1
