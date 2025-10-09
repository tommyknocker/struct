<?php
declare(strict_types=1);

namespace tommyknocker\struct\tests\fixtures;

use tommyknocker\struct\Struct;
use tommyknocker\struct\Field;

final class Address extends Struct
{
    #[Field('string')]
    public readonly string $city;

    #[Field('string')]
    public readonly string $street;
}
