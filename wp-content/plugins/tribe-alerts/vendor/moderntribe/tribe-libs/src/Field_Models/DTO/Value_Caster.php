<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Field_Models\DTO;

/**
 * Casts nested arrays into DTOs and collections.
 *
 * Method names match Spatie DTO v2 ValueCaster for back compatibility.
 */
final class Value_Caster
{
    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function cast($value, Field_Validator $validator)
    {
        if (!\is_array($value) || !$this->shouldBeCastToCollection($value)) {
            return $this->castValue($value, $validator->allowedTypes);
        }
        $values = $this->castCollection($value, $validator->allowedArrayTypes);
        $collection_type = $this->collectionType($validator->allowedTypes);
        return $collection_type ? new $collection_type($values) : $values;
    }
    /**
     * @param mixed    $value
     * @param string[] $allowedTypes
     *
     * @return mixed
     */
    public function castValue($value, array $allowedTypes)
    {
        foreach ($allowedTypes as $type) {
            if (!\is_subclass_of($type, Data_Transfer_Object::class)) {
                continue;
            }
            return new $type(\is_array($value) ? $value : (array) $value);
        }
        return $value;
    }
    /**
     * @param mixed    $values
     * @param string[] $allowedArrayTypes
     *
     * @return mixed
     */
    public function castCollection($values, array $allowedArrayTypes)
    {
        $cast_to = null;
        foreach ($allowedArrayTypes as $type) {
            if (\is_subclass_of($type, Data_Transfer_Object::class)) {
                $cast_to = $type;
                break;
            }
        }
        if (!$cast_to || !\is_iterable($values)) {
            return $values;
        }
        $casts = [];
        foreach ($values as $key => $value) {
            $casts[$key] = new $cast_to(\is_array($value) ? $value : (array) $value);
        }
        return $casts;
    }
    /**
     * @param string[] $types
     *
     * @return class-string<Data_Transfer_Object_Collection>|false
     */
    public function collectionType(array $types)
    {
        foreach ($types as $type) {
            if (\is_subclass_of($type, Data_Transfer_Object_Collection::class)) {
                return $type;
            }
        }
        return \false;
    }
    /**
     * @param array<mixed> $values
     */
    public function shouldBeCastToCollection(array $values) : bool
    {
        if ($values === []) {
            return \false;
        }
        foreach ($values as $key => $value) {
            if (\is_string($key) || !\is_array($value)) {
                return \false;
            }
        }
        return \true;
    }
}
