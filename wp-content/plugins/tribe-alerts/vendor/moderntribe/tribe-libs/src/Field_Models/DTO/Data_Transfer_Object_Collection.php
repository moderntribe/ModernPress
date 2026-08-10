<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Field_Models\DTO;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Iterator;
use ReturnTypeWillChange;
/**
 * Collection base for Field Models.
 *
 * API-compatible with Spatie\DataTransferObject\DataTransferObjectCollection (v2).
 *
 * @implements ArrayAccess<array-key, mixed>
 * @implements Iterator<array-key, mixed>
 */
abstract class Data_Transfer_Object_Collection implements ArrayAccess, Iterator, Countable
{
    protected ArrayIterator $iterator;
    /**
     * @param array<array-key, mixed> $collection
     */
    public function __construct(array $collection = [])
    {
        $this->iterator = new ArrayIterator($collection);
    }
    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function __get(string $name)
    {
        if ($name === 'collection') {
            return $this->iterator->getArrayCopy();
        }
        return null;
    }
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->iterator->current();
    }
    /**
     * @param mixed $offset
     *
     * @return mixed|null
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->iterator[$offset] ?? null;
    }
    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value) : void
    {
        if ($offset === null) {
            $this->iterator[] = $value;
        } else {
            $this->iterator[$offset] = $value;
        }
    }
    /**
     * @param mixed $offset
     */
    public function offsetExists($offset) : bool
    {
        return $this->iterator->offsetExists($offset);
    }
    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset) : void
    {
        unset($this->iterator[$offset]);
    }
    public function next() : void
    {
        $this->iterator->next();
    }
    /**
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->iterator->key();
    }
    public function valid() : bool
    {
        return $this->iterator->valid();
    }
    public function rewind() : void
    {
        $this->iterator->rewind();
    }
    /**
     * @return array<array-key, mixed>
     */
    public function toArray() : array
    {
        $collection = $this->iterator->getArrayCopy();
        foreach ($collection as $key => $item) {
            if ($item instanceof Data_Transfer_Object || $item instanceof self) {
                $collection[$key] = $item->toArray();
            }
        }
        return $collection;
    }
    /**
     * @return array<array-key, mixed>
     */
    public function items() : array
    {
        return $this->iterator->getArrayCopy();
    }
    public function count() : int
    {
        return $this->iterator->count();
    }
}
