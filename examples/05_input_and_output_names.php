<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use tommyknocker\struct\exception\ValidationException;
use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

// Translate an upstream snake_case contract to a downstream contract.
final class PartnerContact extends Struct
{
    #[Field(alias: 'contact_id', outputAlias: 'id')]
    public readonly string $contactId;

    #[Field(alias: 'full_name', outputAlias: 'displayName')]
    public readonly string $fullName;
}

$contact = PartnerContact::fromJson('{"contact_id":"c-1","full_name":"Alice Smith"}');
if ($contact->toArray() !== ['id' => 'c-1', 'displayName' => 'Alice Smith']) {
    throw new RuntimeException('Input and output names were conflated');
}
echo $contact->toJson() . "\n";

// For the same wire contract in both directions, declare identical aliases.
final class SymmetricContact extends Struct
{
    #[Field(alias: 'contact_id', outputAlias: 'contact_id')]
    public readonly string $contactId;
}

$original = new SymmetricContact(['contact_id' => 'c-1']);
$restored = SymmetricContact::fromJson($original->toJson());
if ($restored->contactId !== $original->contactId) {
    throw new RuntimeException('Symmetric mapping did not round-trip');
}

// Supplying both names is ambiguous, even if unknown fields are otherwise allowed.
try {
    new SymmetricContact(['contactId' => 'c-1', 'contact_id' => 'c-2']);

    throw new RuntimeException('Conflicting names should have been rejected');
} catch (ValidationException $error) {
    echo $error->fieldName . ': ' . $error->getMessage() . "\n";
}
