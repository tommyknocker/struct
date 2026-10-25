<?php

declare(strict_types=1);

namespace tommyknocker\struct;

/** Input/output shape of a typed collection. */
enum CollectionType
{
    case Preserve;
    case List;
    case Map;
}
