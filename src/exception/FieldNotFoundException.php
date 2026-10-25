<?php

declare(strict_types=1);

namespace tommyknocker\struct\exception;

/**
 * Exception thrown when a required field is missing
 */
final class FieldNotFoundException extends StructException
{
    public function __construct(
        public readonly string $fieldName,
        public readonly ?string $alias = null,
        ?\Throwable $previous = null
    ) {
        $message = "Missing required field: $fieldName";
        if ($alias !== null) {
            $message .= " (alias: $alias)";
        }
        parent::__construct($message, 0, $previous);
    }
}
