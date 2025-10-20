<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests\fixtures;

enum UserType: string
{
    case Admin = 'admin';
    case Regular = 'regular';
    case Guest = 'guest';
}
