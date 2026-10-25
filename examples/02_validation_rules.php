<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use tommyknocker\struct\CollectionType;
use tommyknocker\struct\exception\ValidationException;
use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\validation\rules\CountRule;
use tommyknocker\struct\validation\rules\EmailRule;
use tommyknocker\struct\validation\rules\NullableRule;
use tommyknocker\struct\validation\ValidationResult;
use tommyknocker\struct\validation\ValidationRule;

// Project-specific validation composes with built-in rules.
final class CorporateEmailRule implements ValidationRule
{
    public function validate(mixed $value): ValidationResult
    {
        return is_string($value) && str_ends_with($value, '@example.com')
            ? ValidationResult::valid()
            : ValidationResult::invalid('Use an example.com address');
    }
}

final class InviteTeam extends Struct
{
    /** @var list<string> */
    #[Field(
        'string',
        collection: CollectionType::List,
        validationRules: [new CountRule(min: 1, max: 10)],
        itemRules: [new EmailRule(), new CorporateEmailRule()]
    )]
    public readonly array $recipients;

    #[Field(default: null, validationRules: [new NullableRule(new EmailRule())])]
    public readonly ?string $replyTo;
}

$invitation = new InviteTeam(['recipients' => ['alice@example.com', 'bob@example.com']]);
if ($invitation->replyTo !== null) {
    throw new RuntimeException('Explicit nullable rule should accept the null default');
}
echo $invitation->toJson() . "\n";

// A whole-collection rule rejects an empty list; an item rule locates the bad element.
foreach ([
    ['input' => [], 'field' => 'recipients'],
    ['input' => ['alice@example.com', 'bob@outside.test'], 'field' => 'recipients[1]'],
] as $case) {
    try {
        new InviteTeam(['recipients' => $case['input']]);

        throw new RuntimeException('Invalid recipients should have been rejected');
    } catch (ValidationException $error) {
        if ($error->fieldName !== $case['field']) {
            throw $error;
        }
        echo $error->fieldName . ': ' . $error->getMessage() . "\n";
    }
}
