<?php

declare(strict_types=1);

namespace tommyknocker\struct\tools\exception;

/**
 * Exception thrown when class processing fails
 */
class ClassProcessingException extends AttributeHelperException
{
    public function __construct(
        string $className,
        string $reason = '',
        int $code = 0,
        ?\Exception $previous = null
    ) {
        $message = "Failed to process class '{$className}'";
        if ($reason !== '') {
            $message .= ": {$reason}";
        }

        parent::__construct($message, $code, $previous);
    }
}
