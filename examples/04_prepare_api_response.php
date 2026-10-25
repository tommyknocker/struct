<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

enum AccountState: string
{
    case Active = 'active';
}

// An explicit response DTO whitelists public data from application storage.
final class PublicAccount extends Struct
{
    #[Field(outputAlias: 'user_id')]
    public readonly int $id;

    #[Field(outputAlias: 'display_name')]
    public readonly string $name;

    #[Field]
    public readonly AccountState $state;

    #[Field(outputAlias: 'created_at')]
    public readonly DateTimeImmutable $createdAt;

    // Application state is not exported because it is not a Field.
    public string $traceId = '';
}

$stored = [
    'id' => 42, 'name' => 'Alice', 'passwordHash' => 'private-hash',
    'state' => AccountState::Active, 'createdAt' => new DateTimeImmutable('2026-10-25T12:00:00+03:00'),
];
$response = new PublicAccount([
    'id' => $stored['id'], 'name' => $stored['name'],
    'state' => $stored['state'], 'createdAt' => $stored['createdAt'],
]);
$response->traceId = 'internal-trace';

$expected = [
    'user_id' => 42, 'display_name' => 'Alice', 'state' => 'active',
    'created_at' => '2026-10-25T12:00:00.000000+03:00',
];
if ($response->toArray() !== $expected) {
    throw new RuntimeException('Public response does not match its wire contract');
}
// The password was never part of this DTO, and traceId remains application state.
echo $response->toJson(pretty: true) . "\n";
