<?php

declare(strict_types=1);

namespace tommyknocker\struct;

/** Distinguishes an omitted default from an explicit null default. */
enum DefaultValue
{
    case Missing;
}
