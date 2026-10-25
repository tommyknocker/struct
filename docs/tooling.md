# Optional adapters, scaffolding, and development

[Documentation](README.md) · [Examples](../examples/README.md)

`factory\StructFactory` supplies generic creation by DTO class.
`serialization\JsonSerializer` adapts the same mapping/export behavior to
`SerializerInterface`. Neither needs a container. Input transformations construct
custom value objects explicitly; there is no service locator or static validator
fallback.

Most models can be handwritten with `#[Field]`. AttributeHelper and the optional
CLI support scaffolding; neither is required to use Struct. See
[scaffolding instructions](../examples/README.md#optional-scaffolding).

## PHPStan

For PHPStan 2, include the optional extension so marked readonly properties are
recognized as initialized by Struct, without suppressing uninitialized-property
errors globally:

```neon
includes:
    - vendor/tommyknocker/struct/extension.neon
```

Projects using `phpstan/extension-installer` can discover it through Composer
metadata. PHPStan remains an optional development tool, not a runtime dependency.

## Checks

```bash
composer test
composer phpstan
composer cs-check
composer test-examples
composer test-package
composer check
composer test-coverage # Requires a coverage driver.
```

CI tests PHP 8.1–8.5. Static analysis is level 9, including examples and test
fixtures. Coverage is measured rather than promised as a fixed percentage.
Behavior changes are recorded in [CHANGELOG.md](../CHANGELOG.md).


`composer test-examples` discovers every numbered example. Each scenario checks
its outcome rather than only printing a successful-looking result.
`composer test-package` builds the working tree and installs the archive into
an isolated consumer without network access, then runs the packaged examples
and CLI. It includes this documentation in the distribution.
