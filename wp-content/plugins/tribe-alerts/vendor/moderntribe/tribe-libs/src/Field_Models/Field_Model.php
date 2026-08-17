<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Field_Models;

use Throwable;
use Tribe\Alert_Scoped\Tribe\Libs\Field_Models\DTO\Data_Transfer_Object;
use Tribe\Alert_Scoped\Tribe\Libs\Field_Models\DTO\Data_Transfer_Object_Collection;
use Tribe\Alert_Scoped\Tribe\Libs\Field_Models\DTO\Field_Validator;
use Tribe\Alert_Scoped\Tribe\Libs\Field_Models\DTO\Value_Caster;
/**
 * Base ACF field model.
 *
 * Public/protected API matches tribe-libs 4.x + Spatie DTO v2 extension points
 * (`castValue`, `castType`, validator property names) for back compatibility.
 */
class Field_Model extends Data_Transfer_Object
{
    /**
     * Automatically cast values to their declared types before validation.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    protected function castValue(Value_Caster $valueCaster, Field_Validator $fieldValidator, $value)
    {
        $value = $this->castType($valueCaster, $fieldValidator, $value);
        return parent::castValue($valueCaster, $fieldValidator, $value);
    }
    /**
     * Attempt to cast invalid ACF values into the declared property types.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    protected function castType(Value_Caster $valueCaster, Field_Validator $fieldValidator, $value)
    {
        if ($fieldValidator->isValidType($value)) {
            return $value;
        }
        foreach ($fieldValidator->allowedTypes as $type) {
            if (\is_subclass_of($type, Data_Transfer_Object_Collection::class)) {
                if (empty($value) || !\is_array($value)) {
                    $value = new $type([]);
                    break;
                }
                $values = $valueCaster->castCollection($value, $fieldValidator->allowedArrayTypes);
                $value = new $type(\is_array($values) ? $values : []);
                break;
            }
            if (\is_subclass_of($type, Data_Transfer_Object::class)) {
                try {
                    $value = new $type((array) $value);
                    break;
                } catch (Throwable $e) {
                    continue;
                }
            }
            if ($fieldValidator->allowedArrayTypes !== [] && ($type === 'array' || \str_ends_with($type, '[]'))) {
                if (empty($value)) {
                    $value = [];
                }
                $values = $valueCaster->castCollection($value, $fieldValidator->allowedArrayTypes);
                $collection = $valueCaster->collectionType($fieldValidator->allowedTypes);
                $value = $collection ? new $collection($values) : $values;
                $value = parent::castValue($valueCaster, $fieldValidator, $value);
                break;
            }
            if ($type === 'array' && !\is_array($value)) {
                $value = [];
            }
            if (\in_array($type, ['string', 'integer', 'boolean', 'double', 'array'], \true)) {
                \settype($value, $type);
            }
            break;
        }
        return $value;
    }
}
