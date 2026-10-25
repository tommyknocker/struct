<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use tommyknocker\struct\exception\FieldNotFoundException;
use tommyknocker\struct\exception\ValidationException;
use tommyknocker\struct\factory\StructFactory;
use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\validation\rules\EmailRule;

final class ImportedContact extends Struct
{
    #[Field]
    public readonly string $externalId;

    #[Field(validationRules: [new EmailRule()])]
    public readonly string $email;
}

// A generic importer receives a DTO class, not a container or a framework service.
/** @param class-string<Struct> $type
 * @param list<array<string, mixed>> $rows
 * @return array{accepted: list<Struct>, rejected: list<array{row: int, field: string}>}
 */
function importRows(string $type, array $rows): array
{
    $factory = new StructFactory();
    $accepted = [];
    $rejected = [];
    foreach ($rows as $index => $row) {
        try {
            $accepted[] = $factory->create($type, $row);
        } catch (ValidationException|FieldNotFoundException $error) {
            // Keep row/field location; do not put the entire source record into logs.
            $rejected[] = ['row' => $index + 1, 'field' => $error->fieldName];
        }
    }

    return ['accepted' => $accepted, 'rejected' => $rejected];
}

$result = importRows(ImportedContact::class, [
    ['externalId' => 'c-1', 'email' => 'alice@example.com'],
    ['externalId' => 'c-2', 'email' => 'invalid'],
    ['externalId' => 'c-3'],
]);
if (count($result['accepted']) !== 1 || $result['rejected'] !== [
    ['row' => 2, 'field' => 'email'], ['row' => 3, 'field' => 'email'],
]) {
    throw new RuntimeException('Batch import did not isolate invalid rows');
}
echo json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n";
