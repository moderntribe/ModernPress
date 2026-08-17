<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Field_Models\DTO;

use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
/**
 * Describes a public property for hydration and light validation.
 *
 * Public property names match Spatie DTO v2 (`allowedTypes`, etc.) so existing
 * `Field_Model::castValue()` / `castType()` overrides keep working.
 */
final class Field_Validator
{
    private const TYPE_MAP = ['int' => 'integer', 'bool' => 'boolean', 'float' => 'double'];
    public bool $isNullable;
    public bool $isMixed;
    public bool $isMixedArray;
    public bool $hasDefaultValue;
    public bool $hasTypeDeclaration;
    /** @var string[] */
    public array $allowedTypes;
    /** @var string[] */
    public array $allowedArrayTypes;
    /**
     * @param string[] $allowedTypes
     * @param string[] $allowedArrayTypes
     */
    public function __construct(bool $hasTypeDeclaration, bool $hasDefaultValue, bool $isNullable, bool $isMixed, bool $isMixedArray, array $allowedTypes, array $allowedArrayTypes)
    {
        $this->hasTypeDeclaration = $hasTypeDeclaration;
        $this->hasDefaultValue = $hasDefaultValue;
        $this->isNullable = $isNullable;
        $this->isMixed = $isMixed;
        $this->isMixedArray = $isMixedArray;
        $this->allowedTypes = $allowedTypes;
        $this->allowedArrayTypes = $allowedArrayTypes;
    }
    public static function fromReflection(ReflectionProperty $property) : self
    {
        $doc_types = self::docblockTypes($property);
        if ($doc_types !== null) {
            return self::fromDocblock($doc_types, self::propertyHasDefault($property));
        }
        return self::fromPropertyType($property);
    }
    /**
     * @param string[] $definitionTypes
     */
    private static function fromDocblock(array $definitionTypes, bool $hasDefaultValue) : self
    {
        $normalized = self::normalizeTypes(...$definitionTypes);
        $is_nullable = self::definitionAllowsNull($definitionTypes);
        $is_mixed = \in_array('mixed', $normalized, \true);
        $is_mixed_array = \false;
        $array_types = [];
        $allowed = [];
        foreach ($definitionTypes as $raw) {
            $type = \trim($raw);
            if ($type === '' || $type === 'null') {
                continue;
            }
            if (\str_ends_with($type, '[]')) {
                $array_types[] = self::normalizeType(\substr($type, 0, -2));
                $allowed[] = $type;
                $is_mixed_array = \true;
                continue;
            }
            if (\str_starts_with($type, 'array') || $type === 'iterable') {
                $allowed[] = 'array';
                $is_mixed_array = \true;
                continue;
            }
            $allowed[] = self::normalizeType($type);
        }
        return new self($allowed !== [], $hasDefaultValue, $is_nullable, $is_mixed, $is_mixed_array, \array_values(\array_filter($allowed)), \array_values(\array_filter($array_types)));
    }
    private static function fromPropertyType(ReflectionProperty $property) : self
    {
        $type = $property->getType();
        $has_type = $property->hasType();
        $allowed = [];
        $allowed_array = [];
        $is_nullable = \true;
        $is_mixed_array = \false;
        if ($type instanceof ReflectionNamedType) {
            $is_nullable = $type->allowsNull();
            $name = self::normalizeType($type->getName());
            $allowed[] = $name;
            $is_mixed_array = \in_array($name, ['array', 'iterable'], \true);
            if (\is_string($name) && \is_subclass_of($name, Data_Transfer_Object_Collection::class)) {
                $allowed_array = self::collectionItemTypes($name);
            }
        } elseif ($type instanceof ReflectionUnionType) {
            $is_nullable = $type->allowsNull();
            foreach ($type->getTypes() as $inner) {
                if (!$inner instanceof ReflectionNamedType || $inner->getName() === 'null') {
                    continue;
                }
                $name = self::normalizeType($inner->getName());
                $allowed[] = $name;
                if (\in_array($name, ['array', 'iterable'], \true)) {
                    $is_mixed_array = \true;
                }
                if (\is_string($name) && \is_subclass_of($name, Data_Transfer_Object_Collection::class)) {
                    $allowed_array = \array_merge($allowed_array, self::collectionItemTypes($name));
                }
            }
        }
        return new self($has_type, self::propertyHasDefault($property), $is_nullable, !$has_type, $is_mixed_array, \array_values(\array_filter($allowed)), \array_values(\array_unique(\array_filter($allowed_array))));
    }
    /**
     * @return string[]|null
     */
    private static function docblockTypes(ReflectionProperty $property) : ?array
    {
        $doc = $property->getDocComment();
        if (!$doc || !\preg_match('/@var\\s+([^\\n*]+)/', $doc, $matches)) {
            return null;
        }
        $definition = \trim($matches[1]);
        if ($definition === '') {
            return null;
        }
        return \array_map('trim', \explode('|', $definition));
    }
    private static function definitionAllowsNull(array $types) : bool
    {
        foreach ($types as $type) {
            $type = \trim($type);
            if ($type === 'null' || $type === 'mixed' || \str_starts_with($type, '?')) {
                return \true;
            }
        }
        return \false;
    }
    private static function propertyHasDefault(ReflectionProperty $property) : bool
    {
        if (\method_exists($property, 'hasDefaultValue')) {
            return $property->hasDefaultValue();
        }
        return $property->isDefault();
    }
    /**
     * @return string[]
     */
    private static function collectionItemTypes(string $collection_class) : array
    {
        if (!\class_exists($collection_class)) {
            return [];
        }
        $method = (new \ReflectionClass($collection_class))->getMethod('current');
        $return = $method->getReturnType();
        if ($return instanceof ReflectionNamedType && $return->getName() !== 'null') {
            return [self::normalizeType($return->getName())];
        }
        return [];
    }
    /**
     * @return string[]
     */
    private static function normalizeTypes(string ...$types) : array
    {
        return \array_values(\array_filter(\array_map([self::class, 'normalizeType'], $types)));
    }
    private static function normalizeType(?string $type) : ?string
    {
        if ($type === null || $type === '') {
            return null;
        }
        $type = \ltrim(\trim($type), '?');
        return self::TYPE_MAP[$type] ?? $type;
    }
    /**
     * @param mixed $value
     */
    public function isValidType($value) : bool
    {
        if (!$this->hasTypeDeclaration || $this->isMixed) {
            return \true;
        }
        if ($this->isNullable && $value === null) {
            return \true;
        }
        if (\is_iterable($value) && $this->isMixedArray) {
            return \true;
        }
        if (\is_iterable($value)) {
            return $this->isValidIterable($value);
        }
        foreach ($this->allowedTypes as $type) {
            if ($this->assertType($type, $value)) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @param iterable<mixed> $iterable
     */
    private function isValidIterable(iterable $iterable) : bool
    {
        foreach ($this->allowedTypes as $type) {
            if ($this->assertType($type, $iterable)) {
                return \true;
            }
        }
        if ($this->allowedArrayTypes === []) {
            return \false;
        }
        foreach ($iterable as $item) {
            $valid = \false;
            foreach ($this->allowedArrayTypes as $type) {
                if ($this->assertType($type, $item)) {
                    $valid = \true;
                    break;
                }
            }
            if (!$valid) {
                return \false;
            }
        }
        return \true;
    }
    /**
     * @param mixed $value
     */
    private function assertType(string $type, $value) : bool
    {
        return $value instanceof $type || \gettype($value) === $type;
    }
}
