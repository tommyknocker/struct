<?php

declare(strict_types=1);

namespace tommyknocker\struct\transformation;

/**
 * Interface for data transformation
 */
interface TransformerInterface
{
    /**
     * Transform a value
     *
     * @param mixed $value The value to transform
     * @return mixed The transformed value
     */
    public function transform(mixed $value): mixed;
}
