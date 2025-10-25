<?php

declare(strict_types=1);

namespace tommyknocker\struct\validation;

/**
 * Represents the result of a validation operation
 */
final class ValidationResult
{
    /**
     * @var bool Whether the validation was successful
     */
    public readonly bool $isValid;
    /**
     * @var string|null The error message if validation failed
     */
    public readonly ?string $errorMessage;

    public function __construct(
        bool $isValid,
        ?string $errorMessage = null
    ) {
        $this->isValid = $isValid;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Creates a valid ValidationResult
     * @return ValidationResult
     */
    public static function valid(): self
    {
        return new self(true);
    }

    /**
     * Creates an invalid ValidationResult with the given error message
     * @param string $message The error message
     * @return ValidationResult
     */
    public static function invalid(string $message): self
    {
        return new self(false, $message);
    }

    /**
     * Checks if the validation was successful
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * Gets the error message if validation failed
     * @return string|null
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
}
