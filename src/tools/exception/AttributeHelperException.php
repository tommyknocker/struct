<?php

declare(strict_types=1);

namespace tommyknocker\struct\tools\exception;

use Exception;

/**
 * Base exception for AttributeHelper operations
 */
class AttributeHelperException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
