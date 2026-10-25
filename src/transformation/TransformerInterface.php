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
     * @throws \tommyknocker\struct\exception\TransformationException For expected invalid input.
     * Programming errors and unrelated exceptions propagate unchanged.
     */
    public function transform(mixed $value): mixed;
}
