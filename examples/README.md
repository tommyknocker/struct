# Examples: external data in, useful payloads out

[Documentation](../docs/README.md) · [Rule reference](../docs/validation.md)

Each file is a standalone scenario with input data, application code, checked
results, and visible output. No web server, database, framework, or network is
required. Checks throw exceptions on unexpected behavior, even when PHP
assertions are disabled.

```bash
composer install
php examples/01_receive_api_request.php
php examples/10_partner_to_fulfillment.php
composer test-examples
```

Start with **01** to understand mapping. Read **10** to see an entire integration.
Use the rest as a cookbook:

| Example | Practical task | What it demonstrates |
| --- | --- | --- |
| [01 Receive an API request](01_receive_api_request.php) | Validate a registration payload | Inferred types, required fields, explicit optional fields, defaults, strict input |
| [02 Validation rules](02_validation_rules.php) | Invite a bounded list of corporate recipients | Custom rules, whole-array rules, item rules, nullable rules, indexed errors |
| [03 Nested payloads](03_nested_payloads.php) | Read an order with an address and keyed order lines | Nested DTOs, typed collections, numeric validation, explicit map output and preservation of keys |
| [04 Prepare an API response](04_prepare_api_response.php) | Build a public account response from internal storage | Explicit output fields, enums, dates, output aliases, exclusion of application state |
| [05 Input and output names](05_input_and_output_names.php) | Connect systems with different field names | Independent aliases, symmetric round trips, rejection of ambiguous keys |
| [06 Transformations and copies](06_transformations_and_copies.php) | Normalize a partner reference and retry an import job | Explicit string-to-int conversion, transformed defaults, `with()` without repeated transformations |
| [07 Batch import](07_batch_import.php) | Import valid records while reporting bad rows | Generic creation through StructFactory, per-row errors, continuing the batch |
| [08 Errors at the boundary](08_errors_at_the_boundary.php) | Translate failures into application response data | Malformed JSON, missing fields, unknown fields, nested error paths |
| [09 Webhooks and dynamic payloads](09_webhooks_and_dynamic_payloads.php) | Forward a typed envelope with a dynamic body | Unions, enum/date conversion, mixed data, preservation of `{}` versus `[]` in JSON and oversized integer precision |
| [10 Partner to fulfillment](10_partner_to_fulfillment.php) | Turn a partner order into a fulfillment job | Separate input/output DTOs, validation, application calculations, a checked outbound contract |
| [11 Webhook subscription](11_webhook_subscription.php) | Register an integration callback | UUID version, anchored identifier pattern, HTTPS-only URL, allowed event values, indexed errors and checked copies |

The examples intentionally separate mapping from application logic. Struct
validates and converts declared data; code outside the DTO computes totals,
handles records, and decides which fields the next system receives. When two
systems exchange the same contract, a single DTO with symmetric names is enough.

Errors stop at the first problem in one DTO. Batch example 07 collects failures
across separate records; that is application behavior, not aggregate field
validation inside Struct. Generic `mixed` bodies retain JSON structure but have
no schema for their contents; use nested DTOs for data that needs validation.

## Optional scaffolding

Most models only need `#[Field]` plus their PHP types. The CLI can add these
markers to existing unannotated public readonly properties:

```bash
php scripts/struct-helper.php --dry-run path/to/Models.php
php scripts/struct-helper.php --verbose path/to/models/
# From a project that installed this package:
vendor/bin/struct-helper.php --dry-run path/to/Models.php
```

The CLI parses source without executing it, leaves existing attributes untouched,
and creates backups unless `--no-backup` is supplied. It resolves namespaces,
qualified/aliased/grouped imports, parent names, interfaces, and same-file
inheritance. Unrelated or unresolved parents are left untouched. Ancestors
outside the processed file and already attributed properties need manual editing.
Collection element types/shapes and business rules are always explicit.

The reflection-based AttributeHelper is also available for already loaded
classes. Generated-code execution belongs in its tests; using Struct does not
require `eval`, generated model classes, a container, or code generation.
