<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use tommyknocker\struct\exception\FieldNotFoundException;
use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\validation\rules\EmailRule;
use tommyknocker\struct\validation\rules\LengthRule;

// An API boundary: accept only a declared, validated registration request.
final class RegisterRequest extends Struct
{
    protected const STRICT = true;

    #[Field(validationRules: [new EmailRule()])]
    public readonly string $email;

    #[Field(validationRules: [new LengthRule(8, 128)])]
    public readonly string $password;

    #[Field(required: false)]
    public readonly ?string $phone;

    #[Field(default: 'en')]
    public readonly string $locale;
}

$request = RegisterRequest::fromJson('{
    "email": "alice@example.com",
    "password": "correct-horse-battery-staple"
}');

// Application code gets real typed values, including explicit defaults.
if ($request->phone !== null || $request->locale !== 'en') {
    throw new RuntimeException('Optional/default field handling failed');
}

try {
    RegisterRequest::fromJson('{"email":"alice@example.com"}');

    throw new RuntimeException('Missing password should have been rejected');
} catch (FieldNotFoundException $error) {
    if ($error->fieldName !== 'password') {
        throw $error;
    }
    echo 'Missing field: ' . $error->fieldName . "\n";
}

// Do not export a password-bearing input DTO as a public response.
echo json_encode(['acceptedEmail' => $request->email, 'locale' => $request->locale], JSON_THROW_ON_ERROR) . "\n";
