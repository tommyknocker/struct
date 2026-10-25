# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.0.0] - 2026-10-25

### Breaking changes
- Replace `isArray` with explicit collection policies: List, Map, or Preserve.
- Decode oversized JSON integers as exact decimal strings instead of floats.
- Reject provably incompatible mapping/native types during schema compilation.
- Nullable no longer implies optional: fields are required unless given a default
  or `required: false`. Nullability comes from native PHP types.
- Mapped properties must be public readonly typed instance properties. The
  constructor and mapping/export methods are final.
- Remove the PSR-11 container hook and legacy static validators; no external
  runtime dependencies remain. Custom value objects use explicit transformers.
- Replace process-global strict mode with a per-DTO `STRICT` constant.
- Reject simultaneous property/alias input names instead of choosing a winner.
- Collection `validationRules` validate the entire array; `itemRules` validate
  elements. Numeric rules accept finite native numbers only.
- JSON input requires an object root and preserves dynamic object/list identity.
- `toArray()` exports only Field properties, normalizing dates to strings and enums
  to scalar values/case names. `jsonSerialize()` returns an object.
- Defaults and null values now run transformations and validation rules;
  `default: null` is an explicit default, not an omitted default.
- Collection keys are preserved instead of reindexed. Conflicting input aliases
  and duplicate output names are rejected.
- Native property types are checked without Reflection's implicit scalar coercion;
  invalid calendar dates are rejected.
- Attribute Helper no longer guesses aliases, rules, transformations, or defaults.
  The CLI adds inferred Field markers through static parsing.

### Changed
- Focus on external payload mapping, validated DTOs, and explicit outbound payloads.
- Field types and nullability can be inferred from native PHP properties.
- Default and null values run through the validation pipeline.
- Collection keys are preserved; nested errors include field paths.
- `toArray()` exports only Field properties and recursively normalizes dates and enums.
- Attribute scaffolding is type-only, without guessed business rules or defaults.
- CLI uses static parsing, supports namespaces, and does not execute target files.
- Serializer and JSON factory methods delegate to Struct's implementation.
- Example checks discover all numbered scenarios; remove automatic install/update hooks.

### Added
- PatternRule, strict OneOfRule, version-aware UuidRule, and scheme-aware UrlRule.
- A checked webhook subscription example combining format, membership, and item rules.
- Markdown documentation covering field contracts, collections, validation,
  transformations, serialization, errors, tooling, and the public API reference.
- TransformationException gives transformers an explicit input rejection contract,
  with field paths and previous exceptions retained by the mapper.
- Namespace-aware static CLI parser handles aliases, grouped imports, interfaces,
  and same-file inheritance without modifying unrelated Struct classes.
- Independent `outputAlias` names for outbound payloads, with duplicate-name checks.
- Explicit null defaults and `required: true` for nullable input presence.
- Unicode code-point `LengthRule` for string lengths.
- Integration, regression, generated-code, and CLI tests.
- Eleven scenario-driven, checked examples covering receiving, validation, nesting,
  outbound contracts, aliases, transformations/copies, batch import, boundary
  errors, dynamic webhooks, partner-to-fulfillment integration, and subscriptions.
- CountRule for collection size and NullableRule for explicit nullable validation.
- Optional PHPStan property initialization extension, with examples and fixtures
  included in static analysis.
- PHP 8.5 in the CI test matrix and isolated distribution installation checks.

### Fixed
- `with()` transforms only explicit replacements and rejects misspelled keys.
- Split previously undiscovered test classes into dedicated test files.
- Mutable date/default data is isolated per construction; mapping depth is bounded.
- Date JSON round trips and concrete DateTime property mapping.
- Calendar warnings and incorrect enum backing types produce validation errors.
- Reflection cannot silently coerce values that disagree with native property types.
- Union scaffolding generates executable attributes.
- CLI dry runs avoid side effects; `--no-backup` and failure exit statuses work.
- Remove unsupported coverage/performance/boilerplate claims from current documentation.

## [1.2.0] - 2025-10-26

### Added
- **Attribute Helper Tool** - Automated Field attribute generation for Struct classes
- **Console Interface** - Command-line tool similar to phpstan/php-cs-fixer for batch processing
- **Intelligent Type Inference** - Automatic detection of PHP property types (scalars, objects, arrays, union types)
- **Smart Validation Suggestions** - Automatic validation rule recommendations based on property names
- **Alias Generation** - Automatic snake_case alias generation for camelCase properties
- **Transformer Suggestions** - Intelligent transformer recommendations (StringToUpper, StringToLower)
- **Default Value Inference** - Smart default value suggestions based on property types and names
- **Custom Exceptions** - Dedicated exception classes for AttributeHelper operations
- **Comprehensive Test Suite** - Full test coverage for AttributeHelper functionality
- **Live Examples** - Real-world usage examples demonstrating AttributeHelper capabilities
- **Composer Scripts** - Easy integration with `composer struct-helper` commands

### Fixed
- Removed phpstan-baseline.neon file - all PHPStan Level 9 errors properly resolved
- Improved type safety in AttributeHelper with proper Reflection type handling
- Enhanced code quality by eliminating all static analysis warnings

### Changed
- Enhanced documentation with Attribute Helper section in README.md
- Updated examples directory with Attribute Helper demonstrations
- Improved codebase maintainability with automated attribute generation

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

- [Unreleased]: https://github.com/tommyknocker/struct/compare/v2.0.0...HEAD
- [2.0.0]: https://github.com/tommyknocker/struct/compare/v1.2.0...v2.0.0
- [1.2.0]: https://github.com/tommyknocker/struct/compare/v1.1.0...v1.2.0
- [1.1.0]: https://github.com/tommyknocker/struct/compare/v1.0.0...v1.1.0
- [1.0.0]: https://github.com/tommyknocker/struct/compare/v0.1.0...v1.0.0
- [0.1.0]: https://github.com/tommyknocker/struct/compare/v0.0.1...v0.1.0
- [0.0.1]: https://github.com/tommyknocker/struct/releases/tag/v0.0.1
