<?php

declare(strict_types=1);

namespace tommyknocker\struct\phpstan;

use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

/** Teach PHPStan that the final Struct constructor initializes marked properties. */
final class FieldPropertyExtension implements ReadWritePropertiesExtension
{
    public function isAlwaysRead(ExtendedPropertyReflection $property, string $propertyName): bool
    {
        return $this->isMapped($property);
    }

    public function isAlwaysWritten(ExtendedPropertyReflection $property, string $propertyName): bool
    {
        return $this->isMapped($property);
    }

    public function isInitialized(ExtendedPropertyReflection $property, string $propertyName): bool
    {
        return $this->isMapped($property);
    }

    private function isMapped(ExtendedPropertyReflection $property): bool
    {
        if (!$property->isPublic() || $property->isStatic() || !$property->getDeclaringClass()->isSubclassOf(Struct::class)) {
            return false;
        }
        foreach ($property->getAttributes() as $attribute) {
            if ($attribute->getName() === Field::class) {
                return true;
            }
        }

        return false;
    }
}
