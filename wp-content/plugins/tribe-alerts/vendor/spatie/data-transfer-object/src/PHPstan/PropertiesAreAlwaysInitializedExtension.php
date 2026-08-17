<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Spatie\DataTransferObject\PHPstan;

use Tribe\Alert_Scoped\PHPStan\Reflection\PropertyReflection;
use Tribe\Alert_Scoped\PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use Tribe\Alert_Scoped\Spatie\DataTransferObject\DataTransferObject;
class PropertiesAreAlwaysInitializedExtension implements ReadWritePropertiesExtension
{
    public function isAlwaysRead(PropertyReflection $property, string $propertyName) : bool
    {
        return \false;
    }
    public function isAlwaysWritten(PropertyReflection $property, string $propertyName) : bool
    {
        return \false;
    }
    public function isInitialized(PropertyReflection $property, string $propertyName) : bool
    {
        return $property->getDeclaringClass()->isSubclassOf(DataTransferObject::class);
    }
}
