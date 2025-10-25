<?php

declare(strict_types=1);

namespace tommyknocker\struct\exception;

/**
 * Exception thrown when field validation fails
 */
final class ValidationException extends StructException
{
    /**
     * @var string The name of the field that failed validation
     */
    public readonly string $fieldName;

    /**
     * @var mixed The value that failed validation
     */
    public readonly mixed $value;

    public function __construct(
        string $message,
        string $fieldName,
        mixed $value,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->fieldName = $fieldName;
        $this->value = $value;
    }
}
