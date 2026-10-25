<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use tommyknocker\struct\CollectionType;
use tommyknocker\struct\exception\FieldNotFoundException;
use tommyknocker\struct\exception\ValidationException;
use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;
use tommyknocker\struct\validation\rules\CountRule;
use tommyknocker\struct\validation\rules\RangeRule;

final class StockChange extends Struct
{
    #[Field]
    public readonly string $sku;

    #[Field(validationRules: [new RangeRule(1, 100)])]
    public readonly int $quantity;
}

final class StockRequest extends Struct
{
    protected const STRICT = true;

    /** @var list<StockChange> */
    #[Field(StockChange::class, collection: CollectionType::List, validationRules: [new CountRule(min: 1)])]
    public readonly array $items;
}

// Framework-independent boundary: distinguish malformed JSON, missing input,
// and invalid values. Validation stops at the first problem in each request.
/** @return array<string, mixed> */
function validateStockRequest(string $json): array
{
    try {
        $request = StockRequest::fromJson($json);

        return ['status' => 200, 'data' => $request->toArray()];
    } catch (JsonException $error) {
        return ['status' => 400, 'error' => 'malformed_json'];
    } catch (FieldNotFoundException $error) {
        return ['status' => 422, 'error' => 'missing_field', 'field' => $error->fieldName];
    } catch (ValidationException $error) {
        return ['status' => 422, 'error' => 'invalid_input', 'field' => $error->fieldName];
    }
}

$cases = [
    ['json' => '{', 'expected' => ['status' => 400, 'error' => 'malformed_json']],
    ['json' => '{}', 'expected' => ['status' => 422, 'error' => 'missing_field', 'field' => 'items']],
    ['json' => '{"items":[],"unexpected":true}', 'expected' => ['status' => 422, 'error' => 'invalid_input', 'field' => 'unexpected']],
    ['json' => '{"items":[{"sku":"BOOK","quantity":0}]}', 'expected' => ['status' => 422, 'error' => 'invalid_input', 'field' => 'items[0].quantity']],
];
foreach ($cases as $case) {
    $response = validateStockRequest($case['json']);
    if ($response !== $case['expected']) {
        throw new RuntimeException('Boundary returned the wrong error response');
    }
    echo json_encode($response, JSON_THROW_ON_ERROR) . "\n";
}
