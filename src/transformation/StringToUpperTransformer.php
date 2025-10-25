<?php

declare(strict_types=1);

namespace tommyknocker\struct\transformation;

/**
 * Transformer that converts strings to uppercase
 */
final class StringToUpperTransformer implements TransformerInterface
{
    /**
     * Transforms the given value to uppercase if it is a string
     * @param mixed $value The value to transform
     * @return mixed The transformed value
     */
    public function transform(mixed $value): mixed
    {
        return is_string($value) ? strtoupper($value) : $value;
    }
}
