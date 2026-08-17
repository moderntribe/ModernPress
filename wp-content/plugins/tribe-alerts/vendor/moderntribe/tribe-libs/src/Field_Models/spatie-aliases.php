<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped;

/**
 * Back-compat class aliases for projects that still typehint Spatie DTO v2 classes
 * in Field_Model overrides (`castValue`, `castType`, etc.).
 *
 * Only registered when Spatie DTO is not installed, so a leftover project dependency
 * does not conflict.
 */
use Tribe\Alert_Scoped\Tribe\Libs\Field_Models\DTO\Data_Transfer_Object;
use Tribe\Alert_Scoped\Tribe\Libs\Field_Models\DTO\Data_Transfer_Object_Collection;
use Tribe\Alert_Scoped\Tribe\Libs\Field_Models\DTO\Field_Validator;
use Tribe\Alert_Scoped\Tribe\Libs\Field_Models\DTO\Value_Caster;
if (!\class_exists(\Tribe\Alert_Scoped\Spatie\DataTransferObject\DataTransferObject::class, \false)) {
    \class_alias(Data_Transfer_Object::class, \Tribe\Alert_Scoped\Spatie\DataTransferObject\DataTransferObject::class);
}
if (!\class_exists(\Tribe\Alert_Scoped\Spatie\DataTransferObject\FlexibleDataTransferObject::class, \false)) {
    \class_alias(Data_Transfer_Object::class, \Tribe\Alert_Scoped\Spatie\DataTransferObject\FlexibleDataTransferObject::class);
}
if (!\class_exists(\Tribe\Alert_Scoped\Spatie\DataTransferObject\DataTransferObjectCollection::class, \false)) {
    \class_alias(Data_Transfer_Object_Collection::class, \Tribe\Alert_Scoped\Spatie\DataTransferObject\DataTransferObjectCollection::class);
}
if (!\class_exists(\Tribe\Alert_Scoped\Spatie\DataTransferObject\ValueCaster::class, \false)) {
    \class_alias(Value_Caster::class, \Tribe\Alert_Scoped\Spatie\DataTransferObject\ValueCaster::class);
}
if (!\class_exists(\Tribe\Alert_Scoped\Spatie\DataTransferObject\FieldValidator::class, \false)) {
    \class_alias(Field_Validator::class, \Tribe\Alert_Scoped\Spatie\DataTransferObject\FieldValidator::class);
}
