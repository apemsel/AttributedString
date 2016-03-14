<?php
namespace apemsel\AttributedString;

class Bitmap implements Attribute
{
  protected $bitmap;
  protected $length;
  
  public function __construct($length) {
    $this->length = $length;
    $this->bitmap = str_repeat(chr(0), ceil($this->length / 8));
  }
  
  public function __toString() {
    return $this->toString();
  }
  
  public function toString($true = "1", $false = "0") {
    $string = str_repeat($false, $this->length);
    for ($offset = 0; $offset < $this->length; $offset++) {
      if (ord($this->bitmap[(int) ($offset / 8)]) & (1 << $offset % 8)) {
        $string[$offset] = $true;
      }
    }
    
    return $string;
  }
  
  /**
   * Set given range to a state
   *
   * @param int $from start offset
   * @param int $to end offset
   * @param bool $state set state to true (default) or false
   */
  public function setRange($from, $to, $state = true) {
    // Set attribute state for given range
    for($i = $from; $i <= $to; $i++) {
      $this->offsetSet($i, $state);
    }
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
  public function search($offset = 0, $returnLength = false, $state = true, $strict = true) {
    for ($i = $offset; $i < $this->length; $i++) {
      if (($strict and $this->offsetGet($i) === $state) or (!$strict and $this->offsetGet($i) == $state)) {
        if ($returnLength) {
          $length = $this->search($i, false, !$state, $strict);
          $length = $length ? $length - $i : $this->length - $i;
          
          return [$i, $length];
        } else {
          return $i;
        }
      }
    }
    
    return false;
  }
  
  // ArrayAccess interface
  
  public function offsetExists($offset) {
    return is_int($offset) && $offset >= 0 && $offset < $this->length;
  }
  
  public function offsetGet($offset)
	{
		if ($this->offsetExists($offset)) {
			return (bool) (ord($this->bitmap[(int) ($offset / 8)]) & (1 << $offset % 8));
		} else {
			throw new \OutOfRangeException();
		}
	}
  
  public function offsetSet($offset, $value)
	{
		if ($this->offsetExists($offset)) {
			$index = (int) ($offset / 8);
			if ($value) {
				$this->bitmap[$index] = chr(ord($this->bitmap[$index]) | (1 << $offset % 8));
			} else {
				$this->bitmap[$index] = chr(ord($this->bitmap[$index]) & ~(1 << $offset % 8));
			}
		} else {
			throw new \OutOfRangeException();
		}
	}
  
  public function offsetUnset($offset) {
    throw new \RuntimeException("Bitmap does not support offsetUnset");
  }
  
  // Countable interface
  
  /**
   * Return bitmap length
   *
   * @return int bitmap length
   */
  public function count() {
    return $this->length;
  }
}
