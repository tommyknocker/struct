<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use tommyknocker\struct\exception\TransformationException;
use tommyknocker\struct\exception\ValidationException;
use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\transformation\TransformerInterface;
use tommyknocker\struct\validation\rules\RangeRule;

final class PartnerReference implements TransformerInterface
{
    public function transform(mixed $value): mixed
    {
        // Intentionally non-idempotent: copying must not prefix an unchanged value twice.
        return is_string($value) ? 'partner:' . trim($value) : $value;
    }
}

final class DecimalInteger implements TransformerInterface
{
    public function transform(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }
        $number = filter_var($value, FILTER_VALIDATE_INT);

        if ($number === false) {
            throw new TransformationException('Expected a decimal integer');
        }

        return $number;
    }
}

final class ImportJob extends Struct
{
    #[Field(transformers: [new PartnerReference()])]
    public readonly string $reference;

    #[Field(default: '0', transformers: [new DecimalInteger()], validationRules: [new RangeRule(0, 5)])]
    public readonly int $attempts;
}

$original = new ImportJob(['reference' => ' order-42 ']);
$retried = $original->with(['attempts' => '1']);
$renamed = $retried->with(['reference' => 'order-43']);
if ($original->reference !== 'partner:order-42' || $original->attempts !== 0
    || $retried->reference !== 'partner:order-42' || $retried->attempts !== 1
    || $renamed->reference !== 'partner:order-43') {
    throw new RuntimeException('Transformations ran more than once or changed the source');
}
echo $retried->toJson() . "\n";

try {
    $original->with(['attempts' => 'not-an-integer']);

    throw new RuntimeException('Invalid transformed input should have been rejected');
} catch (ValidationException $error) {
    echo $error->fieldName . ': ' . $error->getMessage() . "\n";
}
