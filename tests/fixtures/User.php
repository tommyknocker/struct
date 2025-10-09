<?php
declare(strict_types=1);

namespace tommyknocker\struct\tests\fixtures;

use tommyknocker\struct\Struct;
use tommyknocker\struct\Field;

final class User extends Struct
{
    #[Field('string')]
    public readonly string $name;

    #[Field(Address::class)]
    public readonly Address $address;

    #[Field(Address::class, isArray: true)]
    public readonly array $previousAddresses;
}
