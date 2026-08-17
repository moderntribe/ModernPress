<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Field_Models\DTO;

use ReflectionClass;
use ReflectionProperty;
/**
 * Lightweight array-hydrated DTO used by Field Models.
 *
 * Protected method names match Spatie DTO v2 (`castValue`, `getFieldValidators`, …)
 * so existing overrides remain valid.
 */
abstract class Data_Transfer_Object
{
    /** @var string[] */
    protected array $exceptKeys = [];
    /** @var string[] */
    protected array $onlyKeys = [];
    /**
     * @param array<array-key, mixed> $arrayOfParameters
     *
     * @return static[]
     */
    public static function arrayOf(array $arrayOfParameters) : array
    {
        return \array_map(static fn($parameters) => new static(\is_array($parameters) ? $parameters : (array) $parameters), $arrayOfParameters);
    }
    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(array $parameters = [])
    {
        $validators = $this->getFieldValidators();
        $value_caster = $this->getValueCaster();
        foreach ($validators as $field => $validator) {
            $has_parameter = \array_key_exists($field, $parameters);
            // Keep the class-declared default when the key is absent from input.
            if (!$has_parameter && $validator->hasDefaultValue) {
                unset($parameters[$field]);
                continue;
            }
            if (!$has_parameter) {
                $value = $validator->isNullable ? null : $this->defaultForValidator($validator);
            } else {
                $value = $parameters[$field];
            }
            $value = $this->castValue($value_caster, $validator, $value);
            $this->{$field} = $value;
            unset($parameters[$field]);
        }
    }
    /**
     * @return array<string, mixed>
     */
    public function all() : array
    {
        $data = [];
        $class = new ReflectionClass(static::class);
        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }
            $name = $property->getName();
            if ($property->isInitialized($this)) {
                $data[$name] = $property->getValue($this);
            }
        }
        return $data;
    }
    /**
     * @return static
     */
    public function only(string ...$keys) : self
    {
        $clone = clone $this;
        $clone->onlyKeys = [...$this->onlyKeys, ...$keys];
        return $clone;
    }
    /**
     * @return static
     */
    public function except(string ...$keys) : self
    {
        $clone = clone $this;
        $clone->exceptKeys = [...$this->exceptKeys, ...$keys];
        return $clone;
    }
    /**
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        $array = $this->onlyKeys ? \array_intersect_key($this->all(), \array_flip($this->onlyKeys)) : \array_diff_key($this->all(), \array_flip($this->exceptKeys));
        return $this->parseArray($array);
    }
    /**
     * @param array<string, mixed> $array
     *
     * @return array<string, mixed>
     */
    protected function parseArray(array $array) : array
    {
        foreach ($array as $key => $value) {
            if ($value instanceof self || $value instanceof Data_Transfer_Object_Collection) {
                $array[$key] = $value->toArray();
                continue;
            }
            if (\is_array($value)) {
                $array[$key] = $this->parseArray($value);
            }
        }
        return $array;
    }
    /**
     * @return array<string, Field_Validator>
     */
    protected function getFieldValidators() : array
    {
        $class = new ReflectionClass(static::class);
        $properties = [];
        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }
            $properties[$property->getName()] = Field_Validator::fromReflection($property);
        }
        return $properties;
    }
    /**
     * @param mixed $value
     *
     * @return mixed
     */
    protected function castValue(Value_Caster $valueCaster, Field_Validator $fieldValidator, $value)
    {
        if (\is_array($value)) {
            return $valueCaster->cast($value, $fieldValidator);
        }
        return $value;
    }
    protected function getValueCaster() : Value_Caster
    {
        return new Value_Caster();
    }
    /**
     * @return mixed
     */
    protected function defaultForValidator(Field_Validator $validator)
    {
        foreach ($validator->allowedTypes as $type) {
            if (\is_subclass_of($type, self::class)) {
                return new $type([]);
            }
            if (\is_subclass_of($type, Data_Transfer_Object_Collection::class)) {
                return new $type([]);
            }
            switch ($type) {
                case 'string':
                    return '';
                case 'integer':
                    return 0;
                case 'boolean':
                    return \false;
                case 'double':
                    return 0.0;
                case 'array':
                    return [];
            }
        }
        return null;
    }
}
