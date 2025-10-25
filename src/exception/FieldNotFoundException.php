<?php

declare(strict_types=1);

namespace tommyknocker\struct\exception;

/**
 * Exception thrown when a required field is missing
 */
final class FieldNotFoundException extends StructException
{
    public function __construct(
        string $fieldName,
        ?string $alias = null
    ) {
        $message = "Missing required field: $fieldName";
        if ($alias) {
            $message .= " (alias: $alias)";
        }
        parent::__construct($message);
    }
}
