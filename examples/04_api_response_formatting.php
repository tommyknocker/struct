<?php

declare(strict_types=1);

/**
 * Example 4: API Response Formatting
 * 
 * This example shows how to use Struct for consistent API responses
 * that are sent back to mobile apps.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

// Response structures
final class User extends Struct
{
    #[Field('string')]
    public readonly string $id;

    #[Field('string')]
    public readonly string $username;

    #[Field('string')]
    public readonly string $email;

    #[Field('string', nullable: true)]
    public readonly ?string $avatarUrl;

    #[Field(\DateTimeImmutable::class)]
    public readonly \DateTimeImmutable $createdAt;
}

final class PaginationMeta extends Struct
{
    #[Field('int')]
    public readonly int $currentPage;

    #[Field('int')]
    public readonly int $perPage;

    #[Field('int')]
    public readonly int $total;

    #[Field('int')]
    public readonly int $lastPage;

    #[Field('bool')]
    public readonly bool $hasMore;
}

final class ApiResponse extends Struct
{
    #[Field('bool')]
    public readonly bool $success;

    #[Field('mixed', nullable: true)]
    public readonly mixed $data;

    #[Field('string', nullable: true)]
    public readonly ?string $error;

    #[Field('int', default: 200)]
    public readonly int $statusCode;
}

final class PaginatedResponse extends Struct
{
    #[Field('bool')]
    public readonly bool $success;

    #[Field('mixed')]
    public readonly mixed $data;

    #[Field(PaginationMeta::class)]
    public readonly PaginationMeta $meta;
}

// API Controller simulation
class UserController
{
    public function getUser(string $userId): string
    {
        try {
            // Simulate database fetch
            $user = new User([
                'id' => $userId,
                'username' => 'john_doe',
                'email' => 'john@example.com',
                'avatarUrl' => 'https://example.com/avatars/john.jpg',
                'createdAt' => '2024-01-15 10:30:00',
            ]);

            $response = new ApiResponse([
                'success' => true,
                'data' => $user->toArray(),
                'error' => null,
                'statusCode' => 200,
            ]);

            return $response->toJson(pretty: true);
        } catch (\Exception $e) {
            $response = new ApiResponse([
                'success' => false,
                'data' => null,
                'error' => 'User not found',
                'statusCode' => 404,
            ]);

            return $response->toJson(pretty: true);
        }
    }

    public function listUsers(int $page = 1, int $perPage = 10): string
    {
        // Simulate database fetch with pagination
        $users = [
            new User([
                'id' => 'user1',
                'username' => 'alice',
                'email' => 'alice@example.com',
                'avatarUrl' => null,
                'createdAt' => '2024-01-10 08:00:00',
            ]),
            new User([
                'id' => 'user2',
                'username' => 'bob',
                'email' => 'bob@example.com',
                'avatarUrl' => 'https://example.com/avatars/bob.jpg',
                'createdAt' => '2024-01-12 14:20:00',
            ]),
            new User([
                'id' => 'user3',
                'username' => 'charlie',
                'email' => 'charlie@example.com',
                'avatarUrl' => null,
                'createdAt' => '2024-01-15 16:45:00',
            ]),
        ];

        $total = 25; // Total users in database
        $lastPage = (int) ceil($total / $perPage);

        $response = new PaginatedResponse([
            'success' => true,
            'data' => array_map(fn(User $u) => $u->toArray(), $users),
            'meta' => [
                'currentPage' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'lastPage' => $lastPage,
                'hasMore' => $page < $lastPage,
            ],
        ]);

        return $response->toJson(pretty: true);
    }
}

// Test API responses
$controller = new UserController();

echo "=== Get Single User ===\n";
echo $controller->getUser('user123');
echo "\n\n";

echo "=== Get Users List (Paginated) ===\n";
echo $controller->listUsers(page: 1, perPage: 10);
echo "\n\n";

echo "=== Error Response Example ===\n";
try {
    throw new \RuntimeException("Unauthorized access");
} catch (\Exception $e) {
    $errorResponse = new ApiResponse([
        'success' => false,
        'data' => null,
        'error' => $e->getMessage(),
        'statusCode' => 401,
    ]);
    echo $errorResponse->toJson(pretty: true);
}
echo "\n";

