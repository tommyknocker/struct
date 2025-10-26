<?php

declare(strict_types=1);

namespace tommyknocker\struct\tools\exception;

/**
 * Exception thrown when file processing fails
 */
class FileProcessingException extends AttributeHelperException
{
    public function __construct(
        string $filePath,
        string $reason = '',
        int $code = 0,
        ?\Exception $previous = null
    ) {
        $message = "Failed to process file '{$filePath}'";
        if ($reason !== '') {
            $message .= ": {$reason}";
        }

        parent::__construct($message, $code, $previous);
    }
}
