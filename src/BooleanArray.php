<?php

namespace apemsel\AttributedString;

/**
 * BooleanArray
 *
 * A time efficient Attribute implementation using a standard PHP array of booleans
 *
 * @author Adrian Pemsel <apemsel@gmail.com>
 */
class BooleanArray implements MutableAttribute
{
    protected $attribute;
    protected $length;

    /**
     * @param int length of array
     */
    public function __construct(int $length)
    {
        $this->length = $length;
        $this->attribute = array_fill(0, $length, false);
    }

    /**
     * Returns the array as a visual string
     *
     * @return string array as visual string of 0s and 1s
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Returns the array as a visual string with custom chars for 0s and 1s
     *
     * @param string $true representation of 1s
     * @param string $true representation of 0s
     * @return string array as visual string of the given representations
     */
    public function toString(string $true = "1", string $false = "0"): string
    {
        return implode("", array_map(function ($v) use ($true, $false) {
            return $v ? $true : $false;
        }, $this->attribute));
    }

    /**
     * Set given range to a state
     *
     * @param int $from start offset
     * @param int $to end offset
     * @param bool $state set state to true (default) or false
     */
    public function setRange(int $from, int $to, bool $state = true): void
    {
        // Set attribute state for given range
        $this->attribute = array_replace($this->attribute, array_fill($from, $to - $from + 1, $state));
    }

    /**
     * Search inside bitmap for ranges with the given state
     *
     * @param int $offset start offset
     * @param bool $returnLength if true (default is false), return an array with position and length of the found range
     * @param bool $state the state to look for (default is true)
     * @param bool $strict perform strict comparison during search
     * @return int|int[] either position or position and lenght in an array
     */
    public function search($offset = 0, $returnLength = false, $state = true, $strict = true)
    {
        $a = $this->attribute;
        if ($offset) {
            $a = array_slice($a, $offset, NULL, true);
        }

        $pos = array_search($state, $a, $strict);

        if ($returnLength) {
            if (false === $pos) {
                return false;
            }

            $a = array_slice($a, $pos - $offset);
            $length = array_search(!$state, $a, $strict);
            $length = $length ? $length : $this->length - $pos;
            return [$pos, $length];
        } else {
            return $pos;
        }
    }

    // MutableAttribute interface

    /**
     * Insert a piece into the array at given offset with a given state and length
     *
     * @param int $offset offset
     * @param int $length length of inserted piece
     * @param bool $state state of inserted piece
     */
    public function insert($offset, $length, $state)
    {
        $this->length += $length;
        array_splice($this->attribute, $offset, 0, array_fill(0, $length, $state));
    }

    /**
     * Delete a piece of the array at given offset with a given length
     *
     * @param int $offset offset
     * @param int $length length of inserted piece
     */
    public function delete($offset, $length)
    {
        $this->length -= $length;
        array_splice($this->attribute, $offset, $length);
    }

    // ArrayAccess interface

    /**
     * Check if the given offset exists in the array
     *
     * @param int $offset offset
     * @return bool does the offset exist
     */
    public function offsetExists(mixed $offset): bool
    {
        return $offset < $this->length;
    }

    /**
     * Get bool at given offset
     *
     * @param int $offset offset
     * @return bool bit at given offset
     */
    public function offsetGet(mixed $offset): bool
    {
        return $this->attribute[$offset];
    }

    /**
     * Set bool at given offset
     *
     * @param int $offset offset
     * @param bool $value bit at given offset
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->attribute[$offset] = (bool) $value;
    }

    /**
     * Unset bit at given offset
     *
     * @param int $offset offset
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->attribute[$offset]);
    }

    // Countable interface

    /**
     * Return array length
     *
     * @return int array length
     */
    public function count(): int
    {
        return $this->length;
    }
}
